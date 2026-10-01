<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
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
        view()->composer('layouts.admin', function ($view) {
            // These badge counts render on every admin page, including the
            // page a user lands on right after logging in. They don't need
            // to be second-accurate, so cache them briefly to avoid running
            // COUNT queries on every single request.
            $pendingUserCount = Cache::remember('badge.pending_user_count', 30, function () {
                return \App\Models\User::where('is_approved', 0)->count();
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
                $kidneyQuery = \App\Models\KidneyAssessment::where('is_read', 0);
                $saltQuery = \App\Models\SaltAssessment::where('is_read', 0);

                if ($currentUser->User_rank_id != 1) {
                    $agencyUserIds = self::badgeAgencyUserIds($currentUser);
                    $kidneyQuery->whereIn('user_id', $agencyUserIds);
                    $saltQuery->whereIn('user_id', $agencyUserIds);
                }

                $unreadKidneyCount = $kidneyQuery->count();
                $unreadSaltCount = $saltQuery->count();
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
