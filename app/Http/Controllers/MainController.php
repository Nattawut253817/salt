<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Province;
use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use App\Models\SaltAssessment;
use App\Models\User;
use App\Models\Hi;
use App\Models\District;
use App\Models\ReducedSodiumMenu;
use App\Models\KidneyAssessment;
use App\Models\FiscalYear;
use App\Models\AwarenessPassSetting;
use App\Services\AwarenessPassResolver;
use App\Services\AwarenessScoreCalculator;
use App\Support\DbCompat;

class MainController extends Controller
{
    /**
     * Display the Kidney DHB presentation/report page.
     */
    public function kidneyDHBReport(Request $request)
    {
        $targetProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];
        $query = KidneyAssessment::with(['user.province', 'user.district']);

        // Filter Options - real data-years, plus any year an admin has
        // enabled/hidden for this module via "จัดการปีงบประมาณ".
        $years = FiscalYear::selectableYearsFor('kidney', KidneyAssessment::distinct()->pluck('fiscal_year'));
        $provinces = Province::whereIn('province_name', $targetProvinces)->get();

        // has() (not filled()) - so a user who explicitly picks "ทั้งหมด"
        // (value="") still gets every fiscal year combined, same fix
        // already applied on /awareness for this exact bug. filled()
        // treats an empty string as "nothing chosen", so picking
        // "ทั้งหมด" silently fell back to the single latest year below -
        // same as a bare visit - while every OTHER filter on this page
        // (จังหวัด/อำเภอ/ไตรมาส/ประเภทหน่วยงาน) already treats "ทั้งหมด" as a
        // real "no filter" choice. $fiscalYear === '' now means exactly
        // that: every query below that used to unconditionally scope to
        // this single value now skips that filter when it's empty.
        $fiscalYear = $request->has('fiscal_year') ? (string) $request->input('fiscal_year') : (string) $years->first();
        $selectedProvince = $request->get('province', 'ทั้งหมด');
        $selectedDistrict = $request->get('district', 'ทั้งหมด');
        $selectedRank = $request->get('rank', 'ทั้งหมด');
        // Free-text search box on the agency progress table (ใหม่).
        $searchQuery = trim((string) $request->get('search', ''));

        $districts = collect();
        if ($selectedProvince && $selectedProvince != 'ทั้งหมด') {
            $province = Province::where('province_name', $selectedProvince)->first();
            if ($province) {
                // Show every district in the selected province, not just
                // ones with reported data for this fiscal year - a
                // district shouldn't disappear from the filter just
                // because nobody has submitted an assessment for it yet.
                $districts = District::where('province_id', $province->province_id)
                    ->orderBy('district_name')
                    ->get();
            }
        }

        // Validation: If province changes, and current district doesn't belong to it, reset district
        if ($selectedProvince != 'ทั้งหมด' && $selectedDistrict != 'ทั้งหมด') {
            $provObj = Province::where('province_name', $selectedProvince)->first();
            if ($provObj) {
                $isValidDistrict = District::where('province_id', $provObj->province_id)
                    ->where('district_name', $selectedDistrict)
                    ->exists();
                if (!$isValidDistrict) {
                    $selectedDistrict = 'ทั้งหมด';
                    $request->merge(['district' => 'ทั้งหมด']);
                }
            }
        }

        // --- OVERVIEW QUERY (Filtered by Province/District for Charts) ---
        $overviewQuery = KidneyAssessment::with(['user.province', 'user.district']);
        // "ทั้งหมด" ($fiscalYear === '') -> every fiscal year combined,
        // not scoped to one - see the note above where $fiscalYear is set.
        if ($fiscalYear !== '') {
            $overviewQuery->where('fiscal_year', $fiscalYear);
        }

        if ($selectedProvince && $selectedProvince != 'ทั้งหมด') {
            $overviewQuery->whereHas('user.province', function ($q) use ($selectedProvince) {
                $q->where('province_name', $selectedProvince);
            });
        }
        if ($selectedDistrict && $selectedDistrict != 'ทั้งหมด') {
            $overviewQuery->whereHas('user.district', function ($q) use ($selectedDistrict) {
                $q->where('district_name', $selectedDistrict);
            });
        }
        if ($selectedRank && $selectedRank != 'ทั้งหมด') {
            $overviewQuery->whereHas('user', function ($q) use ($selectedRank) {
                $q->where('User_rank_id', $selectedRank);
            });
        }
        $yearlyAssessments = $overviewQuery->get();


        $selectedQuarter = $request->get('quarter', 'ทั้งหมด');
        // Normalize: if a numeric quarter is selected, cast to int for consistent comparison
        $quarterInt = ($request->filled('quarter') && $request->quarter !== 'ทั้งหมด') ? (int) $request->quarter : null;

