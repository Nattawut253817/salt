<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLog;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "จัดการระบบ" (System Management) admin screen - reached from the sidebar
 * link right after "จัดการแบบฟอร์ม Excel" (rank 1 only, same "จัดการระบบ"
 * section as จัดการผู้ใช้งาน / จัดการปีงบประมาณ / จัดการแบบฟอร์ม Excel).
 *
 * Mostly still a placeholder - most rows are marked "เร็ว ๆ นี้" until
 * their own settings screen is built out - except the OTP toggle below,
 * which is live: it lets a Level-1 admin turn the login-OTP step on/off
 * (see AuthController::login()) without touching code or the mail
 * server. Backed by App\Support\SystemSettings, a small JSON file under
 * storage/app/ rather than a DB table, since this project's migrations
 * can only be run from the developer's own machine, not from here.
 */
class SystemManagementController extends Controller
{
    public function index()
    {
        $otpEnabled = SystemSettings::get('login_otp_enabled', true);
        $rank1RegisterEnabled = (bool) SystemSettings::get('register_rank1_enabled', true);
        $rank1UserCount = User::where('User_rank_id', 1)->count();

        $sections = [
            [
                'title' => 'ตั้งค่าทั่วไป',
                'subtitle' => 'ชื่อระบบ โลโก้ และข้อมูลการติดต่อ',
                'icon' => 'fa-sliders',
                'color' => '#6366f1',
            ],
            [
                'title' => 'สิทธิ์การใช้งาน',
                'subtitle' => 'กำหนดสิทธิ์การเข้าถึงตามระดับผู้ใช้งาน',
                'icon' => 'fa-shield-halved',
                'color' => '#16a34a',
            ],
        ];

        [$pageViews, $pageViewsTotal, $pageViewsSince] = $this->pageViewSummary();

        return view('admin.system-management.index', compact('sections', 'otpEnabled', 'rank1RegisterEnabled', 'rank1UserCount', 'pageViews', 'pageViewsTotal', 'pageViewsSince'));
    }

    /**
     * Flips the "require OTP at login" switch. GET-triggered toggles are
     * usually a bad idea, but this one only ever runs from a same-origin
     * POST form submit (CSRF-protected like every other admin form), so
     * there's no separate confirmation step beyond the toggle itself.
     */
    public function toggleOtp(Request $request)
    {
        $enabled = $request->boolean('enabled');
        SystemSettings::set('login_otp_enabled', $enabled);

        $message = $enabled
            ? 'เปิดใช้งานการยืนยันตัวตนด้วย OTP เมื่อเข้าสู่ระบบแล้ว'
            : 'ปิดใช้งานการยืนยันตัวตนด้วย OTP เมื่อเข้าสู่ระบบแล้ว';

        return redirect()->route('admin.system-management.index')->with('success', $message);
    }

    /**
     * เปิด/ปิดตัวเลือก "สคร." ในหน้าสมัครสมาชิก (และปิดรับสมัครระดับนี้ฝั่งเซิร์ฟเวอร์ด้วย)
     * ใช้เมื่อ สคร. สมัครครบแล้ว - เฉพาะ สคร. (ระดับ 1) เท่านั้น
     */
    public function toggleRank1Register(Request $request)
    {
        abort_unless((int) auth()->user()->User_rank_id === 1, 403);

        $enabled = $request->boolean('enabled');
        SystemSettings::set('register_rank1_enabled', $enabled);

        return redirect()->route('admin.system-management.index')->with('success', $enabled
            ? 'เปิดรับสมัครสมาชิกระดับ สคร. แล้ว (ตัวเลือก สคร. จะแสดงในหน้าสมัครสมาชิก)'
            : 'ปิดรับสมัครสมาชิกระดับ สคร. แล้ว (ตัวเลือก สคร. จะไม่แสดงในหน้าสมัครสมาชิก)');
    }

    /** หน้าสาธารณะที่มีตัวนับการเข้าชม (key = ชื่อ route ที่ใช้ middleware page.view) */
    private const COUNTED_PAGES = [
        'home' => 'หน้าหลัก',
        'awareness' => 'การประเมินความตระหนักรู้',
        'reduced-sodium-menu' => 'เมนูลดโซเดียม',
        'reduced-sodium-products' => 'ผลิตภัณฑ์ลดโซเดียม',
        'new-ht-cases' => 'อัตราป่วยรายใหม่ HT',
        'kidney-dhb-report' => 'รายงาน พชอ.ไต',
        'consumption-report' => 'แบบรายงานลดการบริโภคเกลือ',
    ];

    /** @return array{0: array<int, array{label: string, views: int}>, 1: int, 2: ?\Illuminate\Support\Carbon} */
    private function pageViewSummary(): array
    {
        $rows = collect();
        if (Schema::hasTable('page_views')) {
            $rows = DB::table('page_views')->get()->keyBy('page_key');
        }

        $items = [];
        foreach (self::COUNTED_PAGES as $key => $label) {
            $items[] = ['label' => $label, 'views' => (int) optional($rows->get($key))->views];
        }

        $since = $rows->pluck('started_at')->filter()->min();

        return [$items, array_sum(array_column($items, 'views')), $since ? \Illuminate\Support\Carbon::parse($since) : null];
    }

