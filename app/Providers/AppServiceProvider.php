<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // บันทึกการใช้งานระบบ: เข้า/ออกจากระบบ (ครอบคลุมทุกทางที่เรียก Auth::login / Auth::logout)
        Event::listen(Login::class, function (Login $event) {
            \App\Support\ActivityLog::record('login_success', 'เข้าสู่ระบบสำเร็จ', $event->user, ['remember' => (bool) $event->remember]);

            // เข้าสู่ระบบครั้งแรก (มีบันทึก login_success ของผู้ใช้นี้เพียง 1 รายการ = ครั้งที่เพิ่งบันทึก)
            // -> ตั้งธงให้หน้าแอดมินแสดง pop up ต้อนรับครั้งเดียว (layouts.admin ดึงธงนี้ออกทันทีที่แสดง)
            try {
                if (\Illuminate\Support\Facades\DB::table('activity_logs')
                    ->where('user_id', $event->user->id)->where('event', 'login_success')->count() === 1) {
                    session(['show_welcome' => true]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                \App\Support\ActivityLog::record('logout', 'ออกจากระบบ', $event->user);
            }
        });

        // (เฉพาะ APP_DEBUG) วัดเวลา/จำนวน query ต่อหน้า -> header Server-Timing
        if (config('app.debug')) {
            $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\ServerTiming::class);
        }

        view()->composer('layouts.admin', function ($view) {
            // These badge counts render on every admin page, including the
            // page a user lands on right after logging in. They don't need
            // to be second-accurate, so cache them briefly to avoid running
            // COUNT queries on every single request.
            $pendingUserCount = Cache::remember('badge.pending_user_count', 30, function () {
                return \App\Models\User::where('is_approved', 0)->withoutImportPlaceholders()->count();
            });

            $currentUser = auth()->user();
            $unreadKidneyCount = 0;
            $unreadSaltCount = 0;

            if ($currentUser) {
                // The unread counts must reflect only what THIS user can actually
                // see when they click through to the list (same data-isolation
                // rules as saltAssessmentList()/kidneyDHBList() in
                // AdminController: rank 1 sees everything, rank 2 is scoped to
                // their province, rank 3+ to their district/agency). Otherwise a
                // province/district-level user sees a badge count that includes
                // other agencies' unread items they can't actually open, which
                // never matches what they find on the list page.
                //
                // NOT cached (unlike pendingUserCount above): these need to drop
                // the instant a user opens an item and it gets marked as read
                // (AdminController::showSaltAssessment / showKidneyDHB). Since
                // is_read is a single shared flag on the record rather than
                // per-viewer, one person reading an item can change what several
                // other users' badges should show too — there's no single cache
                // key that could be safely invalidated at that moment. Running two
                // plain COUNT queries per admin page load is cheap enough here
                // that it isn't worth trading accuracy for it.
                // Badges are shown only to rank-1 users (SuperAdmin / สคร.),
                // so skip the COUNT queries entirely for everyone else.
                if ($currentUser->User_rank_id == 1) {
                    $unreadKidneyCount = \App\Models\KidneyAssessment::where('is_read', 0)->count();
                    $unreadSaltCount   = \App\Models\SaltAssessment::where('is_read', 0)->count();
                }
            }

            $view->with([
                'pendingUserCount' => $pendingUserCount,
                'unreadKidneyCount' => $unreadKidneyCount,
                'unreadSaltCount' => $unreadSaltCount,
            ]);
        });
    }

    /**
     * Same agency-grouping rule used by AdminController::getAgencyUserIds():
     * rank 2 (สสจ.) groups by province, rank 3+ groups by district. Duplicated
     * here (rather than calling the controller) since a service provider boots
     * before controllers are resolved and this is the only other place that
     * needs it.
     */
    private static function badgeAgencyUsersQuery($user)
    {
        $query = \App\Models\User::query();

        if ($user->User_rank_id == 2) {
            $query->where('Province_id', $user->Province_id);
        } elseif ($user->User_rank_id >= 3) {
            $query->where('District_id', $user->District_id);
        } else {
            $query->where('id', $user->id);
        }

        return $query;
    }

    private static function badgeAgencyUserIds($user): array
    {
        if (!$user) {
            return [];
        }

        $query = \App\Models\User::query();

        if ($user->User_rank_id == 2) {
            $query->where('Province_id', $user->Province_id);
        } elseif ($user->User_rank_id >= 3) {
            $query->where('District_id', $user->District_id);
        } else {
            return [$user->id];
        }

        return $query->pluck('id')->toArray();
    }
}
