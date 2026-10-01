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
        $statsQuery = clone $query;
        $totalUsers = $statsQuery->count();
        $approvedCount = (clone $statsQuery)->where('is_approved', 1)->count();
        $pendingCount = (clone $statsQuery)->where('is_approved', 0)->count();

        $stats = [
            'total' => $totalUsers,
            'approved' => $approvedCount,
            'pending' => $pendingCount
        ];

        $users = $query->orderBy('is_approved', 'asc') // Unapproved first
            ->orderBy('created_at', 'desc')
            ->paginate(15)->appends($request->all());

        $provinces = \App\Models\Province::orderBy('province_name', 'asc')->get();

        if ($request->ajax()) {
            return view('admin.users.table', compact('users', 'stats'))->render();
        }

        return view('admin.users.index', compact('users', 'provinces', 'stats'));
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new UsersExport($request->all()), 'users_export.xlsx');
    }

    public function toggleApproval(Request $request, $id)
    {
        $user = User::findOrFail($id);
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

    public function edit(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $provinces = \App\Models\Province::orderBy('province_name', 'asc')->get();
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

        $request->validate([
            'User_firstname' => 'required|string|max:255',
            'User_lastname' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{3,4}$/',
            'email' => 'required|email|unique:users,email,' . $id,
            'User_rank_id' => 'required',
            'Province_id' => 'required',
        ]);

        $data = $request->except(['password', 'name']);

        // Ensure name is updated from firstname/lastname
        $data['name'] = $request->User_firstname . ' ' . $request->User_lastname;

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
}