    /**
     * รีเซ็ตตัวนับการเข้าชมทุกหน้าเป็น 0 และเริ่มนับใหม่ตั้งแต่ตอนนี้
     * (ใช้ก่อนนำระบบขึ้น server จริง) - เฉพาะ สคร. (ระดับ 1) เท่านั้น
     */
    public function resetPageViews(Request $request)
    {
        abort_unless((int) auth()->user()->User_rank_id === 1, 403);

        if (Schema::hasTable('page_views')) {
            DB::table('page_views')->update(['views' => 0, 'started_at' => now(), 'updated_at' => now()]);
        }

        return redirect()->route('admin.system-management.index')
            ->with('success', 'รีเซ็ตจำนวนการเข้าชมทุกหน้าเป็น 0 แล้ว และเริ่มนับใหม่ตั้งแต่ตอนนี้');
    }

    /**
     * "บันทึกการใช้งานระบบ" - เฉพาะ สคร. (ระดับ 1)
     *  - แท็บ ผู้ใช้งาน : ผู้ใช้แต่ละคนพร้อมการเข้าใช้สำเร็จ 5 ครั้งล่าสุด (เวลา / IP / อุปกรณ์)
     *  - แท็บ เหตุการณ์ทั้งหมด : log ทุกประเภท กรองตามประเภท ผู้ใช้ และช่วงวันที่
     */
    public function activityLogs(Request $request)
    {
        abort_unless((int) auth()->user()->User_rank_id === 1, 403);

        return view('admin.system-management.activity-logs', $this->activityLogData($request));
    }

    public function activityLogData(Request $request): array
    {
        ActivityLog::ensureTable();

        $tab = $request->query('tab') === 'events' ? 'events' : 'users';
        $q = trim((string) $request->query('q', ''));
        $event = (string) $request->query('event', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $stats = [
            'total' => DB::table('activity_logs')->count(),
            'logins_today' => DB::table('activity_logs')->where('event', 'login_success')->where('created_at', '>=', now('Asia/Bangkok')->startOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s'))->count(),
            'failed_24h' => DB::table('activity_logs')->whereIn('event', ['login_failed', 'otp_failed', 'password_reset_otp_failed'])->where('created_at', '>=', now()->subDay())->count(),
        ];

        $users = null;
        $recentLogins = [];
        $failed7d = [];
        $logs = null;

        if ($tab === 'users') {
            $lastLogin = DB::table('activity_logs')
                ->selectRaw('MAX(created_at)')
                ->whereColumn('activity_logs.user_id', 'users.id')
                ->where('event', 'login_success');

            $query = User::query()->select('users.*')->selectSub($lastLogin, 'last_login_at');
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            }
            $users = $query->orderByRaw('last_login_at IS NULL')->orderByDesc('last_login_at')->orderBy('name')
                ->paginate(15)->appends($request->query());

            $ids = $users->pluck('id')->all();
            foreach ($ids as $id) {
                $recentLogins[$id] = DB::table('activity_logs')
                    ->where('user_id', $id)->where('event', 'login_success')
                    ->orderByDesc('id')->limit(5)->get();
            }
            if ($ids) {
                $failed7d = DB::table('activity_logs')
                    ->whereIn('user_id', $ids)
                    ->whereIn('event', ['login_failed', 'otp_failed'])
                    ->where('created_at', '>=', now()->subDays(7))
                    ->selectRaw('user_id, COUNT(*) AS c')->groupBy('user_id')->pluck('c', 'user_id')->all();
            }
        } else {
            $query = DB::table('activity_logs')
                ->leftJoin('users', 'users.id', '=', 'activity_logs.user_id')
                ->select('activity_logs.*', 'users.email as user_email', 'users.User_rank_id as user_rank');
            if ($event !== '' && isset(ActivityLog::EVENTS[$event])) {
                $query->where('activity_logs.event', $event);
            }
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('activity_logs.actor_name', 'like', "%{$q}%")
                        ->orWhere('users.email', 'like', "%{$q}%")
                        ->orWhere('activity_logs.description', 'like', "%{$q}%")
                        ->orWhere('activity_logs.ip', 'like', "%{$q}%");
                });
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $query->where('activity_logs.created_at', '>=', \Illuminate\Support\Carbon::parse($from . ' 00:00:00', 'Asia/Bangkok')->setTimezone('UTC')->format('Y-m-d H:i:s'));
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $query->where('activity_logs.created_at', '<=', \Illuminate\Support\Carbon::parse($to . ' 23:59:59', 'Asia/Bangkok')->setTimezone('UTC')->format('Y-m-d H:i:s'));
            }
            $logs = $query->orderByDesc('activity_logs.id')->paginate(25)->appends($request->query());
        }

        return compact('tab', 'q', 'event', 'from', 'to', 'stats', 'users', 'recentLogins', 'failed7d', 'logs');
    }
}