        // --- Region-wide "8 milestones" overview stats (for the new
        // summary card) --- computed fresh, inside this already-correct
        // per-province/per-area loop, instead of reusing the separately
        // computed $milestoneCounts/$milestonePercents block further down:
        // that block (1) counts ALL registered users regardless of
        // whether they submitted anything for the current filters, and
        // (2) groups by user_id alone, so one login covering several
        // operating areas is only counted once - both wrong against the
        // "(user_id|operating_area)" counting unit used everywhere else
        // on this page (map/stacked chart/agency table). Building it here
        // reuses the exact same scoping/grouping as $mapData and
        // $stackedChartData, so it can never show a different, confusing
        // total.
        $overviewMilestoneCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, '8' => 0];
        $overviewTotalAreas = 0;
        $overviewFullyCompletedCount = 0;

        foreach ($targetProvinces as $provName) {
            $provYearly = $yearlyAssessments->filter(function ($a) use ($provName, $quarterInt, $selectedDistrict) {
                // Must belong to this province
                if (!($a->user && $a->user->province && $a->user->province->province_name == $provName)) {
                    return false;
                }
                // Must belong to this district (if selected)
                if ($selectedDistrict && $selectedDistrict !== 'ทั้งหมด') {
                    if (!($a->user->district && $a->user->district->district_name == $selectedDistrict)) {
                        return false;
                    }
                }
                // Quarter filter: if specific quarter selected, only include that quarter
                if ($quarterInt !== null) {
                    if ($a->quarter != $quarterInt)
                        return false;
                }
                return true;
            });

            // Count by "พื้นที่ดำเนินการ" (operating_area), not by user_id -
            // one login (user_id) can report for several operating areas
            // side by side (e.g. more than one รพ.สต. reporting under the
            // same อำเภอ account - see the ACTIVE ONLY / operating_area
            // handling in the agency table below), and each area's own
            // progress should count as its own unit here too, matching
            // both the map's "จำนวนพื้นที่ดำเนินงาน" label and the stacked
            // chart's per-area completeness. A blank/null operating_area
            // (legacy rows from before this column existed) still counts
            // as exactly one area for that user, same normalization as
            // the agency table's $areas collection further down.
            $agenciesInProv = $provYearly->groupBy(function ($a) {
                $area = ($a->operating_area !== null && $a->operating_area !== '') ? $a->operating_area : '';
                return $a->user_id . '|' . $area;
            });
            $yearlyCompletedCount = 0;
            $yearlyTotalAgencies = $agenciesInProv->count();

            foreach ($agenciesInProv as $areaKey => $userAssessments) {
                $milestoneQuarters = [];
                for ($i = 1; $i <= 7; $i++) {
                    $field = "category_$i";
                    if ($userAssessments->contains(fn($a) => !empty($a->$field))) {
                        $milestoneQuarters[] = $i;
                    }
                }
                // Item 8 (8.1/8.2/8.3) counts as ONE combined milestone,
                // satisfied by any single one of its three sub-questions -
                // per the user's explicit rule ("8.1 8.2 8.3 ทำข้อได้ข้อ
                // หนึ่งถือว่าทำครบ"), unlike items 1-7 which each need
                // their own field filled individually. Previously this
                // required BOTH 8_1 AND 8_2 (never satisfiable by just
                // one) and never even looked at 8_3 at all.
                if (
                    $userAssessments->contains(fn($a) => !empty($a->category_8_1))
                    || $userAssessments->contains(fn($a) => !empty($a->category_8_2))
                    || $userAssessments->contains(fn($a) => !empty($a->category_8_3))
                ) {
                    $milestoneQuarters[] = '8';
                }

                // Done = all of 1-7, plus item 8 (any one of 8.1/8.2/8.3) = 8 total.
                if (count($milestoneQuarters) >= 8) {
                    $yearlyCompletedCount++;
                }

                // Tally into the region-wide overview stats - same area
                // (user_id|operating_area) unit, same milestone list, as
                // computed just above for this one area.
                foreach ($milestoneQuarters as $m) {
                    $overviewMilestoneCounts[$m]++;
                }
                $overviewTotalAreas++;
                if (count($milestoneQuarters) >= 8) {
                    $overviewFullyCompletedCount++;
                }
            }

            $stackedChartData[$provName] = [
                'completed' => $yearlyCompletedCount,
                'incomplete' => max(0, $yearlyTotalAgencies - $yearlyCompletedCount)
            ];
            $mapData[$provName] = $yearlyTotalAgencies;

        }

        // Turn the raw tallies above into percentages for the overview
        // card, plus one headline "top province" pick reusing the
        // already-computed, already-correct $stackedChartData (so this
        // never disagrees with the map/stacked-chart numbers next to it).
        $overviewMilestonePercents = [];
        foreach ($overviewMilestoneCounts as $label => $count) {
            $overviewMilestonePercents[$label] = ($overviewTotalAreas > 0) ? round(($count / $overviewTotalAreas) * 100) : 0;
        }
        $overviewFullyCompletedPercent = ($overviewTotalAreas > 0) ? round(($overviewFullyCompletedCount / $overviewTotalAreas) * 100) : 0;

        $overviewTopProvince = null;
        $overviewTopProvinceRate = -1;
        foreach ($stackedChartData as $provName => $d) {
            $provTotal = $d['completed'] + $d['incomplete'];
            if ($provTotal > 0) {
                $rate = $d['completed'] / $provTotal;
                if ($rate > $overviewTopProvinceRate) {
                    $overviewTopProvinceRate = $rate;
                    $overviewTopProvince = $provName;
                }
            }
        }
        $overviewTopProvincePercent = $overviewTopProvinceRate >= 0 ? round($overviewTopProvinceRate * 100) : 0;

        // --- Agency Summary Table Data ---
        // --- Agency Summary Table Data ---
        // --- Agency Summary Table Data ---
        $agencyQuery = User::with(['subdistrictHospital', 'district', 'hospital', 'province'])->whereHas('province', function ($q) use ($targetProvinces, $selectedProvince) {
            if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
                $q->where('province_name', $selectedProvince);
            } else {
                $q->whereIn('province_name', $targetProvinces);
            }
        });

        if ($selectedDistrict && $selectedDistrict != 'ทั้งหมด') {
            $agencyQuery->whereHas('district', function ($q) use ($selectedDistrict) {
                $q->where('district_name', $selectedDistrict);
            });
        }

        if ($selectedRank && $selectedRank != 'ทั้งหมด') {
            $agencyQuery->where('User_rank_id', $selectedRank);
        }

        // --- ACTIVE ONLY FILTER ---
        // Only show agencies that have at least one KidneyAssessment matching the criteria
        $agencyQuery->whereHas('kidneyAssessments', function ($q) use ($fiscalYear, $quarterInt) {
            if ($fiscalYear !== '') {
                $q->where('fiscal_year', $fiscalYear);
            }
            if ($quarterInt !== null) {
                $q->where('quarter', $quarterInt);
            }
        });

        // Free-text search box (ใหม่) - matches the agency's *displayed*
        // name (province/district/hospital label), not the raw Con_name
        // column, since that's what the user actually sees and searches
        // for. This needs the collection resolved up front instead of the
        // old SQL-level ->paginate(), so the display name can be computed
        // and filtered on before slicing the page.
        $agencyCollection = $agencyQuery->get()->map(function ($user) {
            $user->display_name = ($user->User_rank_id == 2 && $user->province)
                ? 'สํานักงานสาธารณสุขจังหวัด' . $user->province->province_name
                : (($user->User_rank_id == 3 && $user->district)
                    ? 'สํานักงานสาธารณสุขอำเภอ' . $user->district->district_name
                    : (($user->User_rank_id == 4 && $user->subdistrictHospital)
                        ? $user->subdistrictHospital->hospital_name
                        : (($user->User_rank_id == 5 && $user->hospital)
                            ? $user->hospital->hos_name
                            : ($user->Con_name ?: $user->name ?: 'N/A'))));
            return $user;
        })->sortBy('display_name')->values();

        // A single agency (user_id) can now have saved several operating
        // areas side by side (e.g. more than one รพ.สต. reporting under the
        // same อำเภอ login) - each area keeps its own KidneyAssessment rows.
        // Expand every agency into one row per (agency, operating_area) pair
        // found within the current fiscal_year/quarter context, instead of
        // one row per agency, so different areas' progress is never blended
        // together into a single row's checkmarks.
        // Operating-area list per agency used to run one KidneyAssessment
        // query PER AGENCY here - every agency across every province, not
        // just the current page, since rows are expanded/sorted/searched
        // before pagination happens further down. Fetched once instead:
        // every relevant user_id's operating_area rows in a single query,
        // grouped by user_id in memory - same per-agency area list as
        // before (the SQL-level ->distinct() was only ever a minor fetch
        // optimization; the ->unique() a few lines below already
        // guarantees the final list has no duplicates either way).
        $agencyUserIds = $agencyCollection->pluck('id');
        $areasBaseQuery = KidneyAssessment::whereIn('user_id', $agencyUserIds);
        if ($fiscalYear !== '') {
            $areasBaseQuery->where('fiscal_year', $fiscalYear);
        }
        if ($quarterInt !== null) {
            $areasBaseQuery->where('quarter', $quarterInt);
        }
        $areasByUserId = $areasBaseQuery->get(['user_id', 'operating_area'])->groupBy('user_id');

        $rowEntries = collect();
        $agencyColorMap = []; // user_id -> color index, assigned in display order so every area belonging to the same agency shares one badge color
        foreach ($agencyCollection as $user) {
            $areas = ($areasByUserId->get($user->id) ?? collect())
                ->pluck('operating_area')
                ->map(fn($a) => $a === '' ? null : $a)
                ->unique()
                ->sort(fn($a, $b) => ($a ?? '') <=> ($b ?? ''))
                ->values();

            if ($areas->isEmpty()) {
                $areas = collect([null]);
            }

            if (!isset($agencyColorMap[$user->id])) {
                $agencyColorMap[$user->id] = count($agencyColorMap);
            }
            $agencyColor = $agencyColorMap[$user->id];

            // Same color index for every area of this agency (badge color
            // identifies the AGENCY, not the individual area), so a district
            // with several areas is easy to spot as one group at a glance.
            foreach ($areas as $area) {
                $rowEntries->push([
                    'user' => $user,
                    'display_name' => $user->display_name,
                    'operating_area' => $area,
                    'operating_area_color' => $agencyColor,
                ]);
            }
        }

        // Sort by agency name first, then by area name within the same
        // agency (blank/no-specific-area sorts first).
        $rowEntries = $rowEntries->sortBy(
            fn($row) => $row['display_name'] . '|' . ($row['operating_area'] ?? '')
        )->values();

        if ($searchQuery !== '') {
            $rowEntries = $rowEntries->filter(
                fn($row) => mb_stripos($row['display_name'], $searchQuery) !== false
            )->values();
        }

        $perPage = 10;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page') ?: 1;
        $paginatedAgencies = new \Illuminate\Pagination\LengthAwarePaginator(
            $rowEntries->slice(($currentPage - 1) * $perPage, $perPage)->values(),
            $rowEntries->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );
        $paginatedAgencies->appends($request->query());

        $summaryTableData = $paginatedAgencies->getCollection()->map(function ($row) use ($fiscalYear, $quarterInt) {
            $user = $row['user'];
            $area = $row['operating_area'];

            $allAssessmentsQuery = KidneyAssessment::where('user_id', $user->id)
                ->where('operating_area', $area);
            if ($fiscalYear !== '') {
                $allAssessmentsQuery->where('fiscal_year', $fiscalYear);
            }
            $allAssessments = $allAssessmentsQuery->get();

            // Filter context for milestones based on selected quarter
            if ($quarterInt !== null) {
                $contextAssessments = $allAssessments->filter(fn($a) => $a->quarter == $quarterInt);
            } else {
                // "ทั้งหมด" -> Cumulative, include all quarters
                $contextAssessments = $allAssessments;
            }

            $milestones = [];
            $allFields = [
                1 => 'category_1',
                2 => 'category_2',
                3 => 'category_3',
                4 => 'category_4',
                5 => 'category_5',
                6 => 'category_6',
                7 => 'category_7',
                '8.1' => 'category_8_1',
                '8.2' => 'category_8_2',
                '8.3' => 'category_8_3'
            ];

            foreach ($allFields as $label => $field) {
                // Milestone is the FIRST quarter in the context that has this field filled
                $found = $contextAssessments->filter(fn($a) => !empty($a->$field))->sortBy('quarter')->first();
                $fileField = $field . '_file';
                $milestones[$label] = [
                    'q' => $found ? $found->quarter : null,
                    'has_file' => $found ? !empty($found->$fileField) : false
                ];
            }

            // Items 1-7 each count as their own step, but 8.1/8.2/8.3 count
            // as ONE combined step ("ข้อ 8 ใหญ่") - done as soon as any ONE
            // of the three is filled, per the same rule already applied to
            // the map/stacked-chart/quarter-status aggregates above
            // ("8.1 8.2 8.3 ทำข้อได้ข้อหนึ่งถือว่าทำครบ"). The 3 dots in the
            // table/detail panel stay separate and each still shows its OWN
            // true fill status (per the user's explicit choice to keep them
            // that way) - only this step-count summary treats them as 1 of
            // 8 total, not 3 of 10, so it agrees with the rest of the page.
            $coreStepsDone = collect([1, 2, 3, 4, 5, 6, 7])
                ->filter(fn($cat) => $milestones[$cat]['q'] !== null)
                ->count();
            $item8Done = collect(['8.1', '8.2', '8.3'])
                ->contains(fn($cat) => $milestones[$cat]['q'] !== null);
            $stepsDone = $coreStepsDone + ($item8Done ? 1 : 0);

            $latest = $contextAssessments->sortByDesc('quarter')->first();

            return [
                'agency' => $row['display_name'],
                'operating_area' => $area,
                'operating_area_color' => $row['operating_area_color'] ?? 0,
                // A little extra identifying detail for the plain
                // operating-area summary table (ตารางข้อมูลพื้นที่ดำเนินงาน
                // below the map) - not shown anywhere in the big
                // per-indicator matrix table, so this is the only place
                // an admin can see which จังหวัด/อำเภอ an area belongs to.
                'province_name' => $user->province->province_name ?? null,
                'district_name' => $user->district->district_name ?? null,
                'milestones' => $milestones,
                'cat_statuses' => collect($milestones)->map(fn($m) => $m['q'] !== null), // For simplicity if needed
                'steps_done' => $stepsDone,
                'total_steps' => 8,
                'latest_id' => $latest ? $latest->id : null,
                'latest_quarter' => $latest ? $latest->quarter : null,
                'fiscal_year' => $fiscalYear,
                'q_details' => collect([1, 2, 3, 4])->mapWithKeys(function ($q) use ($allAssessments, $milestones) {
                    $assessment = $allAssessments->where('quarter', $q)->first();

                    if (!$assessment) {
                        return [$q => ['submitted' => false, 'cats' => []]];
                    }

                    $c = [];
                    foreach ([1, 2, 3, 4, 5, 6, 7] as $i) {
                        $f = "category_$i";
                        $ff = $f . "_file";
                        // Use Milestone logic to determine if it was "Done" in THIS quarter
                        $isMilestone = isset($milestones[$i]) && $milestones[$i]['q'] == $q;
                        // The narrative answer this agency actually typed for
                        // this category, in THIS quarter's own submission -
                        // independent of the milestone flag above, so it still
                        // shows even for a quarter that isn't the "first done"
                        // one (e.g. the text was revised in a later quarter).
                        $rawText = trim((string) ($assessment->$f ?? ''));
                        $c[$i] = [
                            'done' => $isMilestone,
                            'has_file' => $isMilestone && !empty($assessment->$ff),
                            'text' => $rawText !== '' ? $rawText : null,
                        ];
                    }

                    // Category 8 mapping
                    $text81 = trim((string) ($assessment->category_8_1 ?? ''));
                    $text82 = trim((string) ($assessment->category_8_2 ?? ''));
                    $text83 = trim((string) ($assessment->category_8_3 ?? ''));
                    $c['8_1'] = ['done' => (isset($milestones['8.1']) && $milestones['8.1']['q'] == $q), 'has_file' => (isset($milestones['8.1']) && $milestones['8.1']['q'] == $q && !empty($assessment->category_8_1_file)), 'text' => $text81 !== '' ? $text81 : null];
                    $c['8_2'] = ['done' => (isset($milestones['8.2']) && $milestones['8.2']['q'] == $q), 'has_file' => (isset($milestones['8.2']) && $milestones['8.2']['q'] == $q && !empty($assessment->category_8_2_file)), 'text' => $text82 !== '' ? $text82 : null];
                    $c['8_3'] = ['done' => (isset($milestones['8.3']) && $milestones['8.3']['q'] == $q), 'has_file' => (isset($milestones['8.3']) && $milestones['8.3']['q'] == $q && !empty($assessment->category_8_3_file)), 'text' => $text83 !== '' ? $text83 : null];

                    // The agency's own free-text "problems/obstacles" and
                    // "recommendations" for this quarter, so the report can
                    // surface them alongside the 10 indicators.
                    $problemsText = trim((string) ($assessment->problems_obstacles ?? ''));
                    $recommendationsText = trim((string) ($assessment->recommendations_opportunities ?? ''));

                    return [
                        $q => [
                            'submitted' => true,
                            'id' => $assessment->id,
                            'cats' => $c,
                            'problems' => $problemsText !== '' ? $problemsText : null,
                            'recommendations' => $recommendationsText !== '' ? $recommendationsText : null,
                        ]
                    ];
                })
            ];
        });

        $paginatedAgencies->setCollection($summaryTableData);

        // --- Regional Achievement Stats for Infographic ---
        // We want to know: % of agencies in the region that completed EACH of the 10 milestones
        $milestoneCounts = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            '8.1' => 0,
            '8.2' => 0,
            '8.3' => 0
        ];

        $targetUserQuery = User::whereHas('province', function ($q) use ($targetProvinces, $selectedProvince) {
            if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
                $q->where('province_name', $selectedProvince);
            } else {
                $q->whereIn('province_name', $targetProvinces);
            }
        });
        if ($selectedDistrict && $selectedDistrict !== 'ทั้งหมด') {
            $targetUserQuery->whereHas('district', function ($q) use ($selectedDistrict) {
                $q->where('district_name', $selectedDistrict);
            });
        }
        $targetUserIds = $targetUserQuery->pluck('id');

        $allRegionAssessmentsQuery = KidneyAssessment::whereIn('user_id', $targetUserIds);
        if ($fiscalYear !== '') {
            $allRegionAssessmentsQuery->where('fiscal_year', $fiscalYear);
        }
        $allRegionAssessments = $allRegionAssessmentsQuery->get();

        // Filter by quarter if needed — use $quarterInt for consistency
        if ($quarterInt !== null) {
            $regionContext = $allRegionAssessments->filter(fn($a) => $a->quarter == $quarterInt);
        } else {
            $regionContext = $allRegionAssessments;
        }

        $fields = [
            1 => 'category_1',
            2 => 'category_2',
            3 => 'category_3',
            4 => 'category_4',
            5 => 'category_5',
            6 => 'category_6',
            7 => 'category_7',
            '8.1' => 'category_8_1',
            '8.2' => 'category_8_2',
            '8.3' => 'category_8_3'
        ];

        $agenciesWithContext = $regionContext->groupBy('user_id');
        foreach ($agenciesWithContext as $userId => $userAssessments) {
            foreach ($fields as $label => $field) {
                if ($userAssessments->contains(fn($a) => !empty($a->$field))) {
                    $milestoneCounts[$label]++;
                }
            }
        }

        $totalAgenciesInRegion = count($targetUserIds);
        $milestonePercents = [];
        foreach ($milestoneCounts as $label => $count) {
            $milestonePercents[$label] = ($totalAgenciesInRegion > 0) ? round(($count / $totalAgenciesInRegion) * 100) : 0;
        }

        // Keep qStats for compatibility if needed elsewhere, but qStats[q]['percent'] is now for general participation
        $qStats = [1 => ['percent' => 0], 2 => ['percent' => 0], 3 => ['percent' => 0], 4 => ['percent' => 0]];
        foreach ([1, 2, 3, 4] as $q) {
            $count = $allRegionAssessments->where('quarter', $q)->unique('user_id')->count();
            $qStats[$q]['percent'] = ($totalAgenciesInRegion > 0) ? round(($count / $totalAgenciesInRegion) * 100) : 0;
        }

        return view('pages.kidney-dhb-report', compact(
            'qStats',
            'milestonePercents',
            'overviewMilestonePercents',
            'overviewTotalAreas',
            'overviewFullyCompletedPercent',
            'overviewTopProvince',
            'overviewTopProvincePercent',
            'paginatedAgencies',
            'years',
            'provinces',
            'districts',
            'mapData',
            'stackedChartData',
            'fiscalYear',
            'selectedRank',
            'searchQuery'
        ));
    }
    public function index(Request $request)
    {
        $targetProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];

        // Every card this page's ปีงบประมาณ filter actually drives
        // (awareness map, respondent count, behavior metrics, sodium
        // before/after, product count) reads from SodiumSurvey - the
        // "การประเมินความตระหนักรู้" module - never from salt_assessment
        // (that's a separate module/table entirely, used by the
        // consumption-report page's own, independent year filter). This
        // dropdown previously merged in salt_assessment's own selectable
        // years too, so a year enabled only for THAT module (e.g. 2570)
        // could appear here and get selected by default even though
        // "ความตระหนักรู้" has no criteria configured for it - "จัดการ
        // ปีงบประมาณ" > ความตระหนักรู้ is the actual source of truth for
        // which years belong on this filter. Computed up front (previously
        // only built after the whole dashboard, purely for the dropdown)
        // so its newest entry can also be used as this filter's default
        // below.
        $awarenessYears = SodiumSurvey::select('fiscal_year')->distinct()->pluck('fiscal_year');
        $years = FiscalYear::selectableYearsFor('awareness', $awarenessYears)->values();

        // Global Filters
        //
        // Defaults to the latest selectable ปีงบประมาณ (was hardcoded to
        // 2568, which quietly went stale the moment a newer year - e.g.
        // 2570 - became selectable: a bare first visit kept showing
        // year-old data instead of the dropdown's own top entry). This
        // filter now has a "ทั้งหมด" (all years, value="") option, same as
        // /awareness - has() (not filled()) is checked here so picking
        // "ทั้งหมด" is distinguished from "nothing picked yet": filled()
        // treats an empty string the same as absent, which would have
        // silently fallen back to the default year instead of meaning
        // "every year".
        $fiscalYear = $request->has('fiscal_year') ? (string) $request->input('fiscal_year') : (string) $years->first();
        $selectedProvince = $request->get('province', 'all');

        $activeFiscalYear = $fiscalYear;

        // Human-readable stand-in for $activeFiscalYear wherever the view
        // displays the year as TEXT (never as a query value) - "ทั้งหมด"
        // instead of a blank string when every year is in view.
        $fiscalYearLabel = $activeFiscalYear !== '' ? $activeFiscalYear : 'ทั้งหมด';

        // Every chart/card below is a pure function of just the year +
        // province filters (no writes happen on this page), and one of
        // them - the score-method awareness pass/fail count - does a
        // chunked, row-by-row JSON decode that scales with how many
        // respondents that year/province combo has (see
        // AwarenessPassResolver::passFailCountsByColumn()). Caching the
        // whole bundle for a while, keyed by the 2 filters, means switching
        // back to a year/province someone else (or the same admin) already
        // viewed in that window returns instantly instead of re-running
        // every query - the same pattern already used for the same reason
        // on /awareness (MainController::awareness()). 10 minutes (was 3):
        // for a score-configured fiscal year with several thousand
        // respondents, the first (cache-miss) load still costs real CPU
        // time decoding and scoring each respondent row one by one - no
        // amount of query tuning removes that, so a longer window before
        // it has to happen again matters more here than staying maximally
        // fresh on data that only changes a handful of times a day anyway.
        // "v2" bumps past any 10-minute cache entry written by the previous
        // version of this closure (before the awarenessOverall* KPI keys
        // below existed) - without it, a still-warm cache entry would get
        // extract()ed without those keys and the compact() further down
        // would fail on an undefined variable.
        $homeDashboardCacheKey = 'home_dashboard_v2:' . md5(json_encode([
            'fiscal_year' => $activeFiscalYear,
            'province' => $selectedProvince,
        ]));

        $homeDashboardData = Cache::remember($homeDashboardCacheKey, now()->addMinutes(10), function () use ($activeFiscalYear, $selectedProvince, $targetProvinces, $awarenessYears) {

        // 2. Map & Chart Data
        //
        // "aware/pass" is decided per fiscal year via AwarenessPassResolver,
        // which itself reads that year's own chosen method from "ตั้งค่า
        // เกณฑ์ความตระหนักรู้" > "วิธีตั้งเกณฑ์": either the original 2-specific-
        // questions rule (fast SQL aggregate) or, starting FY2569, the
        // scoring rubric's own pass/fail (computed row-by-row in PHP - see
        // the resolver for why). $awarenessCriteriaPending drives the "รอ
        // เกณฑ์การประเมิน" overlay on the map below.
        //
        // "ทั้งหมด" ($activeFiscalYear === '') runs passFailCountsByColumn()
        // once per distinct fiscal year with data ($awarenessYears, from
        // near the top of this method) against the SAME $awarenessBaseQuery
        // and sums the per-province counts - exactly the reuse its own
        // docblock describes ("$baseQuery should already carry every OTHER
        // filter ... but NOT a fiscal_year condition ... so the same
        // $baseQuery can be reused across several years"). A year with
        // nothing configured yet simply contributes nothing (its own call
        // returns []), the same "pending" treatment a single unconfigured
        // year already got. The overlay now only fires when NOT ONE
        // fiscal year in scope has anything configured.
        $awarenessBaseQuery = SodiumSurvey::query();
        if ($selectedProvince !== 'all') {
            $awarenessBaseQuery->where('province_name', $selectedProvince);
        } else {
            $awarenessBaseQuery->whereIn('province_name', $targetProvinces);
        }

        $awarenessYearsForPass = $activeFiscalYear !== '' ? collect([$activeFiscalYear]) : $awarenessYears;

        $awarenessCombinedCounts = [];
        $awarenessConfiguredMethods = collect();
        foreach ($awarenessYearsForPass as $passYear) {
            if (!AwarenessPassResolver::isConfiguredFor($passYear)) {
                continue;
            }
            $awarenessConfiguredMethods->push(AwarenessPassResolver::methodFor($passYear));
            foreach (AwarenessPassResolver::passFailCountsByColumn($passYear, $awarenessBaseQuery, 'province_name') as $province => $counts) {
                $awarenessCombinedCounts[$province]['total'] = ($awarenessCombinedCounts[$province]['total'] ?? 0) + $counts['total'];
                $awarenessCombinedCounts[$province]['pass'] = ($awarenessCombinedCounts[$province]['pass'] ?? 0) + $counts['pass'];
            }
        }
        $awarenessCriteriaPending = $awarenessConfiguredMethods->isEmpty();

        $awarenessDataRaw = collect($awarenessCombinedCounts)
            ->map(fn ($counts, $province) => (object) [
                'province_name' => $province,
                'total_people' => $counts['total'],
                'passed_count' => $counts['pass'],
            ])
            ->values();

        // Overall KPI summary (จำนวนผู้เข้าร่วมทั้งหมด / ผ่านเกณฑ์ / ไม่ผ่านเกณฑ์ /
        // อัตราผ่านเกณฑ์) shown on the public home page - summed across
        // whichever provinces $awarenessDataRaw already covers above (the
        // selected province alone, or every target province for "ทุกจังหวัด"),
        // so this stays in sync with the same year/province filters as the
        // rest of this card without a second query. $awarenessMethod picks
        // the "เกณฑ์ผ่าน ≥ 19.2 / 32 คะแนน" vs. the older 2-question wording
        // in the view, mirroring admin/awareness/interpretation.blade.php.
        $awarenessOverallTotal = (int) $awarenessDataRaw->sum('total_people');
        $awarenessOverallPass = (int) $awarenessDataRaw->sum('passed_count');
        $awarenessOverallFail = $awarenessOverallTotal - $awarenessOverallPass;
        $awarenessOverallRate = $awarenessOverallTotal > 0 ? round($awarenessOverallPass / $awarenessOverallTotal * 100, 1) : 0;
        $awarenessMethod = ($activeFiscalYear !== '' || $awarenessConfiguredMethods->isEmpty())
            ? AwarenessPassResolver::methodFor($activeFiscalYear)
            : ($awarenessConfiguredMethods->unique()->count() === 1 ? $awarenessConfiguredMethods->first() : 'mixed');

        if ($selectedProvince !== 'all') {
            // Show ONLY the selected province
            $awarenessData = $awarenessDataRaw->where('province_name', $selectedProvince)->mapWithKeys(function ($item) {
                $total = (int) $item->total_people;
                $count = (int) $item->passed_count;
                $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                return [
                    $item->province_name => [
                        'name' => $item->province_name,
                        'value' => $count,
                        'total_people' => $total,
                        'passed_percentage' => $percentage,
                        'color' => $percentage >= 80 ? '#4dbd98' : '#eb7a72'
                    ]
                ];
            });
        } else {
            // Overview: Show all target provinces
            $awarenessData = collect($targetProvinces)->mapWithKeys(function ($province) use ($awarenessDataRaw) {
                $data = $awarenessDataRaw->firstWhere('province_name', $province);
                $total = $data ? (int) $data->total_people : 0;
                $count = $data ? (int) $data->passed_count : 0;
                $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                return [
                    $province => [
                        'name' => $province,
                        'value' => $count,
                        'total_people' => $total,
                        'passed_percentage' => $percentage,
                        'color' => $percentage >= 80 ? '#4dbd98' : '#eb7a72'
                    ]
                ];
            });
        }

        // 3. Sodium Comparison Chart Data (Reduced Sodium Menus)
        //
        // Always scoped to the selected ปีงบประมาณ ($activeFiscalYear), same
        // as every other card on this page - picking a specific province
        // used to switch this chart to a hardcoded [2567, 2568, 2569] year
        // trend instead, so changing the year filter had no visible effect
        // here at all whenever a province was selected (and would have
        // silently stopped including new years, e.g. 2570, without another
        // code change). Grouping by province (narrowed to just the
        // selected one, or every target province for the "ทุกจังหวัด"
        // overview) instead keeps this chart's scope identical to
        // awarenessData/awarenessMetrics above: one year, one or all 5
        // provinces.
        $sodiumCompareScopeProvinces = $selectedProvince !== 'all' ? [$selectedProvince] : $targetProvinces;
        $sodiumCompareQuery = ReducedSodiumMenu::query()
            ->whereIn('province', $sodiumCompareScopeProvinces);
        if ($activeFiscalYear !== '') {
            $sodiumCompareQuery->where('year', $activeFiscalYear);
        }
        $sodiumCompareDataRaw = $sodiumCompareQuery
            ->select(
                'province as label',
                \DB::raw('AVG(sodium_before) as avg_before'),
                \DB::raw('AVG(sodium_after) as avg_after')
            )
            ->groupBy('province')
            ->get();

        $sodiumCompareData = collect($sodiumCompareScopeProvinces)->map(function ($province) use ($sodiumCompareDataRaw) {
            $data = $sodiumCompareDataRaw->firstWhere('label', $province);
            return [
                'province' => $province,
                'avg_before' => $data ? round($data->avg_before, 2) : 0,
                'avg_after' => $data ? round($data->avg_after, 2) : 0,
            ];
        });

        // 4. Reduced Sodium Product Count by Province
        // Products may carry their own province_name (set once per batch by
        // the "นำเข้า Excel" bulk importer - see ReducedSodiumProductImport)
        // - that's the authoritative จังหวัด for a row. The old join-only
        // version below ignored it and grouped purely by the *importing
        // user's* own province (users.Province_id), so every row from a
        // bulk import ended up counted under whichever admin account
        // happened to run the import instead of the จังหวัด actually
        // recorded on the row - the same bug already fixed for
        // reducedSodiumProducts()/mapData above. leftJoin (not join) keeps
        // rows whose own province_name is set even when the user has no
        // matching province row.
        $effectiveProductProvince = "COALESCE(NULLIF(reduced_sodium_products.province_name, ''), province.province_name)";
        $productCountQuery = \App\Models\ReducedSodiumProduct::leftJoin('users', 'reduced_sodium_products.user_id', '=', 'users.id')
            ->leftJoin('province', function ($join) {
                // Table name: the real table is "province" (singular) -
                // that's what App\Models\Province::$table points at,
                // and the only reason the rest of this app's province
                // lookups (dropdowns, the map, etc.) work is that they
                // all go through that model rather than a raw table
                // name. This query bypassed the model and hardcoded
                // "provinces" (plural, matching the create_provinces_
                // table migration's own name, but not the table that
                // actually exists) - on this local MySQL database that
                // literal join failed outright with "Base table or
                // view not found", which on render.com would show up
                // the same way or as missing data, depending on
                // whether its database has the same table under the
                // singular or plural name.
                //
                // Column cast: users.Province_id is a VARCHAR column
                // while province.province_id is an INTEGER primary key
                // (see their migrations), so comparing them needs a
                // cast on PostgreSQL (which has no implicit
                // varchar<->integer comparison) but not on MySQL/
                // MariaDB (which coerces the types implicitly). This
                // used to hardcode the Postgres-only `::text` cast +
                // double-quoted column name, which is invalid syntax
                // under MySQL and would break this join a second way
                // even once the table name above is corrected - see
                // App\Support\DbCompat::castTextExpr().
                $driver = DB::connection()->getDriverName();
                $join->on(
                    DB::raw(DbCompat::castTextExpr($driver, 'users', 'Province_id')),
                    '=',
                    DB::raw(DbCompat::castTextExpr($driver, 'province', 'province_id'))
                );
            });

        // This chart had no fiscal-year filtering at all - changing the
        // ปีงบประมาณ selector above never touched it, unlike every other
        // card on this page. Products may carry their own fiscal_year (set
        // by storeSodiumProducts()/the "นำเข้า Excel" importer) or, for
        // older rows added before that column existed, fall back to
        // YEAR(update_date) - the same two-source match
        // AdminController::scopeProductsByFiscalYear() already uses for
        // this exact model, so this now agrees with what the admin product
        // listing itself shows for the same year.
        if ($activeFiscalYear !== '') {
            $productYearAD = (int) $activeFiscalYear - 543;
            $productCountQuery->where(function ($q) use ($activeFiscalYear, $productYearAD) {
                $q->where('reduced_sodium_products.fiscal_year', (int) $activeFiscalYear)
                    ->orWhere(function ($q2) use ($productYearAD) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('reduced_sodium_products.fiscal_year')->orWhere('reduced_sodium_products.fiscal_year', 0);
                        })->whereYear('reduced_sodium_products.update_date', $productYearAD);
                    });
            });
        }

        if ($selectedProvince !== 'all') {
            $productCountQuery->whereRaw("$effectiveProductProvince = ?", [$selectedProvince]);
        } else {
            $placeholders = implode(',', array_fill(0, count($targetProvinces), '?'));
            $productCountQuery->whereRaw("$effectiveProductProvince IN ($placeholders)", $targetProvinces);
        }

        $productCountDataRaw = $productCountQuery->selectRaw("$effectiveProductProvince as province_name, count(*) as count")
            ->groupBy(\DB::raw($effectiveProductProvince))
            ->get()
            ->keyBy('province_name');

        if ($selectedProvince !== 'all') {
            $productCountData = $productCountDataRaw->where('province_name', $selectedProvince)->map(function ($item, $name) {
                return [
                    'province' => $name,
                    'count' => (int) $item->count
                ];
            })->values();
        } else {
            $productCountData = collect($targetProvinces)->map(function ($province) use ($productCountDataRaw) {
                return [
                    'province' => $province,
                    'count' => $productCountDataRaw->has($province) ? (int) $productCountDataRaw->get($province)->count : 0
                ];
            });
        }

        // 5. Awareness Metrics Card Data
        // 5. Awareness Metrics Calculation (Survey Results)
        //
        // Applies the "count as good behavior" condition for one home-
        // dashboard behavior role directly onto a query builder, for ONE
        // given fiscal year (which question(s) back a role, and which
        // literal answers count as a pass, are themselves set PER YEAR -
        // see SurveyYearMapping::behaviorMappingsFor() - so this can't be
        // resolved once for the whole request the way the simpler
        // scalar-where cards above can). A role can be backed by more
        // than one question at once (an admin can select several from
        // "คอลัมน์สำหรับการ์ด 'พฤติกรรมการบริโภคโซเดียม'"), so this ORs
        // across every question behaviorMappingsFor() finds for the role
        // - any one of them matching counts as a pass. Each question
        // keeps its own chosen pass-values (criteria_pass_values);
        // $default is only used for a question that hasn't had specific
        // answers picked yet. behaviorMappingsFor() reads the independent
        // behavior_roles column (not semantic_key), so a question already
        // used elsewhere - e.g. as that year's is_aware_health criteria
        // question - can also be tagged here without losing that other
        // use.
        $behaviorWhereFor = function ($builder, $fiscalYearForRole, string $role, array $default) {
            $mappings = SurveyYearMapping::behaviorMappingsFor($fiscalYearForRole, $role);
            if ($mappings->isEmpty()) {
                return $builder->whereRaw('1 = 0');
            }
            return $builder->where(function ($q) use ($mappings, $default) {
                $any = false;
                foreach ($mappings as $m) {
                    $values = !empty($m->criteria_pass_values) ? $m->criteria_pass_values : $default;
                    if (empty($values)) {
                        continue;
                    }
                    $expr = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$m->question_key}'))";
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $q->orWhereRaw("{$expr} in ({$placeholders})", $values);
                    $any = true;
                }
                if (!$any) {
                    $q->whereRaw('1 = 0');
                }
            });
        };

        // Every card above (map, sodium chart, product count) supports
        // "ทั้งหมด" ($activeFiscalYear === '') by simply dropping its own
        // year filter, but these 4 behavior cards can't do that the same
        // way - see $behaviorWhereFor's docblock above. "ทั้งหมด" instead
        // runs this exact per-year logic once per distinct fiscal year
        // that actually has data ($awarenessYears, from near the top of
        // this method) and sums the results across years; a single
        // selected year is just this same loop running once, so the two
        // modes can never drift apart from each other.
        $fiscalYearsForMetrics = $activeFiscalYear !== '' ? collect([$activeFiscalYear]) : $awarenessYears;

        $metricsTotalCount = 0;
        // 5.1 Per-Province Breakdown for Interactivity (Top 4 Metrics)
        $metricsByProvince = [
            'no_seasoning' => collect($targetProvinces)->mapWithKeys(fn($p) => [$p => 0]),
            'no_instant_food' => collect($targetProvinces)->mapWithKeys(fn($p) => [$p => 0]),
            'no_pickled_food' => collect($targetProvinces)->mapWithKeys(fn($p) => [$p => 0]),
            'reduction_effort' => collect($targetProvinces)->mapWithKeys(fn($p) => [$p => 0]),
        ];
        $everyTimeCount = 0;
        $noInstantFoodCount = 0;
        $noPickledFoodCount = 0;
        $effortCount = 0;

        foreach ($fiscalYearsForMetrics as $metricsYear) {
            $isFy69Year = ((string) $metricsYear) === '2569';

            // Literal fallback "pass" answers per role for THIS year -
            // unchanged from before this section supported "ทั้งหมด", just
            // resolved per-year now instead of once for the whole request.
            $seasoningDefault = [$isFy69Year ? 'ไม่ทานเลย' : 'ไม่เคยเลย'];
            $instantDefault = [$isFy69Year ? 'ไม่ทานเลย' : 'ไม่เคย'];
            if ($isFy69Year) {
                $processedDefault = ['ไม่ทานเลย'];
            } else {
                // Only freq_pickled_food drives row 3 for pre-FY69 - it used to
                // also require freq_high_sodium, but that question was removed
                // from the "ตั้งค่าเกณฑ์ความตระหนักรู้" screen (same reason as
                // behavioral_reduce_dipping below: no way to assign it meant
                // this row was stuck at 0%).
                $pickledDefault = ['ไม่เคย'];
            }
            if ($isFy69Year) {
                // Only behavioral_reduce_dipping drives row 4 for FY69 - it used
                // to also require behavioral_reduce_soup AND behavioral_reduce_
                // salty, but those 2 questions were removed from the "ตั้งค่า
                // เกณฑ์ความตระหนักรู้" screen (no way to assign them meant row 4
                // was stuck at 0%), so the requirement was simplified to just
                // this one question instead of updating that screen to keep
                // offering all 3.
                $dippingDefault = ['เห็นด้วยอย่างยิ่ง', 'เห็นด้วย'];
            } else {
                // Only importance_level drives row 4 for pre-FY69 - it used to
                // also require effort_level, but that question was removed from
                // the "ตั้งค่าเกณฑ์ความตระหนักรู้" screen (same reason as above).
                $importanceDefault = ['ทุกครั้ง'];
            }

            $metricsBaseYear = SodiumSurvey::where('fiscal_year', $metricsYear);
            if ($selectedProvince !== 'all') {
                $metricsBaseYear->where('province_name', $selectedProvince);
            } else {
                $metricsBaseYear->whereIn('province_name', $targetProvinces);
            }

            $metricsTotalCount += (clone $metricsBaseYear)->count();

            $qNoSeasoning = $behaviorWhereFor(clone $metricsBaseYear, $metricsYear, 'add_seasoning_cook', $seasoningDefault)
                ->select('province_name', \DB::raw('count(*) as count'))->groupBy('province_name')->pluck('count', 'province_name');

            $qNoInstant = $behaviorWhereFor(clone $metricsBaseYear, $metricsYear, 'freq_instant_food', $instantDefault)
                ->select('province_name', \DB::raw('count(*) as count'))->groupBy('province_name')->pluck('count', 'province_name');

            if ($isFy69Year) {
                $qNoPickled = $behaviorWhereFor(clone $metricsBaseYear, $metricsYear, 'freq_processed_food', $processedDefault)
                    ->select('province_name', \DB::raw('count(*) as count'))->groupBy('province_name')->pluck('count', 'province_name');
                $qEffort = $behaviorWhereFor(clone $metricsBaseYear, $metricsYear, 'behavioral_reduce_dipping', $dippingDefault)
                    ->select('province_name', \DB::raw('count(*) as count'))->groupBy('province_name')->pluck('count', 'province_name');
            } else {
                $qNoPickled = $behaviorWhereFor(clone $metricsBaseYear, $metricsYear, 'freq_pickled_food', $pickledDefault)
                    ->select('province_name', \DB::raw('count(*) as count'))->groupBy('province_name')->pluck('count', 'province_name');
                $qEffort = $behaviorWhereFor(clone $metricsBaseYear, $metricsYear, 'importance_level', $importanceDefault)
                    ->select('province_name', \DB::raw('count(*) as count'))->groupBy('province_name')->pluck('count', 'province_name');
            }

            foreach ($targetProvinces as $p) {
                $metricsByProvince['no_seasoning'][$p] += $qNoSeasoning->get($p, 0);
                $metricsByProvince['no_instant_food'][$p] += $qNoInstant->get($p, 0);
                $metricsByProvince['no_pickled_food'][$p] += $qNoPickled->get($p, 0);
                $metricsByProvince['reduction_effort'][$p] += $qEffort->get($p, 0);
            }

            // Same shortcut as before "ทั้งหมด" (sum the per-province
            // counts already fetched instead of re-querying for an
            // ungrouped total), just accumulated across every year in the
            // loop instead of computed once.
            $everyTimeCount += $qNoSeasoning->sum();
            $noInstantFoodCount += $qNoInstant->sum();
            $noPickledFoodCount += $qNoPickled->sum();
            $effortCount += $qEffort->sum();
        }

        $awarenessMetrics = [
            'total' => $metricsTotalCount,
            'no_seasoning' => [
                'count' => $everyTimeCount,
                'percentage' => $metricsTotalCount > 0 ? round(($everyTimeCount / $metricsTotalCount) * 100, 2) : 0
            ],
            'no_instant_food' => [
                'count' => $noInstantFoodCount,
                'percentage' => $metricsTotalCount > 0 ? round(($noInstantFoodCount / $metricsTotalCount) * 100, 2) : 0
            ],
            'no_pickled_food' => [
                'count' => $noPickledFoodCount,
                'percentage' => $metricsTotalCount > 0 ? round(($noPickledFoodCount / $metricsTotalCount) * 100, 2) : 0
            ],
            'reduction_effort' => [
                'count' => $effortCount,
                'percentage' => $metricsTotalCount > 0 ? round(($effortCount / $metricsTotalCount) * 100, 2) : 0
            ],
            'current_year' => $activeFiscalYear,
            'current_province' => $selectedProvince,
            'target_provinces' => $targetProvinces
        ];

        return compact(
            'awarenessCriteriaPending',
            'awarenessData',
            'sodiumCompareData',
            'productCountData',
            'awarenessMetrics',
            'metricsByProvince',
            'awarenessOverallTotal',
            'awarenessOverallPass',
            'awarenessOverallFail',
            'awarenessOverallRate',
            'awarenessMethod'
        );
        });

        // Every variable computed inside the cached closure above is pulled
        // back into this method's own scope under its original name
        // (EXTR_SKIP leaves $activeFiscalYear/$selectedProvince/etc. alone
        // rather than overwriting them, though none of the returned keys
        // collide with those anyway).
        extract($homeDashboardData, EXTR_SKIP);

        return view('pages.home', compact(
            'awarenessData',
            'sodiumCompareData',
            'productCountData',
            'awarenessMetrics',
            'metricsByProvince',
            'years',
            'fiscalYear',
            'fiscalYearLabel',
            'awarenessCriteriaPending',
            'awarenessOverallTotal',
            'awarenessOverallPass',
            'awarenessOverallFail',
            'awarenessOverallRate',
            'awarenessMethod'
        ));
    }

    public function consumptionReport(Request $request)
    {
        $targetProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];

        $fiscalYear = $request->get('fiscal_year', 2569);
        $selectedQuarter = $request->get('quarter', 'ทั้งหมด');
        $selectedProvince = $request->get('province_search', 'ทั้งหมด');
        $selectedAgency = $request->get('agency_search');

        $dbProvinces = Province::whereIn('province_name', $targetProvinces)->get();

        // --- THE "AGENCY" VISION for DASHBOARD ---
        $agenciesQuery = User::with(['province', 'district', 'subdistrictHospital', 'hospital'])
            ->whereHas('province', function ($q) use ($selectedProvince, $targetProvinces) {
                if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
                    $q->where('province_name', $selectedProvince);
                } else {
                    $q->whereIn('province_name', $targetProvinces);
                }
            })
            ->whereHas('saltAssessments', function ($q) use ($fiscalYear) {
                if ($fiscalYear !== 'ทั้งหมด') {
                    $q->where('fiscal_year', $fiscalYear);
                }
            });

        $dbAgencies = $agenciesQuery->get()
            ->map(function ($u) {
                if ($u->User_rank_id == 2) {
                    $u->agency_key = 'PROV_' . $u->Province_id;
                    $u->agency_name_sort = 'สำนักงานสาธารณสุขจังหวัด' . ($u->province->province_name ?? '');
                } elseif ($u->User_rank_id == 3) {
                    $u->agency_key = 'DIST_' . $u->District_id;
                    $u->agency_name_sort = 'สำนักงานสาธารณสุขอำเภอ' . ($u->district->district_name ?? '');
                } elseif ($u->User_rank_id == 4 && $u->subdistrictHospital) {
                    $u->agency_key = 'HOSP_' . $u->Hospital_id;
                    $u->agency_name_sort = $u->subdistrictHospital->hospital_name;
                } else {
                    $u->agency_key = 'USER_' . $u->id;
                    $u->agency_name_sort = $u->Con_name ?: $u->name;
                }
                return $u;
            })
            ->unique('agency_key')
            ->sortBy('agency_name_sort');

        $baseQuery = User::with(['province', 'district', 'subdistrictHospital', 'hospital'])
            ->whereHas('saltAssessments', function ($q) use ($fiscalYear, $selectedQuarter) {
                if ($fiscalYear !== 'ทั้งหมด') {
                    $q->where('fiscal_year', $fiscalYear);
                }
                if ($selectedQuarter !== 'ทั้งหมด') {
                    $q->where('quarter', $selectedQuarter);
                }
            })
            ->whereHas('province', function ($q) use ($targetProvinces, $selectedProvince) {
                if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
                    $q->where('province_name', $selectedProvince);
                } else {
                    $q->whereIn('province_name', $targetProvinces);
                }
            });

        if ($selectedAgency && $selectedAgency !== 'ทั้งหมด') {
            $rep = User::find($selectedAgency);
            if ($rep) {
                $baseQuery->where(function ($q) use ($rep) {
                    if ($rep->User_rank_id == 2) {
                        $q->where('Province_id', $rep->Province_id);
                    } elseif ($rep->User_rank_id >= 3) {
                        $q->where('District_id', $rep->District_id);
                    } else {
                        $q->where('id', $rep->id);
                    }
                });
            }
        }

        $allReporters = $baseQuery->get();

        $agencies = $allReporters->map(function ($u) {
            $key = '';
            $name = '';
            if ($u->User_rank_id == 2) {
                $key = 'PROV_' . $u->Province_id;
                $name = 'สำนักงานสาธารณสุขจังหวัด' . ($u->province->province_name ?? '');
            } elseif ($u->User_rank_id == 3) {
                $key = 'DIST_' . $u->District_id;
                $name = 'สำนักงานสาธารณสุขอำเภอ' . ($u->district->district_name ?? '');
            } elseif ($u->User_rank_id == 4 && $u->subdistrictHospital) {
                $key = 'HOSP_' . $u->Hospital_id;
                $name = $u->subdistrictHospital->hospital_name;
            } else {
                $key = 'USER_' . $u->id;
                $name = $u->Con_name ?: $u->name ?: 'N/A';
            }
            $u->agency_key = $key;
            $u->agency_display_name = $name;
            return $u;
        })->unique('agency_key')->sortBy('agency_display_name');

        // Itemized View
        $detailAgencies = $allReporters->map(function ($u) {
            $key = '';
            $name = '';
            if ($u->User_rank_id == 2) {
                $key = 'PROV_' . $u->Province_id;
                $name = 'สำนักงานสาธารณสุขจังหวัด' . ($u->province->province_name ?? '');
            } elseif ($u->User_rank_id == 3) {
                $key = 'DIST_' . $u->District_id;
                $name = 'สำนักงานสาธารณสุขอำเภอ' . ($u->district->district_name ?? '');
            } elseif ($u->User_rank_id == 4 && $u->subdistrictHospital) {
                $key = 'HOSP_' . $u->Hospital_id;
                $name = $u->subdistrictHospital->hospital_name;
            } else {
                $key = 'USER_' . $u->id;
                $name = $u->Con_name ?: $u->name ?: 'N/A';
            }
            return (object) ['agency_key' => $key, 'agency_display_name' => $name, 'fiscal_year' => null, 'user_ids' => []];
        })->unique('agency_key')->values();

        // Fiscal years per agency used to run one SaltAssessment query PER
        // AGENCY here (every agency across every province/district/รพ.สต. -
        // not just the current page of $perPage=8, since pagination happens
        // further down over $allAgencyYears). Fetched once instead: every
        // relevant user_id's (user_id, fiscal_year) pairs in a single
        // query, grouped by user_id in memory, then unioned per agency -
        // same distinct fiscal-year set per agency as before (whereIn()
        // across an agency's user_ids already collapsed to the same
        // distinct years that a per-user union produces), just without
        // the repeated round-trips.
        $allReporterIds = $allReporters->pluck('id');
        $fiscalYearsByUserId = SaltAssessment::whereIn('user_id', $allReporterIds)
            ->when($fiscalYear !== 'ทั้งหมด', fn($q) => $q->where('fiscal_year', $fiscalYear))
            ->distinct()
            ->get(['user_id', 'fiscal_year'])
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->pluck('fiscal_year'));

        $allAgencyYears = collect();
        foreach ($detailAgencies as $agency) {
            $uIds = $allReporters->where('agency_key', $agency->agency_key)->pluck('id');
            $agency->user_ids = $uIds;
            $yearsList = $uIds
                ->flatMap(fn($uid) => $fiscalYearsByUserId->get($uid, collect()))
                ->unique()
                ->values();
            foreach ($yearsList as $y) {
                $allAgencyYears->push((object) [
                    'agency_key' => $agency->agency_key,
                    'agency_display_name' => $agency->agency_display_name,
                    'fiscal_year' => $y,
                    'user_ids' => $uIds
                ]);
            }
        }
        $allAgencyYears = $allAgencyYears->sortBy('agency_display_name')->sortByDesc('fiscal_year');

        $perPage = 8;
        $detailPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('detail_page') ?: 1;
        $paginatedSalt = new \Illuminate\Pagination\LengthAwarePaginator(
            $allAgencyYears->slice(($detailPage - 1) * $perPage, $perPage)->values(),
            $allAgencyYears->count(),
            $perPage,
            $detailPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'pageName' => 'detail_page']
        );
        $paginatedSalt->appends($request->query());

        // Step definitions for the ขั้นตอนดำเนินงาน 5 ข้อ card - step 5 folds
        // its five sub-items (5.1-5.5) together. Shared by the per-agency
        // curation below (embedded on each row for the click-to-select
        // panel) and by buildStepOverviewRows() in general.
        $stepDefs = [
            ['num' => '1', 'title' => 'จัดทำ MOU / คำสั่งคณะทำงานระดับจังหวัด', 'fields' => ['ans_1_detail']],
            ['num' => '2', 'title' => 'สุ่มตรวจ Salt meter ในอาหารเมนูยอดฮิต', 'fields' => ['ans_2_detail']],
            ['num' => '3', 'title' => 'จัดทำแผนปฏิบัติการลดโซเดียมระดับจังหวัด', 'fields' => ['ans_3_detail']],
            ['num' => '4', 'title' => 'ประเมินความตระหนักรู้ความเสี่ยงโซเดียม', 'fields' => ['ans_4_detail']],
            ['num' => '5', 'title' => 'การดำเนินงานตามกลยุทธ์ 5 ด้าน', 'fields' => ['ans_5_1_detail', 'ans_5_2_detail', 'ans_5_3_detail', 'ans_5_4_detail', 'ans_5_5_detail']],
        ];

        $saltAssessmentsMapping = $paginatedSalt->getCollection()->map(function ($record) use ($selectedQuarter, $stepDefs) {
            $allAssessments = SaltAssessment::whereIn('user_id', $record->user_ids)->where('fiscal_year', $record->fiscal_year)->get();
            $qLimit = ($selectedQuarter === 'ทั้งหมด') ? 4 : (int) $selectedQuarter;
            $fields = ['1' => 'ans_1_detail', '2' => 'ans_2_detail', '3' => 'ans_3_detail', '4' => 'ans_4_detail', '5.1' => 'ans_5_1_detail', '5.2' => 'ans_5_2_detail', '5.3' => 'ans_5_3_detail', '5.4' => 'ans_5_4_detail', '5.5' => 'ans_5_5_detail'];
            $items = [];
            foreach ($fields as $label => $field) {
                $firstDone = $allAssessments->filter(fn($a) => !empty($a->$field) && $a->quarter <= $qLimit)->sortBy('quarter')->first();
                $items[$label] = ['done' => !!$firstDone, 'q' => $firstDone ? $firstDone->quarter : null];
            }
            // Same cumulative definition as $items above (and as the
            // leaderboard/chart's is_complete further down): a field
            // counts as done once any quarter up to the limit has it
            // filled in. Previously this required ONE single assessment
            // record at quarter == qLimit to have every field filled at
            // once, which could mark a row "กำลังดำเนินการ" even when
            // every item's checkmark in this very row was already done
            // (e.g. all 9 fields completed in an earlier quarter, with no
            // further submission at the limit quarter) - contradicting
            // both the row's own checkmarks and the leaderboard above it.
            $isComp = collect($items)->every(fn($item) => $item['done']);

            // Text-curated summary of THIS agency's own answers - powers the
            // ขั้นตอนดำเนินงาน panel that appears when this row is clicked
            // (see selectStepOverviewRow() in extra_js). totalAgenciesInScope
            // is 1 here since it's a single agency's own record, not an
            // aggregate across agencies.
            $stepOverview = $this->buildStepOverviewRows($allAssessments, $stepDefs, 1, $qLimit);
            $problemsRaw = $allAssessments->pluck('problems')->filter(fn($t) => trim((string) $t) !== '')->values()->all();
            $suggestionsRaw = $allAssessments->pluck('suggestions')->filter(fn($t) => trim((string) $t) !== '')->values()->all();

            return [
                'fiscal_year' => $record->fiscal_year,
                'agency' => $record->agency_display_name,
                'status' => $isComp ? 'ส่งครบแล้ว' : 'กำลังดำเนินการ',
                'items' => $items,
                'step_overview' => $stepOverview,
                'step_problems' => $this->curateNarrativePoints($problemsRaw),
                'step_suggestions' => $this->curateNarrativePoints($suggestionsRaw),
            ];
        });
        $paginatedSalt->setCollection($saltAssessmentsMapping);

        // Stats & Chart Data - the KPI cards, bar chart and province
        // leaderboard all read from this, so it needs to honor the same
        // ปีงบประมาณ/จังหวัด/หน่วยงาน/ไตรมาส filters as the detail table
        // below, instead of always covering every province and agency.
        $regionalReportersQuery = User::with(['province', 'district', 'subdistrictHospital'])
            ->whereHas('province', function ($q) use ($targetProvinces, $selectedProvince) {
                if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
                    $q->where('province_name', $selectedProvince);
                } else {
                    $q->whereIn('province_name', $targetProvinces);
                }
            })
            ->whereHas('saltAssessments', function ($q) use ($fiscalYear) {
                if ($fiscalYear !== 'ทั้งหมด')
                    $q->where('fiscal_year', $fiscalYear);
            });

        if ($selectedAgency && $selectedAgency !== 'ทั้งหมด') {
            $statsRep = User::find($selectedAgency);
            if ($statsRep) {
                $regionalReportersQuery->where(function ($q) use ($statsRep) {
                    if ($statsRep->User_rank_id == 2) {
                        $q->where('Province_id', $statsRep->Province_id);
                    } elseif ($statsRep->User_rank_id >= 3) {
                        $q->where('District_id', $statsRep->District_id);
                    } else {
                        $q->where('id', $statsRep->id);
                    }
                });
            }
        }

        $regionalReporters = $regionalReportersQuery->get();

        $regionalAgencies = $regionalReporters->map(function ($u) {
            if ($u->User_rank_id == 2)
                $u->agency_key = 'PROV_' . $u->Province_id;
            elseif ($u->User_rank_id == 3)
                $u->agency_key = 'DIST_' . $u->District_id;
            elseif ($u->User_rank_id == 4 && $u->subdistrictHospital)
                $u->agency_key = 'HOSP_' . $u->Hospital_id;
            else
                $u->agency_key = 'USER_' . $u->id;
            return $u;
        })->unique('agency_key');

        $statsQLimit = ($selectedQuarter === 'ทั้งหมด') ? 4 : (int) $selectedQuarter;
        $milestoneFields = ['ans_1_detail', 'ans_2_detail', 'ans_3_detail', 'ans_4_detail', 'ans_5_1_detail', 'ans_5_2_detail', 'ans_5_3_detail', 'ans_5_4_detail', 'ans_5_5_detail'];

        $agencyStatsRecords = $regionalAgencies->map(function ($agencyUser) use ($fiscalYear, $statsQLimit, $milestoneFields, $regionalReporters) {
            $peerIds = $regionalReporters->where('agency_key', $agencyUser->agency_key)->pluck('id');
            $assessmentsQuery = SaltAssessment::whereIn('user_id', $peerIds)->where('quarter', '<=', $statsQLimit);
            if ($fiscalYear !== 'ทั้งหมด') {
                $assessmentsQuery->where('fiscal_year', $fiscalYear);
            }
            $assessments = $assessmentsQuery->get();

            // Cumulative per-field completion (same rule as the milestone
            // tab): a field counts as done once any quarter up to the limit
            // has it filled in - not just a single row having everything at
            // once - so province progress reflects partial work too, not
            // only fully-finished agencies.
            $doneCount = 0;
            foreach ($milestoneFields as $f) {
                if ($assessments->contains(fn($a) => !empty($a->$f))) {
                    $doneCount++;
                }
            }

            return (object) [
                'agency_key' => $agencyUser->agency_key,
                'province' => $agencyUser->province->province_name ?? 'N/A',
                'is_complete' => $doneCount === count($milestoneFields),
                'done_count' => $doneCount,
                'field_count' => count($milestoneFields),
            ];
        });

        $stats = ['complete' => 0, 'in_progress' => 0, 'provinces' => []];
        $filteredKeys = $agencies->pluck('agency_key')->toArray();
        foreach ($targetProvinces as $p) {
            $pStats = $agencyStatsRecords->where('province', $p);
            $stats['provinces'][$p] = [
                'complete' => $pStats->where('is_complete', true)->count(),
                'in_progress' => $pStats->where('is_complete', false)->count(),
                // Sum of milestone fields actually done vs. possible, across
                // every agency in the province - drives the leaderboard's
                // percentage so partial progress shows up, not just 100%/0%.
                'done_fields' => $pStats->sum('done_count'),
                'total_fields' => $pStats->sum('field_count'),
            ];
            $pFiltered = $pStats->filter(fn($s) => in_array($s->agency_key, $filteredKeys));
            $stats['complete'] += $pFiltered->where('is_complete', true)->count();
            $stats['in_progress'] += $pFiltered->where('is_complete', false)->count();
        }

        $years = FiscalYear::selectableYearsFor('salt_assessment', SaltAssessment::distinct()->pluck('fiscal_year'));

        return view('pages.consumption-report', compact(
            'paginatedSalt',
            'years',
            'fiscalYear',
            'selectedQuarter',
            'dbProvinces',
            'selectedProvince',
            'dbAgencies',
            'selectedAgency',
            'stats'
        ));
    }

    /**
     * Build the ขั้นตอนดำเนินงาน 5 ข้อ rows (text-curated summary, grouped
     * into the 5 report steps - step 5 folds its five sub-items together)
     * for a given set of SaltAssessment rows. $totalAgenciesInScope is 1
     * for a single agency's own rows, or the real agency count when
     * summarizing across several agencies at once.
     *
     * @param \Illuminate\Support\Collection $assessments
     * @param array $stepDefs
     * @return array
     */
    private function buildStepOverviewRows($assessments, array $stepDefs, int $totalAgenciesInScope = 1, int $qLimit = 4): array
    {
        // Same cutoff the detail table's own per-item checkmarks use
        // ($items[$label] in consumptionReport() above) - filtered once
        // here so neither this panel's completion state nor its quarter
        // badges can ever show progress that table is currently hiding
        // behind the ปีงบประมาณ/ไตรมาส filter.
        $scopedAssessments = $assessments->filter(fn($a) => $a->quarter <= $qLimit);

        return collect($stepDefs)->map(function ($step) use ($scopedAssessments, $totalAgenciesInScope) {
            $rawTexts = [];
            $doneUserIds = [];
            $doneQuarters = [];
            foreach ($step['fields'] as $field) {
                // The quarter this field was FIRST filled in - identical
                // rule to $items[$label]['q'] in the detail table above,
                // so a step folding several fields together (step 5) ends
                // up with the exact same set of quarters that table's own
                // columns show for those same fields.
                $firstDone = $scopedAssessments->filter(fn($a) => !empty($a->$field))->sortBy('quarter')->first();
                if ($firstDone) {
                    $doneQuarters[] = (int) $firstDone->quarter;
                }
                foreach ($scopedAssessments as $a) {
                    $val = trim((string) ($a->$field ?? ''));
                    if ($val !== '') {
                        $rawTexts[] = $val;
                        $doneUserIds[$a->user_id] = true;
                    }
                }
            }
            $doneCount = count($doneUserIds);
            if ($doneCount === 0) {
                $statusLabel = 'ยังไม่เริ่มดำเนินการ';
                // Matches the existing .badge-pending/.badge-complete/
                // .badge-inprogress classes already used in the detail
                // table's "สถานะ" column, so this status pill reuses the
                // same visual language instead of inventing a new one.
                $statusClass = 'pending';
            } elseif ($doneCount >= $totalAgenciesInScope) {
                $statusLabel = 'เสร็จสิ้น';
                $statusClass = 'complete';
            } else {
                $statusLabel = 'ก้าวหน้าตามแผน';
                $statusClass = 'inprogress';
            }

            $uniqueQuarters = collect($doneQuarters)->unique()->sort()->values();
            $qLabel = $uniqueQuarters->isEmpty()
                ? null
                : ($uniqueQuarters->count() === 1
                    ? 'Q' . $uniqueQuarters->first()
                    : 'Q' . $uniqueQuarters->first() . '-Q' . $uniqueQuarters->last());

            return [
                'num' => $step['num'],
                'q_label' => $qLabel,
                'title' => $step['title'],
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'curated' => $this->curateNarrativePoints($rawTexts),
            ];
        })->values()->all();
    }

    /**
     * Curate a batch of free-text agency answers into a deduplicated
     * bullet list for display. Each answer is split into its own lines
     * (agencies already write them as a dash-prefixed list, matching the
     * paper report form), and identical lines written by more than one
     * agency are folded into one - but nothing is capped or truncated
     * here: every distinct line the agency wrote is shown, in full, per
     * the user's explicit request ("แสดงข้อมูลทั้งหมดเลยครับ" - show ALL
     * the data). Previously this capped the list to 6 items and
     * word-safe truncated each to 90 chars via truncateWordsSafe(),
     * hiding the rest behind a "+N ข้อเพิ่มเติม" line.
     *
     * @param string[] $rawTexts
     * @return array{items: string[], more: int} 'more' is always 0 now -
     *     kept in the return shape so the existing blade/JS (which reads
     *     curated.more) doesn't need to branch on a missing key.
     */
    private function curateNarrativePoints(array $rawTexts): array
    {
        $seen = [];
        $points = [];
        foreach ($rawTexts as $text) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $line = preg_replace('/^[-•*·▪◦]\s*/u', '', $line);
                $line = preg_replace('/^\(?\d+[.\)]\s*/u', '', $line);
                $line = trim((string) $line);
                if ($line === '') {
                    continue;
                }
                $dedupeKey = mb_strtolower($line);
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;
                $points[] = $line;
            }
        }

        return [
            'items' => $points,
            'more' => 0,
        ];
    }

    public function awareness(Request $request)
    {
        // sodium_surveys holds every fiscal year in a single table now
        // (survey_data JSON carries whatever that year's form asked), so
        // there's no more legacy/FY69 model split or manual dual-collection
        // pagination - just one query, filtered like before.
        $query = SodiumSurvey::query();

        // Every distinct fiscal year that actually has data - computed once
        // here (was previously computed a second time, later, just for the
        // dropdown) and reused both for the "latest year" default below and
        // for the criteria/dropdown logic further down.
        $allFiscalYears = SodiumSurvey::query()->distinct()->pluck('fiscal_year');

        // "ปีงบประมาณ" defaults to the LATEST year with data instead of every
        // year at once. With years of history piling up, a bare first visit
        // (no fiscal_year in the URL at all) used to mean "ทั้งหมด" - every
        // chart/aggregate below then scanned the entire table, and for
        // "score"-method years, decoded survey_data row by row for every
        // respondent ever submitted. That's both the slowest case and not
        // what almost anyone actually wants to see first. has() (not
        // filled()) is checked here so a user who explicitly picks
        // "ทั้งหมด" (value="") still gets every year as before - only a
        // genuinely bare visit (the key absent entirely) gets defaulted.
        $defaultFiscalYear = (string) FiscalYear::selectableYearsFor('awareness', $allFiscalYears)->first();
        $effectiveFiscalYear = $request->has('fiscal_year') ? (string) $request->input('fiscal_year') : $defaultFiscalYear;

        // 1. Filter Setup
        if ($effectiveFiscalYear !== '') {
            $query->where('fiscal_year', $effectiveFiscalYear);
        }
        if ($request->filled('province')) {
            $query->where('province_name', $request->province);
        }
        if ($request->filled('district')) {
            $query->where('district_name', $request->district);
        }

        // Clone the filtered builder before it's consumed by the list fetch
        // below, so the chart stats can run as lightweight SQL GROUP BY /
        // COUNT(*) queries against the database instead of pulling every
        // matching row into PHP and aggregating with Collection methods.
        //
        // The "aware/pass" criteria and the 4 question-breakdown panels
        // below are both driven by survey_year_mappings (semantic_key /
        // dashboard_panel), set per fiscal year from "การประเมินความตระหนักรู้
        // > ตั้งค่าคำถามและเกณฑ์การประเมิน" - a brand new year needs someone to
        // visit that screen once, not a code change here.
        $statsQueryAll = clone $query;

        // Everything below this point (every chart/table stat on the page,
        // down to the 4 dashboard panels) is a pure function of the three
        // filters above plus whatever's currently in sodium_surveys /
        // survey_year_mappings - it does no writes and nothing here depends
        // on which page of the assessment list is showing. Several of these
        // aggregates can't use an index at all (JSON_EXTRACT on survey_data
        // for the criteria/panel breakdowns, or a full chunked PHP pass for
        // "score"-method years' rubric), so their cost scales with how many
        // rows exist for the selected fiscal year - which is exactly what
        // made the page (and its filters) slow to load as more years' data
        // piled up. Caching the whole bundle for a while, keyed by
        // the 3 filters, means every OTHER admin loading the same year/
        // province/district combination in that window - by far the common
        // case, since most traffic is everyone checking the current year -
        // gets it back instantly instead of re-running all of this. 60
        // minutes (was 10 - measured live: a first, cache-miss view of a
        // year/province/district combination nobody had recently opened
        // took 1.3-7s depending on how much data that combination scans,
        // vs. 0.2-0.6s once cached; a 6x longer window means 6x fewer
        // people ever have to pay that cost across a normal workday,
        // confirmed with the user as an acceptable tradeoff) means new
        // data or a settings change shows up within a bit on its own,
        // without needing every write path to remember to bust this
        // cache - and, for a score-configured fiscal year, gives the
        // genuinely expensive part (decoding and scoring every respondent
        // row one by one, which no query-level tuning removes) more room
        // to actually pay off across a browsing session before it has to
        // happen again.
        // A settings change from "ตั้งค่าแดชบอร์ด" / "ตั้งค่าเกณฑ์ความตระหนักรู้" /
        // "ตั้งค่าคะแนนความตระหนักรู้" (all of which write to
        // survey_year_mappings) needs to show up on the very next load, not
        // wait out the 60-minute cache below - otherwise an admin who just
        // saved a setting keeps seeing the OLD data and reasonably reads
        // that as "the setting didn't take". Folding the latest updated_at
        // across whichever fiscal year this request covers into the cache
        // key means a save always produces a brand new key immediately, so
        // the very next request recomputes fresh; the old key's entry is
        // simply left to expire on its own 60-minute TTL exactly as before
        // - no settings-save path has to remember to forget anything.
        // A brand new SURVEY IMPORT (new respondent rows, not a settings
        // change) is NOT folded into this key - it can take up to the full
        // 60 minutes to appear here, longer than the previous 10-minute
        // wait. Confirmed acceptable with the user; if that ever changes,
        // fold each covered fiscal year's row count/max(id) into the key
        // the same way settings_version already folds in updated_at.
        $mappingsVersionQuery = SurveyYearMapping::query();
        if ($effectiveFiscalYear !== '') {
            $mappingsVersionQuery->where('fiscal_year', $effectiveFiscalYear);
        }
        $mappingsVersion = $mappingsVersionQuery->max('updated_at');

        $dashboardCacheKey = 'awareness_dashboard:' . md5(json_encode([
            'fiscal_year' => $effectiveFiscalYear,
            'province' => $request->input('province'),
            'district' => $request->input('district'),
            'settings_version' => $mappingsVersion,
        ]));

        $dashboardData = Cache::remember($dashboardCacheKey, now()->addMinutes(60), function () use ($statsQueryAll, $effectiveFiscalYear, $allFiscalYears) {
        // --- SQL-side aggregation helpers -------------------------------
        // Runs "SELECT col, COUNT(*) GROUP BY col" against the database and
        // returns a Collection shaped exactly like the old
        // ->countBy($col)/->groupBy($col)->map->count() results: keyed by
        // distinct column value, valued by row count.
        $countByColumn = function ($builder, string $column) {
            return (clone $builder)
                ->select($column)
                ->selectRaw('COUNT(*) as aggregate_count')
                ->groupBy($column)
                ->get()
                ->mapWithKeys(function ($row) use ($column) {
                    return [$row->$column => (int) $row->aggregate_count];
                });
        };

        // Chart 1: Respondents by Province (Donut)
        $provinceStats = $countByColumn($statsQueryAll, 'province_name');

        // Demographic Stats
        $genderStats = $countByColumn($statsQueryAll, 'gender');

        // Map Age Ranges into 5 main groups. The bucketing is bespoke PHP
        // logic (not a plain column value), so this one still needs actual
        // rows - but only the age_range column, not a full model hydration
        // (which would also decode every row's survey_data JSON for no
        // reason here).
        $ageStats = (clone $statsQueryAll)->pluck('age_range')->map(function($ageText) {
            $ageText = $ageText ?? '';
            $age = (int) filter_var($ageText, FILTER_SANITIZE_NUMBER_INT);
            if (!$age && $ageText !== '0' && $ageText !== '0 ปี') {
                return 'อื่นๆ';
            }

            if ($age < 20) return 'ต่ำกว่า 20 ปี';
            if ($age <= 35) return '20 - 35 ปี';
            if ($age <= 50) return '36 - 50 ปี';
            if ($age <= 60) return '51 - 60 ปี';
            return 'มากกว่า 60 ปี';
        })->countBy();

        $educationStats = $countByColumn($statsQueryAll, 'education');

        // Chart 2/3: Pass/Fail by Province & Awareness % by District
        // Logic: Aware (ตระหนักรู้) = criteria 1 answered one of its own
        // configured "pass" values AND criteria 2 likewise. Which question(s)
        // back criteria 1/2, and which of each one's answers count as
        // "pass", is set per fiscal year from "การประเมินความตระหนักรู้ >
        // ตั้งค่าเกณฑ์ความตระหนักรู้" (see SurveyYearMapping::CRITERIA_ROLES - a
        // question with no pass values chosen yet falls back to ['ใช่',
        // 'เคย']). A role can now be backed by more than one question at
        // once, collected into a list per role/year below - ANY one of them
        // matching counts as that role being satisfied (same OR semantics
        // as behavior_roles elsewhere). A year missing either role mapped
        // at all is treated as "criteria not decided yet" and excluded here
        // - the same "pending" treatment FY69 always got, just no longer
        // hardcoded to that one fiscal year specifically.
        // Split every fiscal year with real data into "questions"-method
        // years (the original 2-specific-questions rule) vs "score"-method
        // years (FY2569+'s scoring rubric) per AwarenessPassResolver, and
        // further into "actually configured yet" vs not - a year in neither
        // group below is "pending" (same treatment FY69 always got before
        // any criteria existed, just no longer hardcoded to that one year).
        // $allFiscalYears is computed once, up front in the method (also
        // needed there to pick the default fiscal year before this cached
        // block even runs), and reused here and below for the dropdown -
        // previously this ran the same distinct-fiscal-year query a second
        // time just for the dropdown.
        $criteriaByYear = [];
        $questionYears = collect();
        $scoreYears = collect();
        $scoreCalculators = [];
        foreach ($allFiscalYears as $year) {
            if (AwarenessPassResolver::methodFor($year) === AwarenessPassSetting::METHOD_SCORE) {
                $calculator = new AwarenessScoreCalculator($year);
                if ($calculator->isFullyConfigured()) {
                    $scoreYears->push($year);
                    $scoreCalculators[$year] = $calculator;
                }
                continue;
            }

            $awareMappings = SurveyYearMapping::criteriaMappingsFor($year, 'is_aware_health');
            $limitMappings = SurveyYearMapping::criteriaMappingsFor($year, 'is_know_limit');
            if ($awareMappings->isNotEmpty() && $limitMappings->isNotEmpty()) {
                $criteriaByYear[$year] = [
                    'is_aware_health' => $awareMappings->map(fn ($m) => ['key' => $m->question_key, 'pass_values' => $m->criteria_pass_values ?: SurveyYearMapping::DEFAULT_CRITERIA_PASS_VALUES])->all(),
                    'is_know_limit' => $limitMappings->map(fn ($m) => ['key' => $m->question_key, 'pass_values' => $m->criteria_pass_values ?: SurveyYearMapping::DEFAULT_CRITERIA_PASS_VALUES])->all(),
                ];
                $questionYears->push($year);
            }
        }
        $yearsWithCriteria = $questionYears->merge($scoreYears)->unique()->values();

        // "(expr IN (...) OR expr IN (...))" across however many questions
        // back one role for one year - falls back to the always-false
        // "1=0" when given none, so an unconfigured role never matches
        // rather than matching everything.
        $criteriaSqlCondFromList = function (array $list): string {
            $parts = [];
            foreach ($list as $entry) {
                $expr = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$entry['key']}'))";
                $parts[] = "{$expr} IN (" . SurveyYearMapping::sqlInList($entry['pass_values']) . ")";
            }
            return $parts ? '(' . implode(' OR ', $parts) . ')' : '1=0';
        };

        // One UNION ALL branch per "questions"-method year (each scoped to
        // its own fiscal_year, so a year's JSON key(s) - and their own
        // pass-values lists - are never applied to another year's rows),
        // PLUS a row-by-row PHP pass over each "score"-method year (no
        // single SQL condition can express the scoring rubric's several
        // averaged sub-groups - see AwarenessPassResolver), folded together
        // by province AND by district in the same pass.
        //
        // Province and district used to be computed by calling this twice
        // with a different $groupColumn - which for "score"-method years
        // meant running AwarenessScoreCalculator::compute() (a per-row
        // decode of survey_data plus ~26 role calculations) TWICE for every
        // single respondent, once per column. Computing both columns from
        // one query (grouped by province+district together) and one
        // chunked PHP pass halves that work.
        $buildPassFailStats = function () use ($statsQueryAll, $criteriaByYear, $questionYears, $scoreYears, $scoreCalculators, $criteriaSqlCondFromList) {
            $byProvince = [];
            $byDistrict = [];

            if ($questionYears->isNotEmpty()) {
                $subQueries = [];
                foreach ($questionYears as $year) {
                    $keys = $criteriaByYear[$year];
                    $awareCond = $criteriaSqlCondFromList($keys['is_aware_health']);
                    $limitCond = $criteriaSqlCondFromList($keys['is_know_limit']);
                    $subQueries[] = (clone $statsQueryAll)->where('fiscal_year', $year)
                        ->select(['province_name', 'district_name'])
                        ->selectRaw("SUM(CASE WHEN {$awareCond} AND {$limitCond} THEN 1 ELSE 0 END) as pass_count")
                        ->selectRaw('COUNT(*) as total_count')
                        ->groupBy(['province_name', 'district_name']);
                }

                $combined = array_shift($subQueries);
                foreach ($subQueries as $sub) {
                    $combined->unionAll($sub);
                }

                foreach ($combined->get() as $row) {
                    $pass = (int) $row->pass_count;
                    $total = (int) $row->total_count;

                    $byProvince[$row->province_name] = $byProvince[$row->province_name] ?? ['pass' => 0, 'total' => 0];
                    $byProvince[$row->province_name]['pass'] += $pass;
                    $byProvince[$row->province_name]['total'] += $total;

                    $byDistrict[$row->district_name] = $byDistrict[$row->district_name] ?? ['pass' => 0, 'total' => 0];
                    $byDistrict[$row->district_name]['pass'] += $pass;
                    $byDistrict[$row->district_name]['total'] += $total;
                }
            }

            foreach ($scoreYears as $year) {
                $calculator = $scoreCalculators[$year];
                // 2000 (was 500) - see the matching note in
                // AwarenessPassResolver::passFailCountsByColumn(): compute()
                // is CPU-bound, so a bigger chunk mainly cuts query
                // round-trips, not the real cost.
                (clone $statsQueryAll)->where('fiscal_year', $year)
                    ->select(['province_name', 'district_name', 'survey_data', 'id'])
                    ->chunkById(2000, function ($rows) use (&$byProvince, &$byDistrict, $calculator) {
                        foreach ($rows as $row) {
                            $score = $calculator->compute($row);
                            $isPass = $score && $score['is_pass'];

                            $province = $row->province_name;
                            $byProvince[$province] = $byProvince[$province] ?? ['pass' => 0, 'total' => 0];
                            $byProvince[$province]['total']++;
                            if ($isPass) {
                                $byProvince[$province]['pass']++;
                            }

                            $district = $row->district_name;
                            $byDistrict[$district] = $byDistrict[$district] ?? ['pass' => 0, 'total' => 0];
                            $byDistrict[$district]['total']++;
                            if ($isPass) {
                                $byDistrict[$district]['pass']++;
                            }
                        }
                    });
            }

            return [collect($byProvince), collect($byDistrict)];
        };

        [$passFailByProvinceRaw, $districtStatsRaw] = $buildPassFailStats();

        $passFailByProvince = $passFailByProvinceRaw->map(function ($row) {
            return ['pass' => $row['pass'], 'fail' => $row['total'] - $row['pass']];
        });

        $districtStats = $districtStatsRaw->map(function ($row) {
            return $row['total'] > 0 ? round(($row['pass'] / $row['total']) * 100, 1) : 0;
        })->sortDesc();

        $hasPendingCriteria = (clone $statsQueryAll)->whereNotIn('fiscal_year', $yearsWithCriteria->all())->count() > 0;

        // Chart 4: the 4 fixed breakdown panels. Which question goes in
        // which panel (1-4, or none) is set per fiscal year from the same
        // settings screen (SurveyYearMapping::dashboard_panel) - this just
        // reads whatever's configured and charts it, so any fiscal year
        // (including one that doesn't exist yet) works without a code
        // change here.
        $statsForPanel = function (int $panel) use ($statsQueryAll, $effectiveFiscalYear) {
            $mappingsQuery = SurveyYearMapping::where('dashboard_panel', $panel);

            // When a specific fiscal year is selected in the filter above,
            // only chart THAT year's own questions for this panel - without
            // this, every year ever assigned to this panel got pulled in
            // regardless of the filter, and each one from a year other than
            // the selected one produced an empty "0 respondents" row (its
            // sub-query below is scoped to the selected year via
            // $statsQueryAll, which never matches that other year's rows) -
            // extra blank categories with no data, mismatched against
            // "ตั้งค่าแดชบอร์ด"'s per-year question count. Left unfiltered for
            // "ทั้งหมด" (every year at once) so same-wording questions from
            // different years still merge into one bar as intended below.
            if ($effectiveFiscalYear !== '') {
                $mappingsQuery->where('fiscal_year', $effectiveFiscalYear);
            }

            $mappings = $mappingsQuery
                ->orderBy('sort_order')
                ->get(['fiscal_year', 'question_key', 'question_label', 'chart_polarity', 'chart_polarity_values']);

            if ($mappings->isEmpty()) {
                return ['data' => [], 'polarity' => [], 'extremeValues' => []];
            }

            $labelByBranch = [];
            $polarityByBranch = [];
            $extremeValuesByBranch = [];
            $subQueries = [];
            $i = 0;
            foreach ($mappings as $m) {
                $branch = 'p' . $i++;
                $labelByBranch[$branch] = \App\Support\DefaultQuestionPanels::shortLabelFor($m->question_label ?: $m->question_key);
                $polarityByBranch[$branch] = $m->chart_polarity;
                $extremeValuesByBranch[$branch] = $m->chart_polarity_values;
                $expr = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$m->question_key}'))";
                $subQueries[] = (clone $statsQueryAll)->where('fiscal_year', $m->fiscal_year)
                    ->select([
                        \DB::raw("'" . $branch . "' as field_name"),
                        \DB::raw("{$expr} as field_value"),
                        \DB::raw('COUNT(*) as aggregate_count'),
                    ])->groupBy(\DB::raw($expr));
            }

            $combined = array_shift($subQueries);
            foreach ($subQueries as $sub) {
                $combined->unionAll($sub);
            }
            $rowsByBranch = $combined->get()->groupBy('field_name');

            // Same question label used by more than one fiscal year (e.g.
            // wording that hasn't changed year to year) is merged into one
            // bar instead of showing once per year. The admin-set direction
            // (chart_polarity - see "ตั้งค่าแดชบอร์ด") likewise merges: the
            // first year that has one explicitly set wins for the merged
            // label, so one still-unconfigured year doesn't erase another
            // year's explicit choice.
            $result = [];
            $polarityByLabel = [];
            $extremeValuesByLabel = [];
            foreach ($labelByBranch as $branch => $label) {
                $dist = ($rowsByBranch[$branch] ?? collect())
                    ->mapWithKeys(fn ($r) => [$r->field_value => (int) $r->aggregate_count]);
                if (isset($result[$label])) {
                    foreach ($dist as $value => $count) {
                        $result[$label][$value] = ($result[$label][$value] ?? 0) + $count;
                    }
                } else {
                    $result[$label] = $dist->toArray();
                }
                if (empty($polarityByLabel[$label]) && !empty($polarityByBranch[$branch])) {
                    $polarityByLabel[$label] = $polarityByBranch[$branch];
                }
                if (empty($extremeValuesByLabel[$label]) && !empty($extremeValuesByBranch[$branch])) {
                    $extremeValuesByLabel[$label] = $extremeValuesByBranch[$branch];
                }
            }
            return ['data' => $result, 'polarity' => $polarityByLabel, 'extremeValues' => $extremeValuesByLabel];
        };

        $panel1 = $statsForPanel(1);
        $panel2 = $statsForPanel(2);
        $panel3 = $statsForPanel(3);
        $panel4 = $statsForPanel(4);
        $statsPanel1 = $panel1['data'];
        $statsPanel2 = $panel2['data'];
        $statsPanel3 = $panel3['data'];
        $statsPanel4 = $panel4['data'];
        $polarityPanel1 = $panel1['polarity'];
        $polarityPanel2 = $panel2['polarity'];
        $polarityPanel3 = $panel3['polarity'];
        $polarityPanel4 = $panel4['polarity'];
        $extremeValuesPanel1 = $panel1['extremeValues'];
        $extremeValuesPanel2 = $panel2['extremeValues'];
        $extremeValuesPanel3 = $panel3['extremeValues'];
        $extremeValuesPanel4 = $panel4['extremeValues'];

            return compact(
                'provinceStats', 'genderStats', 'ageStats', 'educationStats',
                'passFailByProvince', 'districtStats', 'hasPendingCriteria',
                'statsPanel1', 'statsPanel2', 'statsPanel3', 'statsPanel4',
                'polarityPanel1', 'polarityPanel2', 'polarityPanel3', 'polarityPanel4',
                'extremeValuesPanel1', 'extremeValuesPanel2', 'extremeValuesPanel3', 'extremeValuesPanel4'
            );
        });

        // Every view variable computed inside the cached closure above is
        // pulled back out into this method's own scope under its original
        // name (EXTR_SKIP leaves $dashboardCacheKey/$dashboardData/$request/
        // etc. alone rather than overwriting them, though none of the
        // returned keys collide with those anyway).
        extract($dashboardData, EXTR_SKIP);

        // Dropdown Data
        //
        // $provinces is the full, unfiltered distinct list - it only
        // changes when a new province's data is imported, not on every
        // request - so it's cached separately (and longer) from the main
        // filtered dashboard bundle above, which is keyed by fiscal_year/
        // province/district and would otherwise recompute this same
        // whole-table scan on every single page load regardless of which
        // filters are selected.
        $years = FiscalYear::selectableYearsFor('awareness', $allFiscalYears);
        $provinces = Cache::remember('awareness_provinces_list', now()->addMinutes(30), function () {
            return SodiumSurvey::distinct()->orderBy('province_name')->pluck('province_name');
        });
        $districts = SodiumSurvey::when($request->filled('province'), function ($q) use ($request) {
            return $q->where('province_name', $request->province);
        })->distinct()->orderBy('district_name')->pluck('district_name');

        return view('pages.awareness', compact(
            'provinceStats',
            'passFailByProvince',
            'districtStats',
            'genderStats',
            'ageStats',
            'educationStats',
            'statsPanel1',
            'statsPanel2',
            'statsPanel3',
            'statsPanel4',
            'polarityPanel1',
            'polarityPanel2',
            'polarityPanel3',
            'polarityPanel4',
            'extremeValuesPanel1',
            'extremeValuesPanel2',
            'extremeValuesPanel3',
            'extremeValuesPanel4',
            'years',
            'provinces',
            'districts',
            'hasPendingCriteria',
            'effectiveFiscalYear'
        ));
    }

    public function foodSurvey()
    {
        return view('pages.food-survey');
    }

    public function adminReducedSodiumMenu(Request $request)
    {
        return view('pages.admin-reduced-sodium-menu');
    }

    public function adminReducedSodiumProducts(Request $request)
    {
        return view('pages.admin-reduced-sodium-products');
    }

    public function adminNewHtCases(Request $request)
    {
        return view('pages.admin-new-ht-cases');
    }

    public function reducedSodiumMenu(Request $request)
    {
        $user = auth()->user();
        $isLocked = $request->get('org_lock') == 1 && $user;
        $lockedOrgName = null;
        $lockedProvinceName = null;

        if ($isLocked) {
            // Replicate Organization Discovery Logic from AdminController
            $provinceName = $user->province ? $user->province->province_name : null;
            $districtName = $user->district ? $user->district->district_name : null;

            // Always capture province for locking if available
            $lockedProvinceName = $provinceName;

            if ($user->User_rank_id == 2) {
                // SSJ: Lock to Province only
                $lockedProvinceName = $provinceName;
            } elseif ($user->User_rank_id == 1) {
                // Rank 1: Super Admin - No locking
                $lockedProvinceName = null;
                $lockedOrgName = null;
            } elseif ($user->User_rank_id == 3) {
                // SSO
                $lockedOrgName = 'สำนักงานสาธารณสุขอำเภอ' . ($districtName ?? '');
            } elseif ($user->User_rank_id == 4) {
                // Rph.Sot.
                $lockedOrgName = 'รพ.สต.' . $user->Con_name;
            } elseif ($user->User_rank_id == 5) {
                // Hospital
                $lockedOrgName = $user->hospital->hos_name ?? $user->Con_name;
            } else {
                // Others: Lock to Org
                $lockedOrgName = $user->Con_name;
            }
        }

        $query = ReducedSodiumMenu::query();

        // 1. Force Lock if requested
        if ($isLocked) {
            if ($lockedProvinceName) {
                $query->where('province', $lockedProvinceName);
            }
            if ($lockedOrgName) {
                $query->where('org_name', $lockedOrgName);
            }
        }

        // 2. Apply Additional Filters
        if ($request->filled('fiscal_year')) {
            $query->where('year', $request->fiscal_year);
        }

        // Non-locked users can filter by province/district
        if (!$isLocked) {
            if ($request->filled('province') && $request->province !== 'ทั้งหมด') {
                $query->where('province', $request->province);
            }
            if ($request->filled('district') && $request->district !== 'ทั้งหมด') {
                $query->where('district', $request->district);
            }
        }

        // Sale Location Filter (Categories vs Specific Org Names)
        if ($request->filled('org_name') && $request->org_name !== 'ทั้งหมด') {
            $org = $request->org_name;
            $searchFields = ['org_name', 'org_type', 'kitchen_type', 'agency'];

            // Define hierarchy patterns for consistency
            $categories = [
                'โรงพยาบาล' => ['โรงพยาบาล', 'รพ.', 'โรงครัวรพ.'],
                'ตลาด' => ['ตลาด'],
                'โรงเรียน' => ['โรงเรียน', 'รร.', 'เรียน', 'โรงครัวรร.', 'วิทยาลัย', 'มหาลัย'],
                'ร้านอาหารในชุมชน' => ['ร้านอาหาร', 'ร้าน', 'ครัว', 'ป้า', 'ลุง', 'โภชนาการ', 'กุ๊ก'],
            ];

            if ($org === 'อื่นๆ') {
                // Exclude ALL predefined categories
                foreach ($categories as $patterns) {
                    $query->whereNot(function ($q) use ($patterns, $searchFields) {
                        foreach ($searchFields as $field) {
                            foreach ($patterns as $p) {
                                $q->orWhere($field, 'LIKE', '%' . $p . '%');
                            }
                        }
                    });
                }
            } elseif (isset($categories[$org])) {
                // Must match current category patterns
                $query->where(function ($q) use ($categories, $org, $searchFields) {
                    foreach ($searchFields as $field) {
                        foreach ($categories[$org] as $p) {
                            $q->orWhere($field, 'LIKE', '%' . $p . '%');
                        }
                    }
                });

                // And must NOT match any category HIGHER in the hierarchy (to ensure exclusivity)
                foreach ($categories as $catName => $patterns) {
                    if ($catName === $org)
                        break;
                    $query->whereNot(function ($q) use ($patterns, $searchFields) {
                        foreach ($searchFields as $field) {
                            foreach ($patterns as $p) {
                                $q->orWhere($field, 'LIKE', '%' . $p . '%');
                            }
                        }
                    });
                }
            } else {
                // Specific organization name search
                $query->where(function ($q) use ($org, $searchFields) {
                    foreach ($searchFields as $field) {
                        $q->orWhere($field, 'LIKE', '%' . $org . '%');
                    }
                });
            }
        }

        $allData = $query->get();

        // 3. Prepare Chart Data
        // Paired average, not two independently-filtered ones: Collection::
        // avg() silently drops ITS OWN null rows, so avg('sodium_before')
        // and avg('sodium_after') taken separately can each be built from a
        // DIFFERENT subset of menus (e.g. a menu whose "หลัง" hasn't been
        // re-tested yet still counts toward the "ก่อน" average, and vice
        // versa) - the resulting "% ลดลง" wasn't a real before/after
        // comparison for any actual set of menus. A recorded value of
        // exactly 0.0 is treated the same as "ยังไม่ได้วัด" here too
        // (confirmed with the admin) - a real re-tested dish essentially
        // never comes back at EXACTLY 0.00 mg sodium, and both admin
        // data-entry paths (a blank cell in the Excel import, a blank
        // number field on the manual "แก้ไขเมนู" form) happen to store that
        // as a literal 0 rather than leaving the column null - so a
        // handful of never-retested menus were being counted as a full
        // 100% reduction and dragging both this KPI and the ก่อน/หลัง chart
        // below down with them.
        $measuredMenus = $allData->filter(fn($m) => $m->sodium_before > 0 && $m->sodium_after > 0);

        $barChartData = [
            'before' => round($measuredMenus->avg('sodium_before') ?: 0, 1),
            'after' => round($measuredMenus->avg('sodium_after') ?: 0, 1),
        ];

        // Per-meal sodium evaluation ("ระดับการประเมิน"): the same four tiers
        // shown on each menu's badge in the table below, judged on
        // sodium_after (the dish as it stands now, post-reformulation).
        // Built from $measuredMenus - NOT $allData - for the same reason
        // documented above: an unmeasured menu stores sodium_after as a
        // literal 0, which is < 600 and would silently count as a "ดีมาก"
        // pass if it weren't excluded first.
        $evaluationTier = function ($sodiumAfter) {
            if ($sodiumAfter < 600) return 'ดีมาก';
            if ($sodiumAfter <= 800) return 'ดี';
            if ($sodiumAfter <= 1000) return 'ปานกลาง';
            return 'ควรปรับปรุง';
        };
        $evaluatedCount = $measuredMenus->count();
        $bestTierCount = $measuredMenus->filter(fn($m) => $evaluationTier($m->sodium_after) === 'ดีมาก')->count();
        $highRiskCount = $measuredMenus->filter(fn($m) => $evaluationTier($m->sodium_after) === 'ควรปรับปรุง')->count();

        $sodiumEvalSummary = [
            'evaluated_count' => $evaluatedCount,
            'best_count' => $bestTierCount,
            'best_percent' => $evaluatedCount > 0 ? round($bestTierCount / $evaluatedCount * 100, 1) : 0,
            'high_risk_count' => $highRiskCount,
        ];

        $pieChartData = $allData->groupBy(function ($item) {
            $kt = $item->kitchen_type;
            $on = $item->org_name;
            $ag = $item->agency;

            $checkFields = array_filter([$kt, $on, $ag]);

            $categories = [
                'โรงพยาบาล' => ['โรงพยาบาล', 'รพ.', 'โรงครัวรพ.'],
                'ตลาด' => ['ตลาด'],
                'โรงเรียน' => ['โรงเรียน', 'รร.', 'เรียน', 'โรงครัวรร.', 'วิทยาลัย', 'มหาลัย'],
                'ร้านอาหารในชุมชน' => ['ร้านอาหาร', 'ร้าน', 'ครัว', 'ป้า', 'ลุง', 'โภชนาการ', 'กุ๊ก'],
            ];

            foreach ($categories as $catName => $patterns) {
                foreach ($checkFields as $val) {
                    foreach ($patterns as $p) {
                        if (mb_strpos($val, $p) !== false)
                            return $catName;
                    }
                }
            }

            return 'อื่นๆ';
        })->map->count();

        // 4. Table Data (Paginated)
        $validSortColumns = ['sodium_before', 'sodium_after', 'update_date'];
        $sort = $request->get('sort', 'update_date');
        $direction = $request->get('direction', 'desc');

        if (!in_array($sort, $validSortColumns)) {
            $sort = 'update_date';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $menus = $query->orderBy($sort, $direction)->orderBy('id', 'desc')->paginate(10)->appends($request->query());

        // 5. Dropdown Options (Scoped by organization if locked)
        $dropdownQuery = ReducedSodiumMenu::query();
        if ($isLocked) {
            if ($lockedProvinceName) {
                $dropdownQuery->where('province', $lockedProvinceName);
            }
            if ($lockedOrgName) {
                $dropdownQuery->where('org_name', $lockedOrgName);
            }
        }

        $years = FiscalYear::selectableYearsFor(
            'sodium_menus',
            (clone $dropdownQuery)->whereNotNull('year')->where('year', '!=', '')->distinct()->pluck('year')->map(fn($y) => trim($y))
        );
        $provinces = (clone $dropdownQuery)->distinct()->orderBy('province')->pluck('province');

        $activeProvince = $request->get('province');

        $districts = (clone $dropdownQuery)->when($activeProvince && $activeProvince !== 'ทั้งหมด', function ($q) use ($activeProvince) {
            return $q->where('province', $activeProvince);
        })->distinct()->orderBy('district')->pluck('district');

        if (!$isLocked || ($user && $user->User_rank_id <= 2)) {
            $orgNames = ['ตลาด', 'ร้านอาหารในชุมชน', 'โรงพยาบาล', 'โรงเรียน', 'อื่นๆ'];
        } else {
            $orgNames = (clone $dropdownQuery)->when($activeProvince && $activeProvince !== 'ทั้งหมด', function ($q) use ($activeProvince) {
                return $q->where('province', $activeProvince);
            })->when($request->filled('district') && $request->district !== 'ทั้งหมด', function ($q) use ($request) {
                return $q->where('district', $request->district);
            })->distinct()->orderBy('org_name')->pluck('org_name');
        }

        // 6. Map Data
        $mapData = $allData->groupBy('province')->map->count();

        $hideLayout = $request->has('iframe');

        return view('pages.reduced-sodium-menu', compact(
            'menus',
            'barChartData',
            'pieChartData',
            'years',
            'provinces',
            'districts',
            'orgNames',
            'mapData',
            'hideLayout',
            'isLocked',
            'lockedOrgName',
            'lockedProvinceName',
            'sodiumEvalSummary'
        ));
    }

    public function reducedSodiumProducts(Request $request)
    {
        $user = auth()->user();
        $isLocked = $request->get('org_lock') == 1 && $user;
        $lockedProvinceName = null;
        $hideLayout = $request->get('iframe') == 1 || $request->get('hideLayout') == 1;

        if ($isLocked) {
            if ($user->User_rank_id == 2) {
                $lockedProvinceName = $user->province->province_name ?? null;
            } elseif ($user->User_rank_id == 1) {
                $lockedProvinceName = null;
            } else {
                $lockedProvinceName = $user->province->province_name ?? null;
            }
        }

        $query = \App\Models\ReducedSodiumProduct::with(['user.province', 'user.district']);

        // Products may carry their own province_name (set once per batch by
        // the "นำเข้า Excel" bulk importer - see ReducedSodiumProductImport)
        // OR only be reachable through the owning user's own province, for
        // rows added the old way one at a time by a province-level user.
        // Every province match here needs to check both sources - relying
        // on user.province alone (as this used to) attributes every
        // imported row to whichever admin account ran the import instead of
        // the จังหวัด actually recorded on the row, which is what caused
        // products from other provinces to all show up lumped under one
        // province. Mirrors AdminController::scopeProductsByProvinceName().
        $scopeByProvinceName = function ($q, string $provinceName) {
            $q->where(function ($q2) use ($provinceName) {
                $q2->where('province_name', $provinceName)
                    ->orWhereHas('user.province', function ($q3) use ($provinceName) {
                        $q3->where('province_name', $provinceName);
                    });
            });
        };

        // 1. Force Lock if requested
        if ($isLocked && $lockedProvinceName) {
            $scopeByProvinceName($query, $lockedProvinceName);
        }

        // 2. Apply Filters
        // ปีงบประมาณ ("fiscal_year" here) is always submitted as the Buddhist
        // year the dropdown/modal work in (see $years below). Rows may carry
        // their own real fiscal_year column (already Buddhist - set by the
        // admin form / "นำเข้า Excel" importer) OR, for older rows added
        // before that column existed, only be dateable via update_date's
        // (Gregorian) year. Matching only against update_date, as before,
        // compared a Buddhist year straight against a Gregorian one and so
        // never matched any row with a real fiscal_year set - mirrors
        // AdminController::scopeProductsByFiscalYear() so the public page and
        // the admin dashboard agree on the same rows for the same year.
        if ($request->filled('fiscal_year')) {
            $yearBE = (int) $request->fiscal_year;
            $yearAD = $yearBE - 543;
            $query->where(function ($q) use ($yearBE, $yearAD) {
                $q->where('fiscal_year', $yearBE)
                    ->orWhere(function ($q2) use ($yearAD) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('fiscal_year')->orWhere('fiscal_year', 0);
                        })->whereYear('update_date', $yearAD);
                    });
            });
        }
        if ($request->filled('province') && $request->province !== 'ทั้งหมด') {
            $scopeByProvinceName($query, $request->province);
        }
        if ($request->filled('district') && $request->district !== 'ทั้งหมด') {
            $query->whereHas('user.district', function ($q) use ($request) {
                $q->where('district_name', $request->district);
            });
        }
        if ($request->filled('type') && $request->type !== 'ทั้งหมด') {
            $query->where('product_type', $request->type);
        }

        $allData = $query->get();

        // 3. Prepare Chart Data
        $typeChartData = $allData->groupBy('product_type')->map->count();
        $standardChartData = $allData->groupBy(function ($p) {
            return $p->standard_certification ?: 'ยังไม่ได้รับรอง';
        })->map->count();

        $mapData = $allData->groupBy(function ($item) {
            // Prefer the product's own recorded province_name (set per
            // import batch) - only fall back to the owning user's province
            // for older rows added one at a time before that column
            // existed, where province_name is still null.
            return $item->province_name ?: ($item->user->province->province_name ?? 'ไม่ระบุ');
        })->map->count();

        // 4. Table Data
        $validSortColumns = ['sodium_amount', 'update_date'];
        $sort = $request->get('sort', 'update_date');
        $direction = $request->get('direction', 'desc');

        if (!in_array($sort, $validSortColumns)) {
            $sort = 'update_date';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $products = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        // 5. Dropdown Options
        $dropdownQuery = \App\Models\ReducedSodiumProduct::query();
        if ($isLocked && $lockedProvinceName) {
            $scopeByProvinceName($dropdownQuery, $lockedProvinceName);
        }

        // ปีงบประมาณ options - rows with a real fiscal_year contribute it
        // directly; older rows only have update_date, so its (Gregorian)
        // year is converted to Buddhist here - matching the same module's
        // admin dashboard (AdminController::reducedSodiumProducts()) so the
        // two never disagree, plus whatever an admin enabled/hid for this
        // module via "จัดการปีงบประมาณ".
        $fiscalYearValues = (clone $dropdownQuery)->whereNotNull('fiscal_year')
            ->where('fiscal_year', '!=', 0)
            ->distinct()
            ->pluck('fiscal_year');
        $legacyYearValues = (clone $dropdownQuery)->where(function ($q) {
                $q->whereNull('fiscal_year')->orWhere('fiscal_year', 0);
            })
            ->selectRaw(DbCompat::yearExpr(DB::connection()->getDriverName(), 'update_date') . ' as year')
            ->distinct()
            ->pluck('year')
            ->filter()
            ->map(fn($y) => (int) $y + 543);
        $years = FiscalYear::selectableYearsFor('sodium_products', $fiscalYearValues->merge($legacyYearValues));

        $provincesQuery = \App\Models\Province::whereIn('province_name', ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร']);
        if ($isLocked && $lockedProvinceName) {
            $provincesQuery->where('province_name', $lockedProvinceName);
        }
        $provinces = $provincesQuery->pluck('province_name');

        $activeProvince = $request->get('province');

        $districts = [];
        if ($activeProvince && $activeProvince !== 'ทั้งหมด') {
            $districts = \App\Models\District::whereHas('province', function ($q) use ($activeProvince) {
                $q->where('province_name', $activeProvince);
            })->orderBy('district_name')->pluck('district_name');
        }

        $types = (clone $dropdownQuery)->distinct()->whereNotNull('product_type')->pluck('product_type');

        return view('pages.reduced-sodium-products', compact(
            'products',
            'typeChartData',
            'standardChartData',
            'mapData',
            'years',
            'provinces',
            'districts',
            'types',
            'hideLayout',
            'isLocked',
            'lockedProvinceName'
        ));
    }

    public function kidneyDHB()
    {
        return view('pages.kidney-dhb');
    }

    public function newHTCases(Request $request)
    {
        $user = auth()->user();
        // Same "embed this public dashboard inside the admin panel, locked
        // to the viewing user's own province" pattern as reducedSodiumMenu()
        // / reducedSodiumProducts() above (see admin-new-ht-cases.blade.php,
        // opened via ?org_lock=1&iframe=1). Rank 1 (สคร.) is never locked -
        // they oversee every province, same as the other two dashboards.
        $isLocked = $request->get('org_lock') == 1 && $user;
        $hideLayout = $request->get('iframe') == 1 || $request->get('hideLayout') == 1;
        $lockedProvinceName = null;
        if ($isLocked && $user->User_rank_id != 1) {
            $lockedProvinceName = $user->province ? $user->province->province_name : null;
        }

        $selectedYear = $request->get('year');
        $selectedProvinceName = $lockedProvinceName ?: $request->get('province');
        $selectedDistrict = $request->get('district');
        // Quarter-level filter: 'ทั้งหมด' (default) shows the whole fiscal
        // year as before; otherwise this holds one of the q1..q4 keys
        // defined below ($quarters) and narrows the province/district/map
        // figures down to that quarter's 3-month case total, while the
        // 12-month trend chart itself keeps showing the whole year.
        $selectedQuarter = $request->get('quarter', 'ทั้งหมด');

        // Defaults if not provided (Only on first load without query params)
        if (!$request->has('year') && !$request->has('province') && !$lockedProvinceName) {
            $selectedYear = 2569;
            $selectedProvinceName = 'ทั้งหมด';
        }

        $query = Hi::query();

        // Filters
        if ($selectedYear && $selectedYear != 'ทั้งหมด') {
            $query->where('year', $selectedYear);
        }

        if ($selectedProvinceName && $selectedProvinceName != 'ทั้งหมด') {
            $province = Province::where('province_name', $selectedProvinceName)->first();
            if ($province) {
                $query->where('Province_id', $province->province_id);
            }
        }

        if ($selectedDistrict && $selectedDistrict != 'ทั้งหมด') {
            $query->where('District_name', $selectedDistrict);
        }

        $allData = $query->get();

        // Monthly Trends
        $months = [
            'm10_oct' => 'ต.ค.',
            'm11_nov' => 'พ.ย.',
            'm12_dec' => 'ธ.ค.',
            'm01_jan' => 'ม.ค.',
            'm02_feb' => 'ก.พ.',
            'm03_mar' => 'มี.ค.',
            'm04_apr' => 'เม.ย.',
            'm05_may' => 'พ.ค.',
            'm06_jun' => 'มิ.ย.',
            'm07_jul' => 'ก.ค.',
            'm08_aug' => 'ส.ค.',
            'm09_sep' => 'ก.ย.'
        ];

        // Quarters group the same fiscal months above into the 4 standard
        // ไตรมาส. $sumForSelectedPeriod below sums a group's 3 month columns
        // when a quarter is selected, or falls back to the annual 'total_a'
        // column when the filter is left at 'ทั้งหมด' — reused identically
        // by the province/district/map totals further down.
        $quarters = [
            'q1' => ['label' => 'ไตรมาส 1 (ต.ค.-ธ.ค.)', 'months' => ['m10_oct', 'm11_nov', 'm12_dec']],
            'q2' => ['label' => 'ไตรมาส 2 (ม.ค.-มี.ค.)', 'months' => ['m01_jan', 'm02_feb', 'm03_mar']],
            'q3' => ['label' => 'ไตรมาส 3 (เม.ย.-มิ.ย.)', 'months' => ['m04_apr', 'm05_may', 'm06_jun']],
            'q4' => ['label' => 'ไตรมาส 4 (ก.ค.-ก.ย.)', 'months' => ['m07_jul', 'm08_aug', 'm09_sep']],
        ];

        $sumForSelectedPeriod = function ($items) use ($selectedQuarter, $quarters) {
            if ($selectedQuarter !== 'ทั้งหมด' && array_key_exists($selectedQuarter, $quarters)) {
                $cols = $quarters[$selectedQuarter]['months'];
                return collect($cols)->sum(fn($col) => $items->sum($col));
            }
            return $items->sum('total_a');
        };

        $monthlyTrend = [];
        $cumulativeTrend = [];
        $totalSum = 0;
        foreach ($months as $key => $label) {
            $monthSum = $allData->sum($key);
            $totalSum += $monthSum;
            $monthlyTrend[$label] = $monthSum;
            $cumulativeTrend[$label] = $totalSum;
        }

        // Provincial Stats
        //
        // Province name lookup used to run one query PER GROUP inside
        // each ->map() below (Province::where('province_id', ...)->value(...))
        // - fine with 5 provinces, but it re-queries the same handful of
        // rows over and over on every request. Fetched once here instead,
        // into an in-memory province_id => province_name map reused by
        // both the provincial and district stats below - identical
        // results, just without the repeated round-trips.
        $provinceNamesById = Province::whereIn('province_id', $allData->pluck('Province_id')->unique())
            ->pluck('province_name', 'province_id');

        $provinceTotals = $allData->groupBy('Province_id')->map(function ($items) use ($sumForSelectedPeriod, $provinceNamesById) {
            $provinceName = $provinceNamesById->get($items->first()->Province_id);
            $caseCount = $sumForSelectedPeriod($items);
            $targetSum = $items->sum('target_b');
            return [
                'name' => $provinceName,
                'total_a' => $caseCount,
                'target_b' => $targetSum,
                'rate' => $targetSum > 0 ? ($caseCount / $targetSum) * 100000 : 0
            ];
        })->values();

        // District Stats
        // Sorted by rate descending so the horizontal bar chart (which renders
        // the first array item at the top) reads lowest-to-highest from
        // bottom to top. Sorting here (once, server-side) also keeps the
        // chart's category labels and bar values/colors in the same order —
        // previously the labels were re-sorted client-side in the blade view
        // while the bar data/colors were emitted separately in original
        // (unsorted) order, so labels and bars could end up mismatched.
        $districtTotals = $allData->groupBy('District_name')->map(function ($items) use ($sumForSelectedPeriod, $provinceNamesById) {
            $provinceName = $provinceNamesById->get($items->first()->Province_id);
            $caseCount = $sumForSelectedPeriod($items);
            $targetSum = $items->sum('target_b');
            return [
                'name' => $items->first()->District_name,
                'province_name' => $provinceName,
                'total_a' => $caseCount,
                'target_b' => $targetSum,
                'rate' => $targetSum > 0 ? ($caseCount / $targetSum) * 100000 : 0
            ];
        })->sortByDesc('rate')->values();

        // Map Data — always covers all 5 provinces in Health Region 10,
        // regardless of the province/district filters above (only the year
        // filter still applies), so the province map gives a full regional
        // overview at a glance even when the page is currently drilled down
        // into one province/district. Same convention as the province map
        // on the sodium-menu/products dashboards.
        $mapProvinceNames = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];
        $mapQuery = Hi::query();
        if ($selectedYear && $selectedYear != 'ทั้งหมด') {
            $mapQuery->where('year', $selectedYear);
        }
        $mapAllData = $mapQuery->get()->groupBy('Province_id');
        // One query for all 5 provinces instead of one PER province inside
        // the ->map() below (this runs unconditionally on every page view,
        // regardless of filters - see the comment above on why the map
        // always covers all 5 provinces).
        $mapProvinceIdsByName = Province::whereIn('province_name', $mapProvinceNames)->pluck('province_id', 'province_name');
        $mapProvinceTotals = collect($mapProvinceNames)->map(function ($provName) use ($mapAllData, $sumForSelectedPeriod, $mapProvinceIdsByName) {
            $provinceId = $mapProvinceIdsByName->get($provName);
            $items = $provinceId ? ($mapAllData->get($provinceId) ?? collect()) : collect();
            $caseCount = $sumForSelectedPeriod($items);
            $targetB = $items->sum('target_b');
            return [
                'name' => $provName,
                'total_a' => $caseCount,
                'target_b' => $targetB,
                'rate' => $targetB > 0 ? ($caseCount / $targetB) * 100000 : 0,
            ];
        })->values();

        // Filter Options
        $years = FiscalYear::selectableYearsFor('hi', Hi::whereNotNull('year')->distinct()->pluck('year'));
        $provincesQuery = Province::whereIn('province_name', ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร']);
        if ($lockedProvinceName) {
            $provincesQuery->where('province_name', $lockedProvinceName);
        }
        $provinces = $provincesQuery->get();

        $districts = [];
        if ($selectedProvinceName && $selectedProvinceName != 'ทั้งหมด') {
            $province = Province::where('province_name', $selectedProvinceName)->first();
            if ($province) {
                $districts = Hi::where('Province_id', $province->province_id)->distinct()->pluck('District_name');
            }
        }

        $quarterOptions = collect($quarters)->map(fn($q) => $q['label'])->all();

        return view('pages.new-ht-cases', compact(
            'monthlyTrend',
            'cumulativeTrend',
            'provinceTotals',
            'districtTotals',
            'mapProvinceTotals',
            'years',
            'provinces',
            'districts',
            'quarterOptions',
            'selectedYear',
            'selectedProvinceName',
            'selectedDistrict',
            'selectedQuarter',
            'hideLayout',
            'isLocked',
            'lockedProvinceName'
        ));
    }



    public function staff()
    {
        $provinces = Province::orderBy('province_name', 'asc')->get();
        return view('pages.staff', compact('provinces'));
    }

    public function reportProgress()
    {
        return view('pages.report-progress');
    }
}