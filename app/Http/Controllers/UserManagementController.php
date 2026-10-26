<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['province', 'district']);

        // ผู้ที่ไม่ใช่ SuperAdmin จะไม่เห็นบัญชี SuperAdmin ในรายชื่อเลย
        if (! $request->user()->isSuperAdmin()) {
            $query->withoutSuperAdmins();
        }

        // บัญชีตัวแทน (นำเข้า Word) ซ่อนไว้เป็นค่าเริ่มต้น — เฉพาะ SuperAdmin เปิดดูได้ด้วยตัวกรอง
        $showPlaceholders = $request->user()->isSuperAdmin() && $request->boolean('show_placeholders');
        if (! $showPlaceholders) {
            $query->withoutImportPlaceholders();
        }

        // Filter by Province
        if ($request->filled('province_id')) {
            $query->where('Province_id', $request->province_id);
        }

        // Filter by District
        if ($request->filled('district_id')) {
            $query->where('District_id', $request->district_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('is_approved', $request->status);
        }

        // Filter by Rank (Agency Type)
        if ($request->filled('rank_id')) {
            $query->where('User_rank_id', $request->rank_id);
        }

        // Calculate Summary Statistics (on filtered results)
        // 1 query แทน 3 (ทั้งหมด / อนุมัติแล้ว / รออนุมัติ)
        $agg = (clone $query)->reorder()->selectRaw(
            'COUNT(*) as total_users, SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_users, SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_users'
        )->first();
        $totalUsers = (int) ($agg->total_users ?? 0);
        $approvedCount = (int) ($agg->approved_users ?? 0);
        $pendingCount = (int) ($agg->pending_users ?? 0);

        $stats = [
            'total' => $totalUsers,
            'approved' => $approvedCount,
            'pending' => $pendingCount
        ];

        $users = $query->orderBy('is_approved', 'asc') // Unapproved first
            ->orderBy('created_at', 'desc')
            ->paginate(15)->appends($request->all());

        $provinces = \App\Support\AdminCache::provinces();

        if ($request->ajax()) {
            return view('admin.users.table', compact('users', 'stats'))->render();
        }

        return view('admin.users.index', compact('users', 'provinces', 'stats'));
    }

    /**
     * ผู้ที่ไม่ใช่ SuperAdmin เข้าถึงบัญชี SuperAdmin ไม่ได้ (ตอบ 404 เหมือนไม่มีบัญชีนี้)
     */
    protected function hideSuperAdminFromNonSuper(User $target): void
    {
        abort_if(($target->isSuperAdmin() || $target->isImportPlaceholder()) && ! auth()->user()->isSuperAdmin(), 404);
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->all();
        $filters['_exclude_super'] = ! $request->user()->isSuperAdmin();
        return \App\Support\EarlyDownload::excel('users_export.xlsx', fn () => new UsersExport($filters));
    }

    public function toggleApproval(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->hideSuperAdminFromNonSuper($user);
        $user->is_approved = !$user->is_approved;
        $user->save();

        $status = $user->is_approved ? 'อนุมัติเรียบร้อย' : 'ยกเลิกการอนุมัติเรียบร้อย';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$status} สำหรับผู้ใช้งาน {$user->name}",
                'is_approved' => $user->is_approved
            ]);
        }

        return redirect()->back()->with('success', "{$status} สำหรับผู้ใช้งาน {$user->name}");
    }

    /**
     * รายชื่อผู้ใช้ สคร. ที่อนุมัติแล้วและยังไม่เป็น SuperAdmin - ใช้ใน dialog "เพิ่ม SuperAdmin"
     */
    public function superAdminCandidates(Request $request)
    {
        $candidates = User::where('User_rank_id', 1)
            ->where('is_approved', 1)
            ->where('id', '!=', $request->user()->id)
            ->orderBy('User_firstname')
            ->get()
            ->reject(fn ($u) => $u->isSuperAdmin())
            ->map(function ($u) {
                $mail = (string) $u->email;
                if (str_contains($mail, '@')) {
                    [$local, $domain] = explode('@', $mail, 2);
                    $mail = mb_substr($local, 0, 2) . '***@' . $domain;
                }
                return [
                    'id' => $u->id,
                    'label' => trim(($u->prefix ?? '') . ($u->User_firstname ?? '') . ' ' . ($u->User_lastname ?? '')) . ' (' . $mail . ')',
                ];
            })
            ->values();

        return response()->json($candidates);
    }

    /**
     * มอบ/ถอนสิทธิ์ SuperAdmin ให้ผู้ใช้ระดับ สคร. (เรียกได้เฉพาะ SuperAdmin - ดู middleware super.admin)
     */
    public function toggleSuperAdmin(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $me = $request->user();

        $fail = function (string $message, int $status = 422) use ($request) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }
            return redirect()->back()->with('error', $message);
        };

        if ((int) $user->User_rank_id !== 1) {
            return $fail('มอบสิทธิ์ SuperAdmin ได้เฉพาะผู้ใช้ระดับ สคร. เท่านั้น');
        }
        if ($user->id === $me->id) {
            return $fail('ไม่สามารถเปลี่ยนสิทธิ์ของตนเองได้');
        }
        if ($user->isRootSuperAdmin()) {
            return $fail('SuperAdmin หลักไม่สามารถถอนสิทธิ์จากหน้านี้ได้');
        }
        if (! $user->is_approved) {
            return $fail('ต้องอนุมัติผู้ใช้งานก่อนจึงจะมอบสิทธิ์ SuperAdmin ได้');
        }

        $email = strtolower(trim((string) $user->email));
        $granted = array_values(array_filter(array_map('strtolower', (array) \App\Support\SystemSettings::get('super_admin_granted', []))));

        if (in_array($email, $granted, true)) {
            $granted = array_values(array_diff($granted, [$email]));
            $isSuper = false;
        } else {
            $granted[] = $email;
            $isSuper = true;
        }
        \App\Support\SystemSettings::set('super_admin_granted', array_values(array_unique($granted)));

        $name = trim(($user->prefix ?? '') . ($user->User_firstname ?? '') . ' ' . ($user->User_lastname ?? ''));
        $message = ($isSuper ? 'มอบสิทธิ์ SuperAdmin ให้ ' : 'ถอนสิทธิ์ SuperAdmin ของ ') . $name . ' เรียบร้อย';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'is_super_admin' => $isSuper]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function edit(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->hideSuperAdminFromNonSuper($user);
        $provinces = \App\Support\AdminCache::provinces();
        // Hardcoded user ranks as seen in index.blade.php
        $userRanks = [
            1 => 'สคร.',
            2 => 'สสจ.',
            3 => 'สสอ.',
            5 => 'รพ.',
            4 => 'รพ.สต.'
        ];

        // Loaded into the "แก้ไข" modal on the user list page via AJAX -
        // the full edit.blade.php page still works too, as a direct-link
        // fallback (e.g. if someone bookmarks /admin/users/{id}/edit).
        if ($request->ajax()) {
            return view('admin.users.edit-content', compact('user', 'provinces', 'userRanks'))->render();
        }

        return view('admin.users.edit', compact('user', 'provinces', 'userRanks'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->hideSuperAdminFromNonSuper($user);

        $request->validate([
            'User_firstname' => 'required|string|max:255',
            'User_lastname' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{3,4}$/',
            'email' => 'required|email|unique:users,email,' . $id,
            'User_rank_id' => 'required|in:1,2,3,4,5',
            'Province_id' => 'required',
        ]);

        $data = $request->except(['password', 'name']);

        // Ensure name is updated from firstname/lastname
        $data['name'] = $request->User_firstname . ' ' . $request->User_lastname;

        // Only SuperAdmin may change a user's rank; non-super admins keep the existing rank.
        if (! auth()->user()->isSuperAdmin()) {
            $data['User_rank_id'] = $user->User_rank_id;
        }

        $user->update($data);

        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
            $user->save();
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงข้อมูลผู้ใช้งานเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'ปรับปรุงข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->hideSuperAdminFromNonSuper($user);

        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบบัญชีของตัวเองได้'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบผู้ใช้งานเรียบร้อยแล้ว'
        ]);
    }

    /**
     * ลบผู้ใช้หลายคนพร้อมกัน (เฉพาะ SuperAdmin - กั้นที่ route ด้วย middleware super.admin)
     * ข้ามบัญชีตัวเองและบัญชี SuperAdmin เสมอ เพื่อไม่ให้ลบสิทธิ์ผู้ดูแลระบบโดยไม่ตั้งใจ
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer',
        ]);

        $users = User::whereIn('id', $data['ids'])->get();
        $deleted = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($user->id === auth()->id() || $user->isSuperAdmin()) {
                $skipped++;
                continue;
            }
            $user->delete();
            $deleted++;
        }

        \App\Support\ActivityLog::record('data_change', 'ลบผู้ใช้งานหลายรายการ ' . $deleted . ' บัญชี', null, ['deleted' => $deleted, 'skipped' => $skipped], $request);

        $message = 'ลบผู้ใช้งานเรียบร้อย ' . $deleted . ' บัญชี';
        if ($skipped > 0) {
            $message .= ' (ข้าม ' . $skipped . ' บัญชีที่เป็นตัวเอง/SuperAdmin)';
        }

        return response()->json(['success' => true, 'message' => $message, 'deleted' => $deleted, 'skipped' => $skipped]);
    }
}
