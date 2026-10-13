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
use App\Models\OperatingAreaPin;
use App\Models\FiscalYear;
use App\Models\AwarenessPassSetting;
use App\Services\AwarenessPassResolver;
use App\Services\AwarenessScoreCalculator;
use App\Services\AwarenessYearGrid;
use App\Support\DbCompat;

class MainController extends Controller
{
    /**
     * Display the Kidney DHB presentation/report page.
     */
    /**
     * แคชข้อมูลของหน้ารายงาน (ไม่ใช่ HTML) ตามตัวกรอง + "ตราประทับ" ของตารางที่เกี่ยวข้อง (จำนวนแถว + updated_at ล่าสุด)
     * ข้อมูลเปลี่ยนเมื่อไร คีย์เปลี่ยนทันที ไม่ต้องรอ TTL; TTL 10 นาทีเป็นแค่ตาข่ายนิรภัย
     * $perUser = true เมื่อผลขึ้นกับผู้ใช้ที่ล็อกอิน (โหมดฝังในหน้าแอดมิน org_lock=1)
     */
    private function cachedPageData(string $page, Request $request, array $models, \Closure $build, bool $perUser = false): array
    {
        $query = $request->query();
        ksort($query);
        $user = auth()->user();
        $key = 'page_data_v11:' . $page . ':' . md5(json_encode([
            $query,
            ($perUser && $user) ? [$user->id, $user->User_rank_id] : null,
            array_map(fn ($m) => $this->tableStamp($m), $models),
        ]));

        return Cache::remember($key, now()->addMinutes(10), $build);
    }

    /** "จำนวนแถว|updated_at ล่าสุด" ของตารางของโมเดล - ใช้เป็นเวอร์ชันของข้อมูล (หาไม่ได้ -> เปลี่ยนทุกนาที) */
    private function tableStamp(string $modelClass): string
    {
        static $memo = [];
        if (isset($memo[$modelClass])) {
            return $memo[$modelClass];
        }
        try {
            $row = DB::table((new $modelClass)->getTable())->selectRaw('COUNT(*) as c, MAX(updated_at) as u')->first();

            return $memo[$modelClass] = ($row->c ?? 0) . '|' . ($row->u ?? '');
        } catch (\Throwable $e) {
            return $memo[$modelClass] = 'n' . floor(time() / 60);
        }
    }

    public function kidneyDHBReport(Request $request)
    {
        // ข้อมูลของหน้านี้เป็นฟังก์ชันของตัวกรอง (query string) + เนื้อหาตาราง - แคชไว้ตามนั้น
        // คีย์ผูกกับจำนวนแถว/เวลาแก้ไขล่าสุดของตารางที่เกี่ยวข้อง จึงอัปเดตทันทีเมื่อข้อมูลเปลี่ยน
        $data = $this->cachedPageData('kidney-dhb-report', $request, [KidneyAssessment::class, OperatingAreaPin::class, User::class, FiscalYear::class], fn () => $this->kidneyDHBReportData($request), false);

        return view('pages.kidney-dhb-report', $data);
    }

    /**
     * "หมุดแสดงผล" ที่ SuperAdmin เพิ่มไว้ (บอกว่าพื้นที่นี้ดำเนินงาน พชอ.ไต โดยหน่วยงานใด) - ใช้วาดบนแผนที่เท่านั้น
     * ไม่นับรวมในตัวเลขสรุป/ความครอบคลุม/ตัวชี้วัด (ไม่มีรายงานให้นับความก้าวหน้า)
     */
    private function displayOnlyPinList(array $provinceNames, $fiscalYear = '', $province = null, $district = null): array
    {
        if (!OperatingAreaPin::hasDisplayColumns()) {
            return [];
        }

        $query = OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])
            ->where('display_only', true)
            ->whereHas('province', fn($q) => $q->whereIn('province_name', $provinceNames));
        if ($fiscalYear !== '' && $fiscalYear !== null) {
            $query->where('fiscal_year', $fiscalYear);
        }
        if ($province && !in_array($province, ['ทั้งหมด', 'all'], true)) {
            $query->whereHas('province', fn($q) => $q->where('province_name', $province));
        }
        if ($district && $district !== 'ทั้งหมด') {
            $query->whereHas('district', fn($q) => $q->where('district_name', $district));
        }

        return $query->get()->map(function ($pin) {
            $crumbs = array_filter([
                optional($pin->district)->district_name,
                optional($pin->tambon)->tambon_name,
                $pin->village_name_snapshot ?? optional($pin->village)->village_name,
            ]);
            return [
                'id' => $pin->id,
                'display_only' => true,
                'agency_label' => $pin->agency_label,
                'lat' => (float) $pin->latitude,
                'lng' => (float) $pin->longitude,
                'label' => $pin->label,
                'province_name' => optional($pin->province)->province_name,
                'breadcrumb' => implode(' › ', $crumbs),
                'fiscal_year' => $pin->fiscal_year,
            ];
        })->values()->all();
    }

    private function kidneyDHBReportData(Request $request): array
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

        // Individual registered "พื้นที่ดำเนินงาน" pins (บันทึกผ่าน
        // admin/operating-areas), for the pin-map card. Unlike $mapData
        // (aggregate per-province counts derived from KidneyAssessment's
        // own free-text operating_area field), these are the actual
        // lat/lng-pinned OperatingAreaPin records, shown individually and
        // colored per province - see oaProvinceColors in this view's own
        // script (reused from kidney-dhb-content.blade.php's established
        // pin-color convention). This is a public, unauthenticated page,
        // so there is no viewer-based scoping here (unlike
        // AdminController::operatingAreaPinsForUser()) - but it DOES
        // respect the same ตัวกรอง bar as the rest of the page now:
        // ปีงบประมาณ/จังหวัด/อำเภอ narrow the pins directly (columns on the
        // pin itself). ประเภทหน่วยงาน and ไตรมาส aren't columns on a pin
        // (pins are registered by admins of any rank, so filtering by the
        // creator's rank would just empty the map), so they don't hide any
        // pin - they narrow which reports count as an "answer" instead: a
        // pin's progress (and so the "ตอบแล้ว" counts built from it) only
        // looks at reports filed by users of the selected agency type /
        // for the selected quarter.
        $operatingAreaPinsQuery = OperatingAreaPin::reportable()->with(['province', 'district', 'tambon', 'village', 'assessments.user'])
            ->whereHas('province', function ($q) use ($targetProvinces) {
                $q->whereIn('province_name', $targetProvinces);
            });

        if ($fiscalYear !== '') {
            $operatingAreaPinsQuery->where('fiscal_year', $fiscalYear);
        }
        if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
            $operatingAreaPinsQuery->whereHas('province', function ($q) use ($selectedProvince) {
                $q->where('province_name', $selectedProvince);
            });
        }
        if ($selectedDistrict && $selectedDistrict !== 'ทั้งหมด') {
            $operatingAreaPinsQuery->whereHas('district', function ($q) use ($selectedDistrict) {
                $q->where('district_name', $selectedDistrict);
            });
        }

        $pinQuarter = ($request->filled('quarter') && $request->quarter !== 'ทั้งหมด') ? (int) $request->quarter : null;

        // รายงานที่ครอบคลุมหลายพื้นที่: พื้นที่อื่น (reporter_metadata['_extra_pin_ids']) ใช้ผลรายงานฉบับนั้นเป็นความก้าวหน้าของหมุดด้วย
        $extraAssessmentsByPin = [];
        foreach (KidneyAssessment::with('user')
            ->where('reporter_metadata', 'like', '%"' . \App\Support\KidneyAreaList::META_KEY . '"%')
            ->get() as $multiRep) {
            foreach (\App\Support\KidneyAreaList::extraPinIds($multiRep) as $extraPid) {
                $extraAssessmentsByPin[$extraPid][] = $multiRep;
            }
        }

        $operatingAreaPins = $operatingAreaPinsQuery->get()
            ->map(function ($pin) use ($pinQuarter, $selectedRank, $extraAssessmentsByPin) {
                $breadcrumbParts = array_filter([
                    optional($pin->district)->district_name,
                    optional($pin->tambon)->tambon_name,
                    $pin->village_name_snapshot ?? optional($pin->village)->village_name,
                ]);

                // "ความก้าวหน้า X/8 ข้อ" for this ONE registered pin - same
                // 8-milestone rule used everywhere else on this page
                // (category_1-7 each count as 1 if filled in ANY quarter
                // filed so far; 8.1/8.2/8.3 together count as the 8th,
                // satisfied by any single one of the three - see the
                // identical logic building $overviewMilestoneCounts
                // above), just scoped to the reports filed specifically
                // against this pin (operating_area_pin_id) instead of an
                // entire agency/province. Scoped to the pin's OWN
                // fiscal_year (not every report ever filed against it),
                // so a pin reused in a later year doesn't pull that
                // year's progress into this one's badge.
                $pinAssessments = $pin->assessments->where('fiscal_year', $pin->fiscal_year);
                if (!empty($extraAssessmentsByPin[$pin->id])) {
                    $pinAssessments = $pinAssessments->concat(
                        collect($extraAssessmentsByPin[$pin->id])->where('fiscal_year', $pin->fiscal_year)
                    )->unique('id');
                }
                if ($pinQuarter !== null) {
                    $pinAssessments = $pinAssessments->where('quarter', $pinQuarter);
                }
                if ($selectedRank && $selectedRank !== 'ทั้งหมด') {
                    $pinAssessments = $pinAssessments->filter(
                        fn($a) => $a->user && (string) $a->user->User_rank_id === (string) $selectedRank
                    );
                }
                // $doneItems = which of the 8 items this pin has done (1-7 by
                // number, '8' once any of 8.1/8.2/8.3 is filled) - kept next
                // to the count so the per-indicator KPI cards can tally the
                // very same items the progress badge counts.
                $doneItems = [];
                for ($i = 1; $i <= 7; $i++) {
                    $field = "category_$i";
                    if ($pinAssessments->contains(fn($a) => !empty($a->$field))) {
                        $doneItems[] = (string) $i;
                    }
                }
                if (
                    $pinAssessments->contains(fn($a) => !empty($a->category_8_1))
                    || $pinAssessments->contains(fn($a) => !empty($a->category_8_2))
                    || $pinAssessments->contains(fn($a) => !empty($a->category_8_3))
                ) {
                    $doneItems[] = '8';
                }
                $milestoneCount = count($doneItems);

                return [
                    'id' => $pin->id,
                    'district_id' => $pin->district_id,
                    'lat' => (float) $pin->latitude,
                    'lng' => (float) $pin->longitude,
                    'label' => $pin->label,
                    'province_name' => optional($pin->province)->province_name,
                    'breadcrumb' => implode(' › ', $breadcrumbParts),
                    'fiscal_year' => $pin->fiscal_year,
                    'progress' => $milestoneCount,
                    'progress_total' => 8,
                    'done_items' => $doneItems,
                ];
            })
            ->values();

        // Pins placed while FILING a report - "ปักหน่วยงานพื้นที่ใหม่" on
        // admin/kidney-dhb, which includes every report brought in from a Word
        // file - are not registered OperatingAreaPin rows: the location is
        // stored on the KidneyAssessment itself (operating_area_latitude/
        // longitude + province/district/tambon/village ids, operating_area_pin_id
        // left NULL). The query above only reads registered pins, so those
        // placed pins never reached this map (or the coverage/indicator counts
        // built from the same collection). Fold them in here as one pin per
        // distinct area + fiscal year, with the same filters, progress rule and
        // shape as a registered pin.
        $freeAreaPins = collect();
        $targetProvinceMap = Province::whereIn('province_name', $targetProvinces)->pluck('province_name', 'province_id');
        if ($targetProvinceMap->isNotEmpty()) {
            $freeQuery = KidneyAssessment::with('user')
                ->whereNull('operating_area_pin_id')
                ->whereNotNull('operating_area_latitude')
                ->whereNotNull('operating_area_longitude')
                ->whereIn('operating_area_province_id', $targetProvinceMap->keys()->all());
            if ($fiscalYear !== '') {
                $freeQuery->where('fiscal_year', $fiscalYear);
            }
            if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
                $freeQuery->whereIn('operating_area_province_id', $targetProvinceMap->filter(fn($n) => $n === $selectedProvince)->keys()->all());
            }
            if ($selectedDistrict && $selectedDistrict !== 'ทั้งหมด') {
                $freeQuery->whereIn('operating_area_district_id', District::where('district_name', $selectedDistrict)->pluck('district_id')->all());
            }
            $freeRows = $freeQuery->get()->filter(
                fn($a) => (float) $a->operating_area_latitude != 0.0 && (float) $a->operating_area_longitude != 0.0
            );

            $freeDistrictNames = District::whereIn('district_id', $freeRows->pluck('operating_area_district_id')->filter()->unique()->all())
                ->pluck('district_name', 'district_id');
            $freeTambonNames = \App\Models\Tambon::whereIn('tambon_id', $freeRows->pluck('operating_area_tambon_id')->filter()->unique()->all())
                ->pluck('tambon_name', 'tambon_id');

            $freeAreaPins = $freeRows
                ->groupBy(fn($a) => implode('|', [
                    $a->fiscal_year,
                    $a->operating_area_province_id,
                    $a->operating_area_district_id,
                    $a->operating_area_tambon_id,
                    $a->operating_area_village_id,
                    trim((string) $a->operating_area),
                    round((float) $a->operating_area_latitude, 5),
                    round((float) $a->operating_area_longitude, 5),
                ]))
                ->map(function ($group) use ($pinQuarter, $selectedRank, $targetProvinceMap, $freeDistrictNames, $freeTambonNames) {
                    $first = $group->first();
                    $assess = $group;
                    if ($pinQuarter !== null) {
                        $assess = $assess->where('quarter', $pinQuarter);
                    }
                    if ($selectedRank && $selectedRank !== 'ทั้งหมด') {
                        $assess = $assess->filter(
                            fn($a) => $a->user && (string) $a->user->User_rank_id === (string) $selectedRank
                        );
                    }
                    $done = [];
                    for ($i = 1; $i <= 7; $i++) {
                        $field = "category_$i";
                        if ($assess->contains(fn($a) => !empty($a->$field))) {
                            $done[] = (string) $i;
                        }
                    }
                    if ($assess->contains(fn($a) => !empty($a->category_8_1) || !empty($a->category_8_2) || !empty($a->category_8_3))) {
                        $done[] = '8';
                    }
                    $crumbs = array_filter([
                        $freeDistrictNames[$first->operating_area_district_id] ?? null,
                        $freeTambonNames[$first->operating_area_tambon_id] ?? null,
                        $first->operating_area_village_name_snapshot,
                    ]);
                    return [
                        'id' => null,
                        'district_id' => $first->operating_area_district_id,
                        'lat' => (float) $first->operating_area_latitude,
                        'lng' => (float) $first->operating_area_longitude,
                        'label' => trim((string) $first->operating_area) ?: null,
                        'province_name' => $targetProvinceMap[$first->operating_area_province_id] ?? null,
                        'breadcrumb' => implode(' › ', $crumbs),
                        'fiscal_year' => $first->fiscal_year,
                        'progress' => count($done),
                        'progress_total' => 8,
                        'done_items' => $done,
                    ];
                })
                ->values();
        }
        $operatingAreaPins = $operatingAreaPins->concat($freeAreaPins)->values();
        // รายงานเก่าที่ยังไม่มีหมุด/พิกัด (เช่น ปีงบ 2568) - แสดงที่จุดกึ่งกลางอำเภอแบบประมาณ
        $operatingAreaPins = $operatingAreaPins->concat(
            $this->legacyUnpinnedAreaPins((string) $fiscalYear, $targetProvinceMap, $selectedProvince, $selectedDistrict, $pinQuarter, $selectedRank)
        )->values();

        // "พื้นที่ดำเนินงาน X / Y อำเภอ" KPI card, counted from the pins
        // only (same ตัวกรอง as the pin map above):
        //   Y = distinct districts that hold at least one registered pin
        //       (ปักหน่วยงานแล้ว), not every district in the province;
        //   X = of those, distinct districts where at least one pin has
        //       been answered (progress > 0 = a KidneyAssessment filed
        //       against the pin has at least one milestone filled in),
        //       i.e. "ดำเนินงาน" means actually reported on, not just pinned.
        $coverageTotalDistricts = $operatingAreaPins
            ->pluck('district_id')->filter()->unique()->count();
        $coverageCoveredDistricts = $operatingAreaPins
            ->filter(fn($pin) => ($pin['progress'] ?? 0) > 0)
            ->pluck('district_id')->filter()->unique()->count();
        $coveragePercent = $coverageTotalDistricts > 0
            ? min(100, (int) round(($coverageCoveredDistricts / $coverageTotalDistricts) * 100))
            : 0;

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

        // Per-province rows for the "เปรียบเทียบสถานการณ์และผลดำเนินงานรายจังหวัด"
        // card (replaces the old stacked bar chart). Same definition as the
        // "พื้นที่ดำเนินงาน X / Y อำเภอ" KPI card, just split by province
        // (so the 5 rows add up to that card's X and Y):
        //   total    = distinct districts holding at least one registered pin;
        //   answered = of those, districts where at least one pin has been
        //              answered (progress > 0);
        //   percent  = answered / total. null = no pin registered in this
        //              province for the current filters ("—", ยังไม่มีข้อมูล),
        //              which is different from a real 0%.
        $provinceAreaProgress = collect($targetProvinces)->map(function ($provName) use ($operatingAreaPins) {
            $provPins = $operatingAreaPins->where('province_name', $provName);
            $total = $provPins->pluck('district_id')->filter()->unique()->count();
            $answered = $provPins
                ->filter(fn($pin) => ($pin['progress'] ?? 0) > 0)
                ->pluck('district_id')->filter()->unique()->count();

            // ข้อมูลครบ: a pin is "complete" at 8/8 (milestones 1-7 all filled,
            // plus any one of 8.1/8.2/8.3 - the same rule behind each pin's
            // 'progress'). Province-level: every pin complete -> 'complete';
            // at least one pin answered but not all complete -> 'partial';
            // nothing answered (or no pin at all) -> 'none' (ยังไม่รายงาน).
            $pinCount = $provPins->count();
            $answeredPinCount = $provPins->filter(fn($pin) => ($pin['progress'] ?? 0) > 0)->count();
            $completePinCount = $provPins->filter(fn($pin) => ($pin['progress'] ?? 0) >= ($pin['progress_total'] ?? 8))->count();
            $dataStatus = ($pinCount === 0 || $answeredPinCount === 0)
                ? 'none'
                : ($completePinCount === $pinCount ? 'complete' : 'partial');

            return [
                'province' => $provName,
                'answered' => $answered,
                'total' => $total,
                'percent' => $total > 0 ? (int) round($answered / $total * 100) : null,
                'data_status' => $dataStatus,
                'pin_count' => $pinCount,
                'complete_pins' => $completePinCount,
            ];
        })->values();

        // Per-indicator tally for the two KPI cards "ตัวชี้วัดที่ดำเนินงานได้ดี"
        // / "ยังไม่มีการดำเนินงาน": for each of the 8 items, how many of the
        // pins in scope (same filtered set as the pin map) have that item
        // done. percent = done pins / all pins in scope.
        $indicatorTotalPins = $operatingAreaPins->count();
        // KPI card "พื้นที่ที่ปักหมุดทั้งหมด": every pin in scope, and how many of
        // them already carry a report (progress > 0).
        $pinnedAnsweredCount = $operatingAreaPins->filter(fn($pin) => ($pin['progress'] ?? 0) > 0)->count();
        $pinnedAnsweredPercent = $indicatorTotalPins > 0
            ? min(100, (int) round($pinnedAnsweredCount / $indicatorTotalPins * 100))
            : 0;
        $indicatorStats = collect(['1', '2', '3', '4', '5', '6', '7', '8'])->map(function ($key) use ($operatingAreaPins, $indicatorTotalPins) {
            $done = $operatingAreaPins->filter(fn($pin) => in_array($key, $pin['done_items'] ?? [], true))->count();
            return [
                'key' => $key,
                'done' => $done,
                'total' => $indicatorTotalPins,
                'percent' => $indicatorTotalPins > 0 ? (int) round($done / $indicatorTotalPins * 100) : 0,
            ];
        })->values();
        // Best first (ties keep item order), only items somebody has actually done.
        $indicatorTop = $indicatorStats->filter(fn($s) => $s['done'] > 0)
            ->sortBy([['done', 'desc'], ['key', 'asc']])->take(3)->values();
        // Items no pin has done yet.
        $indicatorPending = $indicatorStats->filter(fn($s) => $s['done'] === 0)->values();

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

            // พื้นที่ (ชื่อหมู่บ้าน/ตำบล/อำเภอ) + ประเด็น (หมายเหตุของหมุด) แยกกัน - รายงานที่ไม่ผูกหมุดแสดงชื่อเดิม
            $areaList = \App\Support\KidneyAreaList::forUnit($allAssessments, $area);
            $primaryRow = collect($areaList)->first(fn($r) => !empty($r['primary']));
            $areaPlace = $primaryRow['place'] ?? $area;
            $areaIssue = $primaryRow['issue'] ?? null;

            return [
                'agency' => $row['display_name'],
                'operating_area' => $area,
                'area_place' => $areaPlace,
                'area_issue' => $areaIssue,
                'area_list' => $areaList,
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

        $displayOnlyPins = $this->displayOnlyPinList($targetProvinces, $fiscalYear, $selectedProvince, $selectedDistrict);

        // การ์ด "จำนวนพื้นที่ดำเนินงาน พชอ.ไต แยกรายจังหวัด": นับจากหมุดทั้งหมดที่ปักบนแผนที่
        // "พื้นที่ดำเนินงาน พชอ.ไต แยกสีตามจังหวัด" (หมุดที่ลงทะเบียน + หมุดที่ปักตอนรายงาน/นำเข้า Word
        // + หมุดแสดงผลของ SuperAdmin) ตามตัวกรองเดียวกัน - 1 หมุด = 1 พื้นที่ ตัวเลขจึงตรงกับแผนที่เสมอ
        $allMapPins = collect($operatingAreaPins)->concat($displayOnlyPins);
        foreach ($targetProvinces as $provName) {
            $mapData[$provName] = $allMapPins->where('province_name', $provName)->count();
        }

        return compact(
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
            'operatingAreaPins',
            'displayOnlyPins',
            'provinceAreaProgress',
            'indicatorStats',
            'indicatorTop',
            'indicatorPending',
            'indicatorTotalPins',
            'pinnedAnsweredCount',
            'pinnedAnsweredPercent',
            'coverageTotalDistricts',
            'coverageCoveredDistricts',
            'coveragePercent',
            'fiscalYear',
            'selectedRank',
            'searchQuery'
        );
    }
    public function index(Request $request)
    {
        $targetProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];

        // Every card on this page reads from SodiumSurvey (the
        // "การประเมินความตระหนักรู้" module) EXCEPT the Province Overview
        // Table further down, which also pulls from KidneyAssessment
        // (kidney), Hi (hi), and ReducedSodiumMenu (sodium_menus) - so a
        // year configured only for one of those 3 (e.g. 2567, which only
        // "เมนูลดโซเดียม" has data for) used to be impossible to select here
        // at all, even though every one of those 3 source pages' own
        // dropdown already offers it. This dropdown previously stayed
        // narrowed to 'awareness' alone on purpose (see git history) to
        // avoid a year with no awareness criteria configured becoming the
        // silent default - that still can't happen here since "ทั้งหมด"
        // sorts every module's years together and takes the single newest
        // one as this filter's default below, same as before.
        // 4 DISTINCT scans + 8 fiscal_years lookups used to run on EVERY
        // request (even when the dashboard bundle below was cached). The
        // list only changes when data for a brand-new year is imported or an
        // admin edits "จัดการปีงบประมาณ", so it is cached for 10 minutes and
        // keyed by that table's row count + latest update, which makes an
        // admin's enable/hide show up immediately.
        $fiscalYearStamp = FiscalYear::query()->selectRaw('COUNT(*) as c, MAX(updated_at) as u')->first();
        $yearsBundle = Cache::remember(
            'home_years_v1:' . md5(json_encode([$fiscalYearStamp->c ?? 0, (string) ($fiscalYearStamp->u ?? '')])),
            now()->addMinutes(10),
            function () {
                $awarenessYears = SodiumSurvey::select('fiscal_year')->distinct()->pluck('fiscal_year');
                $kidneyYearsForFilter = KidneyAssessment::distinct()->pluck('fiscal_year');
                $hiYearsForFilter = Hi::whereNotNull('year')->distinct()->pluck('year');
                $sodiumMenuYearsForFilter = ReducedSodiumMenu::whereNotNull('year')->where('year', '!=', '')->distinct()->pluck('year')->map(fn ($y) => trim($y));

                $years = FiscalYear::selectableYearsFor('awareness', $awarenessYears)
                    ->merge(FiscalYear::selectableYearsFor('kidney', $kidneyYearsForFilter))
                    ->merge(FiscalYear::selectableYearsFor('hi', $hiYearsForFilter))
                    ->merge(FiscalYear::selectableYearsFor('sodium_menus', $sodiumMenuYearsForFilter))
                    ->unique()
                    ->sortDesc()
                    ->values();

                return ['awarenessYears' => $awarenessYears, 'years' => $years];
            }
        );
        $awarenessYears = $yearsBundle['awarenessYears'];
        $years = $yearsBundle['years'];

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
        // "v3" bumps past any 10-minute cache entry written by a previous
        // version of this closure (v2: before the awarenessOverall* KPI
        // keys existed; v3: before sodiumCompareData was replaced by
        // provinceOverviewRows) - without bumping this, a still-warm cache
        // entry would get extract()ed without the new key and the
        // compact() further down would fail on an undefined variable.
        $homeDashboardCacheKey = 'home_dashboard_v6:' . md5(json_encode([
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
            if (AwarenessPassResolver::methodFor($passYear) === \App\Models\AwarenessPassSetting::METHOD_SCORE) {
                // Score method: fold the per-year cached [province][district]
                // grid (shared with /awareness) instead of re-scoring every
                // respondent row for each province filter - same totals, the
                // province scope below mirrors $awarenessBaseQuery's own.
                $yearCounts = [];
                foreach (AwarenessPassResolver::scoreGrid($passYear) as $province => $districts) {
                    $province = (string) $province;
                    if ($selectedProvince !== 'all' ? $province !== $selectedProvince : !in_array($province, $targetProvinces, true)) {
                        continue;
                    }
                    foreach ($districts as $cell) {
                        $yearCounts[$province]['total'] = ($yearCounts[$province]['total'] ?? 0) + $cell['total'];
                        $yearCounts[$province]['pass'] = ($yearCounts[$province]['pass'] ?? 0) + $cell['pass'];
                    }
                }
            } else {
                $yearCounts = AwarenessPassResolver::passFailCountsByColumn($passYear, $awarenessBaseQuery, 'province_name');
            }
            foreach ($yearCounts as $province => $counts) {
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

        // 3. Province Overview Table Data ("เปรียบเทียบสถานการณ์และผลดำเนินงานรายจังหวัด")
        //
        // Replaces the old ก่อน/หลัง-ปรับสูตร average-sodium bar chart with a
        // single summary table. Each column is pulled from the exact same
        // computation the dedicated page for that number already uses, so
        // this table never invents a second definition of its own:
        //   - HT / แสนคน: same Hi-table rate (case count / target * 100,000)
        //     shown on the map on /new-ht-cases
        //     (see MainController::newHTCases()'s $mapProvinceTotals).
        //   - พื้นที่ดำเนินงาน / ข้อมูลครบ: same per-operating-area "all 8
        //     milestones filled" completion used for the "หน่วยงานที่ส่ง
        //     ข้อมูลครบ 4 ไตรมาส" stacked chart on /kidney-dhb-report (see
        //     MainController::kidneyDHBReport()'s $stackedChartData) - every
        //     area complete -> "ครบ", some but not all -> "บางส่วน", no
        //     operating area reported at all -> "ยังไม่รายงาน".
        //   - ก่อนปรับสูตร / จำนวนเมนูหลังปรับสูตร: same เมนู rows used by
        //     /reduced-sodium-menu - "ก่อน" is every menu recorded for the
        //     province, "หลัง" is the subset that's actually been
        //     re-tested (sodium_before AND sodium_after both > 0 - the same
        //     $measuredMenus condition reducedSodiumMenu() itself uses,
        //     since an un-retested menu is stored as a literal 0, not
        //     null).
        // Scoped the same way as every other card on this page: one
        // province when selected, all 5 target provinces for "ทุกจังหวัด".
        $overviewScopeProvinces = $selectedProvince !== 'all' ? [$selectedProvince] : $targetProvinces;

        $overviewHtQuery = Hi::query();
        if ($activeFiscalYear !== '') {
            $overviewHtQuery->where('year', $activeFiscalYear);
        }
        $overviewHtByProvinceId = $overviewHtQuery->get()->groupBy('Province_id');
        $overviewProvinceIds = Province::whereIn('province_name', $overviewScopeProvinces)->pluck('province_id', 'province_name');

        $overviewKidneyQuery = KidneyAssessment::with('user.province');
        if ($activeFiscalYear !== '') {
            $overviewKidneyQuery->where('fiscal_year', $activeFiscalYear);
        }
        $overviewKidneyAssessments = $overviewKidneyQuery->get();

        $overviewMenuQuery = ReducedSodiumMenu::query()->whereIn('province', $overviewScopeProvinces);
        if ($activeFiscalYear !== '') {
            $overviewMenuQuery->where('year', $activeFiscalYear);
        }
        $overviewMenusByProvince = $overviewMenuQuery->get()->groupBy('province');

        $provinceOverviewRows = collect($overviewScopeProvinces)->map(function ($province) use (
            $overviewHtByProvinceId,
            $overviewProvinceIds,
            $overviewKidneyAssessments,
            $overviewMenusByProvince
        ) {
            // HT / แสนคน
            $provinceId = $overviewProvinceIds->get($province);
            $htItems = $provinceId ? ($overviewHtByProvinceId->get($provinceId) ?? collect()) : collect();
            $htTarget = $htItems->sum('target_b');
            $htRate = $htTarget > 0 ? round($htItems->sum('total_a') / $htTarget * 100000) : null;

            // พื้นที่ดำเนินงาน % + ข้อมูลครบ - same (user_id|operating_area) unit
            // and "all 8 milestones" completion rule as kidneyDHBReport().
            $areaGroups = $overviewKidneyAssessments
                ->filter(fn($a) => $a->user && $a->user->province && $a->user->province->province_name === $province)
                ->groupBy(fn($a) => $a->user_id . '|' . (($a->operating_area !== null && $a->operating_area !== '') ? $a->operating_area : ''));

            $totalAreas = $areaGroups->count();
            $completedAreas = $areaGroups->filter(function ($userAssessments) {
                $milestonesDone = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $field = "category_$i";
                    if ($userAssessments->contains(fn($a) => !empty($a->$field))) {
                        $milestonesDone++;
                    }
                }
                if (
                    $userAssessments->contains(fn($a) => !empty($a->category_8_1))
                    || $userAssessments->contains(fn($a) => !empty($a->category_8_2))
                    || $userAssessments->contains(fn($a) => !empty($a->category_8_3))
                ) {
                    $milestonesDone++;
                }
                return $milestonesDone >= 8;
            })->count();

            $areaPercent = $totalAreas > 0 ? round($completedAreas / $totalAreas * 100) : null;
            $dataStatus = $totalAreas === 0 ? 'none' : ($completedAreas >= $totalAreas ? 'complete' : 'partial');

            // เมนูลดโซเดียม: ก่อน = ทุกเมนูที่บันทึกไว้, หลัง = เมนูที่วัดค่าแล้วจริง
            // (sodium_before/after > 0 ทั้งคู่)
            $menuItems = $overviewMenusByProvince->get($province) ?? collect();
            $menuBefore = $menuItems->count();
            $menuAfter = $menuItems->filter(fn($m) => $m->sodium_before > 0 && $m->sodium_after > 0)->count();
            $menuPercentChange = $menuBefore > 0 ? round((($menuAfter - $menuBefore) / $menuBefore) * 100) : null;

            return [
                'province' => $province,
                'ht_rate' => $htRate,
                'area_percent' => $areaPercent,
                'data_status' => $dataStatus,
                'menu_before' => $menuBefore,
                'menu_after' => $menuAfter,
                'menu_percent_change' => $menuPercentChange,
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

        // Headline stats for the home page's ผลิตภัณฑ์ลดโซเดียม KPI cards - same
        // rows (same year/province scope) as the count chart, using the same
        // definitions as the reduced-sodium-products page: certified = has a
        // standard_certification value, top type = most frequent product_type.
        $productRows = (clone $productCountQuery)->get([
            'reduced_sodium_products.product_type',
            'reduced_sodium_products.standard_certification',
        ]);
        $productTypeCounts = $productRows->groupBy(fn($r) => trim((string) $r->product_type) !== '' ? trim((string) $r->product_type) : 'ไม่ระบุ')
            ->map->count()->sortDesc();
        $productCertified = $productRows->filter(fn($r) => trim((string) $r->standard_certification) !== '')->count();
        $productStats = [
            'total' => $productRows->count(),
            'certified' => $productCertified,
            'certified_percent' => $productRows->count() > 0 ? round($productCertified / $productRows->count() * 100, 1) : null,
            'top_type' => $productTypeCounts->keys()->first(),
            'top_type_count' => (int) ($productTypeCounts->first() ?? 0),
            'type_total' => $productTypeCounts->count(),
        ];

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

        // Same condition as $behaviorWhereFor above, but as a [sql, bindings]
        // pair so several roles can share ONE grouped query (SUM(CASE ...)).
        $behaviorCondFor = function ($fiscalYearForRole, string $role, array $default): array {
            $mappings = SurveyYearMapping::behaviorMappingsFor($fiscalYearForRole, $role);
            $parts = [];
            $bindings = [];
            foreach ($mappings as $m) {
                $values = !empty($m->criteria_pass_values) ? $m->criteria_pass_values : $default;
                if (empty($values)) {
                    continue;
                }
                $placeholders = implode(',', array_fill(0, count($values), '?'));
                $parts[] = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$m->question_key}')) in ({$placeholders})";
                foreach ($values as $v) {
                    $bindings[] = $v;
                }
            }
            return $parts ? ['(' . implode(' OR ', $parts) . ')', $bindings] : ['1 = 0', []];
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

            // ONE grouped scan per year (COUNT + the 4 role conditions as
            // SUM(CASE ...) columns) instead of 5 separate JSON_EXTRACT
            // scans of the same rows - identical numbers, 1/5 the reads.
            $roleSpecs = $isFy69Year
                ? ['no_seasoning' => ['add_seasoning_cook', $seasoningDefault], 'no_instant_food' => ['freq_instant_food', $instantDefault], 'no_pickled_food' => ['freq_processed_food', $processedDefault], 'reduction_effort' => ['behavioral_reduce_dipping', $dippingDefault]]
                : ['no_seasoning' => ['add_seasoning_cook', $seasoningDefault], 'no_instant_food' => ['freq_instant_food', $instantDefault], 'no_pickled_food' => ['freq_pickled_food', $pickledDefault], 'reduction_effort' => ['importance_level', $importanceDefault]];

            $metricsAgg = (clone $metricsBaseYear)->select('province_name')->selectRaw('COUNT(*) as total_count');
            foreach ($roleSpecs as $alias => [$role, $default]) {
                [$condSql, $condBindings] = $behaviorCondFor($metricsYear, $role, $default);
                $metricsAgg->selectRaw("SUM(CASE WHEN {$condSql} THEN 1 ELSE 0 END) as c_{$alias}", $condBindings);
            }
            $metricsRows = $metricsAgg->groupBy('province_name')->get()->keyBy('province_name');

            $metricsTotalCount += (int) $metricsRows->sum('total_count');
            $qNoSeasoning = $metricsRows->map(fn($r) => (int) $r->c_no_seasoning);
            $qNoInstant = $metricsRows->map(fn($r) => (int) $r->c_no_instant_food);
            $qNoPickled = $metricsRows->map(fn($r) => (int) $r->c_no_pickled_food);
            $qEffort = $metricsRows->map(fn($r) => (int) $r->c_reduction_effort);

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
            'provinceOverviewRows',
            'productCountData',
            'productStats',
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

        // Summary blocks for the 3 non-awareness modules shown on the home
        // page (พชอ.ไต / เมนูลดโซเดียม / HT) - cached separately from the
        // awareness bundle above (different source tables, cheaper, and
        // they should not be invalidated together).
        $homeModules = Cache::remember(
            'home_modules_v13:' . md5(json_encode(['y' => $activeFiscalYear, 'p' => $selectedProvince, 'hi' => $this->tableStamp(Hi::class), 'fy' => $this->tableStamp(FiscalYear::class), 'op' => $this->tableStamp(OperatingAreaPin::class), 'ka' => $this->tableStamp(KidneyAssessment::class)])),
            now()->addMinutes(10),
            fn() => $this->buildHomeModuleSummaries($activeFiscalYear, $selectedProvince, $targetProvinces)
        );

        return view('pages.home', compact(
            'awarenessData',
            'provinceOverviewRows',
            'productCountData',
            'productStats',
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
            'awarenessMethod',
            'homeModules'
        ));
    }

    /**
     * พื้นที่ดำเนินงานของรายงานเก่าที่ "ยังไม่มีหมุด/พิกัด" (เช่น ข้อมูลปีงบประมาณ 2568 ที่กรอกก่อนมีระบบปักหมุด):
     * รายงานพวกนี้มีแค่ชื่อพื้นที่ (ข้อความ) กับหน่วยงานผู้รายงาน จึงไม่เคยโผล่บนแผนที่/การ์ดสรุป
     * ที่นับจากหมุดเลย ทั้งที่มีข้อมูลครบ - ที่นี่รวมเป็น "พื้นที่" ละ 1 รายการ (ปีงบ + จังหวัด + อำเภอ +
     * หน่วยงาน + ชื่อพื้นที่) แล้ววางที่จุดกึ่งกลางอำเภอ (เฉลี่ยพิกัดหมู่บ้านในอำเภอ; ไม่มีอำเภอใช้จุดกึ่งกลางจังหวัด)
     * โดยกระจายหมุดที่ซ้อนกันออกเล็กน้อย และติดธง 'approx' => true เพื่อให้หน้าจอบอกว่าเป็นตำแหน่งโดยประมาณ
     * รูปแบบ/กติกาความก้าวหน้า (8 ข้อ) เหมือนหมุดปกติ จึงรวมเข้า collection เดียวกันได้ทันที
     * ไม่ใช่การปักหมุดจริง - พอมีการปักหมุดให้รายงานนั้น (มีพิกัด/pin id) รายการนี้จะหายไปเองโดยอัตโนมัติ
     *
     * @param \Illuminate\Support\Collection $provinceMap province_id => province_name (ขอบเขตที่แสดง)
     */
    private function legacyUnpinnedAreaPins(string $fiscalYear, $provinceMap, ?string $selectedProvince = null, ?string $selectedDistrict = null, ?int $quarter = null, ?string $rank = null)
    {
        if ($provinceMap->isEmpty()) {
            return collect();
        }

        $rows = KidneyAssessment::with('user')
            ->whereNull('operating_area_pin_id')
            ->where(function ($q) {
                $q->whereNull('operating_area_latitude')->orWhereNull('operating_area_longitude')
                    ->orWhere('operating_area_latitude', 0)->orWhere('operating_area_longitude', 0);
            })
            ->when($fiscalYear !== '', fn($q) => $q->where('fiscal_year', $fiscalYear))
            ->get()
            ->map(function ($a) {
                $a->legacy_province_id = $a->operating_area_province_id ?: optional($a->user)->Province_id;
                $a->legacy_district_id = $a->operating_area_district_id ?: optional($a->user)->District_id;
                return $a;
            })
            ->filter(fn($a) => $a->legacy_province_id && $provinceMap->has($a->legacy_province_id));

        if ($selectedProvince && $selectedProvince !== 'ทั้งหมด') {
            $keep = $provinceMap->filter(fn($n) => $n === $selectedProvince)->keys()->all();
            $rows = $rows->filter(fn($a) => in_array($a->legacy_province_id, $keep));
        }
        if ($selectedDistrict && $selectedDistrict !== 'ทั้งหมด') {
            $keepD = District::where('district_name', $selectedDistrict)->pluck('district_id')->all();
            $rows = $rows->filter(fn($a) => in_array($a->legacy_district_id, $keepD));
        }
        if ($rows->isEmpty()) {
            return collect();
        }

        $districtNames = District::whereIn('district_id', $rows->pluck('legacy_district_id')->filter()->unique()->all())->pluck('district_name', 'district_id');
        $region = fn($q) => $q->whereBetween('latitude', [13.8, 17.5])->whereBetween('longitude', [103.5, 105.8]);
        $districtCentroid = \App\Models\Village::query()->tap($region)
            ->whereIn('district_id', $rows->pluck('legacy_district_id')->filter()->unique()->all())
            ->selectRaw('district_id, AVG(latitude) as lat, AVG(longitude) as lng')->groupBy('district_id')->get()->keyBy('district_id');
        $provinceCentroid = \App\Models\Village::query()->tap($region)
            ->whereIn('province_id', $rows->pluck('legacy_province_id')->unique()->all())
            ->selectRaw('province_id, AVG(latitude) as lat, AVG(longitude) as lng')->groupBy('province_id')->get()->keyBy('province_id');

        $groups = $rows->groupBy(fn($a) => implode('|', [
            $a->fiscal_year,
            $a->legacy_province_id,
            $a->legacy_district_id,
            optional($a->user)->User_rank_id,
            optional($a->user)->sh_id,
            optional($a->user)->hos_id,
            trim((string) $a->operating_area),
        ]));

        $slot = [];
        return $groups->map(function ($group) use ($quarter, $rank, $provinceMap, $districtNames, $districtCentroid, $provinceCentroid, &$slot) {
            $first = $group->first();
            $assess = $group;
            if ($quarter !== null) {
                $assess = $assess->where('quarter', $quarter);
            }
            if ($rank && $rank !== 'ทั้งหมด') {
                $assess = $assess->filter(fn($a) => $a->user && (string) $a->user->User_rank_id === (string) $rank);
            }
            $done = [];
            for ($i = 1; $i <= 7; $i++) {
                $field = "category_$i";
                if ($assess->contains(fn($a) => !empty($a->$field))) {
                    $done[] = (string) $i;
                }
            }
            if ($assess->contains(fn($a) => !empty($a->category_8_1) || !empty($a->category_8_2) || !empty($a->category_8_3))) {
                $done[] = '8';
            }

            $c = $first->legacy_district_id ? ($districtCentroid[$first->legacy_district_id] ?? null) : null;
            $c = $c ?: ($provinceCentroid[$first->legacy_province_id] ?? null);
            if (!$c || (float) $c->lat == 0.0) {
                return null;
            }
            // หมุดที่อยู่อำเภอ/จังหวัดเดียวกันจะซ้อนกัน -> วางเรียงเป็นวงเล็กๆ รอบจุดกึ่งกลาง
            $key = $first->legacy_district_id ?: ('p' . $first->legacy_province_id);
            $n = $slot[$key] = ($slot[$key] ?? -1) + 1;
            $angle = $n * 2.399963; // golden angle
            $radius = $n === 0 ? 0 : 0.012 * sqrt($n);
            $districtName = $first->legacy_district_id ? ($districtNames[$first->legacy_district_id] ?? null) : null;

            return [
                'id' => null,
                'district_id' => $first->legacy_district_id,
                'lat' => (float) $c->lat + $radius * sin($angle),
                'lng' => (float) $c->lng + $radius * cos($angle),
                'label' => trim((string) $first->operating_area) ?: optional($first->user)->User_firstname,
                'province_name' => $provinceMap[$first->legacy_province_id] ?? null,
                'breadcrumb' => $districtName ?: '',
                'fiscal_year' => $first->fiscal_year,
                'progress' => count($done),
                'progress_total' => 8,
                'done_items' => $done,
                'approx' => true,
            ];
        })->filter()->values();
    }

    /**
     * Summary figures for the home page's three module sections
     * (พชอ.ไต / เมนูลดโซเดียม / HT). Every definition is copied from the
     * dedicated page it summarises so the numbers always agree with it:
     *   - kidney: pin-based, same as kidneyDHBReport() (distinct districts
     *     pinned vs. answered, per-item done counts, 8-milestone rule);
     *   - menu:   same $measuredMenus / tier rule as reducedSodiumMenu();
     *   - ht:     same Hi columns and per-100,000 rate as newHTCases().
     * $year '' = every year (kidney/menu); the HT block then falls back to the
     * newest year with data, because summing a population denominator over
     * several years would not be a rate.
     */
    private function buildHomeModuleSummaries(string $year, string $selectedProvince, array $targetProvinces): array
    {
        $scopeProvinces = $selectedProvince !== 'all' ? [$selectedProvince] : $targetProvinces;

        // ---------------------------------------------------------------- kidney
        $pinsQuery = OperatingAreaPin::reportable()->with(['province', 'district', 'tambon', 'village', 'assessments'])
            ->whereHas('province', fn($q) => $q->whereIn('province_name', $scopeProvinces));
        if ($year !== '') {
            $pinsQuery->where('fiscal_year', $year);
        }
        $pins = $pinsQuery->get()->map(function ($pin) {
            $assess = $pin->assessments->where('fiscal_year', $pin->fiscal_year);
            $done = [];
            for ($i = 1; $i <= 7; $i++) {
                $f = "category_$i";
                if ($assess->contains(fn($a) => !empty($a->$f))) {
                    $done[] = (string) $i;
                }
            }
            if ($assess->contains(fn($a) => !empty($a->category_8_1) || !empty($a->category_8_2) || !empty($a->category_8_3))) {
                $done[] = '8';
            }
            $crumbs = array_filter([
                optional($pin->district)->district_name,
                optional($pin->tambon)->tambon_name,
                $pin->village_name_snapshot ?? optional($pin->village)->village_name,
            ]);
            return [
                'district_id' => $pin->district_id,
                'province_name' => optional($pin->province)->province_name,
                'progress' => count($done),
                'progress_total' => 8,
                'done_items' => $done,
                'lat' => (float) $pin->latitude,
                'lng' => (float) $pin->longitude,
                'label' => $pin->label,
                'breadcrumb' => implode(' › ', $crumbs),
                'fiscal_year' => $pin->fiscal_year,
            ];
        })->values();

        // Pins placed while filing a report (incl. Word imports) live on the
        // KidneyAssessment itself, not as registered pins - fold them in the
        // same way kidneyDHBReport() does so home and the report agree.
        $homeProvMap = Province::whereIn('province_name', $scopeProvinces)->pluck('province_name', 'province_id');
        if ($homeProvMap->isNotEmpty()) {
            $freeRows = KidneyAssessment::query()
                ->whereNull('operating_area_pin_id')
                ->whereNotNull('operating_area_latitude')
                ->whereNotNull('operating_area_longitude')
                ->whereIn('operating_area_province_id', $homeProvMap->keys()->all())
                ->when($year !== '', fn($q) => $q->where('fiscal_year', $year))
                ->get()
                ->filter(fn($a) => (float) $a->operating_area_latitude != 0.0 && (float) $a->operating_area_longitude != 0.0);
            $freeDistNames = District::whereIn('district_id', $freeRows->pluck('operating_area_district_id')->filter()->unique()->all())->pluck('district_name', 'district_id');
            $freeTamNames = \App\Models\Tambon::whereIn('tambon_id', $freeRows->pluck('operating_area_tambon_id')->filter()->unique()->all())->pluck('tambon_name', 'tambon_id');
            $freePins = $freeRows
                ->groupBy(fn($a) => implode('|', [$a->fiscal_year, $a->operating_area_province_id, $a->operating_area_district_id, $a->operating_area_tambon_id, $a->operating_area_village_id, trim((string) $a->operating_area), round((float) $a->operating_area_latitude, 5), round((float) $a->operating_area_longitude, 5)]))
                ->map(function ($g) use ($homeProvMap, $freeDistNames, $freeTamNames) {
                    $f = $g->first();
                    $done = [];
                    for ($i = 1; $i <= 7; $i++) {
                        $fld = "category_$i";
                        if ($g->contains(fn($a) => !empty($a->$fld))) {
                            $done[] = (string) $i;
                        }
                    }
                    if ($g->contains(fn($a) => !empty($a->category_8_1) || !empty($a->category_8_2) || !empty($a->category_8_3))) {
                        $done[] = '8';
                    }
                    return [
                        'district_id' => $f->operating_area_district_id,
                        'province_name' => $homeProvMap[$f->operating_area_province_id] ?? null,
                        'progress' => count($done),
                        'progress_total' => 8,
                        'done_items' => $done,
                        'lat' => (float) $f->operating_area_latitude,
                        'lng' => (float) $f->operating_area_longitude,
                        'label' => trim((string) $f->operating_area) ?: null,
                        'breadcrumb' => implode(' › ', array_filter([$freeDistNames[$f->operating_area_district_id] ?? null, $freeTamNames[$f->operating_area_tambon_id] ?? null, $f->operating_area_village_name_snapshot])),
                        'fiscal_year' => $f->fiscal_year,
                    ];
                })->values();
            $pins = $pins->concat($freePins)->values();
            $pins = $pins->concat($this->legacyUnpinnedAreaPins($year, $homeProvMap))->values();
        }

        $pinTotal = $pins->count();
        $answeredPins = $pins->filter(fn($p) => $p['progress'] > 0);
        $completePins = $pins->filter(fn($p) => $p['progress'] >= 8);
        $districtsTotal = $pins->pluck('district_id')->filter()->unique()->count();
        $districtsCovered = $answeredPins->pluck('district_id')->filter()->unique()->count();

        $kidneyProvinces = collect($scopeProvinces)->map(function ($prov) use ($pins) {
            $pp = $pins->where('province_name', $prov);
            $total = $pp->pluck('district_id')->filter()->unique()->count();
            $answered = $pp->filter(fn($p) => $p['progress'] > 0)->pluck('district_id')->filter()->unique()->count();
            $pc = $pp->count();
            $ap = $pp->filter(fn($p) => $p['progress'] > 0)->count();
            $cp = $pp->filter(fn($p) => $p['progress'] >= 8)->count();
            return [
                'province' => $prov,
                'answered' => $answered,
                'total' => $total,
                'percent' => $total > 0 ? (int) round($answered / $total * 100) : null,
                'pin_count' => $pc,
                'complete_pins' => $cp,
                'data_status' => ($pc === 0 || $ap === 0) ? 'none' : ($cp === $pc ? 'complete' : 'partial'),
            ];
        })->values();

        $kidneyItems = collect(['1', '2', '3', '4', '5', '6', '7', '8'])->map(function ($k) use ($pins, $pinTotal) {
            $done = $pins->filter(fn($p) => in_array($k, $p['done_items'], true))->count();
            return ['key' => $k, 'done' => $done, 'total' => $pinTotal, 'percent' => $pinTotal > 0 ? (int) round($done / $pinTotal * 100) : 0];
        })->values();

        $displayPinsForHome = $this->displayOnlyPinList($scopeProvinces, $year);

        $kidney = [
            'pin_total' => $pinTotal,
            // หมุดแสดงผล (SuperAdmin เพิ่มไว้แสดงบนแผนที่) - นับแยก ไม่ปนกับหมุดรายงาน แต่รวมในเลข "ปักหมุดทั้งหมด"
            'display_pin_count' => count($displayPinsForHome),
            'pin_all' => $pinTotal + count($displayPinsForHome),
            'answered_pins' => $answeredPins->count(),
            'complete_pins' => $completePins->count(),
            'districts_total' => $districtsTotal,
            'districts_covered' => $districtsCovered,
            'coverage_percent' => $districtsTotal > 0 ? min(100, (int) round($districtsCovered / $districtsTotal * 100)) : 0,
            'items_done_sum' => (int) $pins->sum('progress'),
            'items_possible' => $pinTotal * 8,
            'items' => $kidneyItems,
            'items_top' => $kidneyItems->filter(fn($s) => $s['done'] > 0)->sortBy([['done', 'desc'], ['key', 'asc']])->take(3)->values(),
            'items_pending' => $kidneyItems->filter(fn($s) => $s['done'] === 0)->values(),
            'provinces' => $kidneyProvinces,
            'pins' => array_merge(
                $pins->map(fn($p) => collect($p)->except(['done_items'])->all())->all(),
                // หมุดแสดงผล: วาดบนแผนที่ (ไม่นับในความครอบคลุม/ตัวชี้วัด แต่รวมในเลข pin_all)
                $displayPinsForHome
            ),
        ];

        // ------------------------------------------------------------------ menu
        $menuQuery = ReducedSodiumMenu::query()->whereIn('province', $scopeProvinces);
        if ($year !== '') {
            $menuQuery->where('year', $year);
        }
        $menus = $menuQuery->get();
        $measured = $menus->filter(fn($m) => $m->sodium_before > 0 && $m->sodium_after > 0);
        $tierOf = function ($after) {
            if ($after < 600) return 'ดีมาก';
            if ($after <= 800) return 'ดี';
            if ($after <= 1000) return 'ปานกลาง';
            return 'ควรปรับปรุง';
        };
        $tierCounts = ['ดีมาก' => 0, 'ดี' => 0, 'ปานกลาง' => 0, 'ควรปรับปรุง' => 0];
        foreach ($measured as $m) {
            $tierCounts[$tierOf($m->sodium_after)]++;
        }
        $measuredCount = $measured->count();
        $avgBefore = round($measured->avg('sodium_before') ?: 0, 1);
        $avgAfter = round($measured->avg('sodium_after') ?: 0, 1);

        $placeCategories = [
            'โรงพยาบาล' => ['โรงพยาบาล', 'รพ.', 'โรงครัวรพ.'],
            'ตลาด' => ['ตลาด'],
            'โรงเรียน' => ['โรงเรียน', 'รร.', 'เรียน', 'โรงครัวรร.', 'วิทยาลัย', 'มหาลัย'],
            'ร้านอาหารในชุมชน' => ['ร้านอาหาร', 'ร้าน', 'ครัว', 'ป้า', 'ลุง', 'โภชนาการ', 'กุ๊ก'],
        ];
        $places = $menus->groupBy(function ($m) use ($placeCategories) {
            $fields = array_filter([$m->kitchen_type, $m->org_name, $m->agency]);
            foreach ($placeCategories as $cat => $patterns) {
                foreach ($fields as $v) {
                    foreach ($patterns as $pt) {
                        if (mb_strpos($v, $pt) !== false) {
                            return $cat;
                        }
                    }
                }
            }
            return 'อื่นๆ';
        })->map->count()->sortDesc();

        $menu = [
            'total' => $menus->count(),
            'measured' => $measuredCount,
            'unmeasured' => $menus->count() - $measuredCount,
            'avg_before' => $avgBefore,
            'avg_after' => $avgAfter,
            'reduction_percent' => $avgBefore > 0 ? round(($avgBefore - $avgAfter) / $avgBefore * 100, 1) : null,
            'best_count' => $tierCounts['ดีมาก'],
            'best_percent' => $measuredCount > 0 ? round($tierCounts['ดีมาก'] / $measuredCount * 100, 1) : null,
            'high_risk_count' => $tierCounts['ควรปรับปรุง'],
            'tiers' => $tierCounts,
            // Per-province: every menu recorded (total), the re-tested subset
            // (measured), and the paired average sodium before/after over
            // exactly that subset - never two averages over different menus.
            'provinces' => collect($scopeProvinces)->map(function ($prov) use ($menus) {
                $all = $menus->where('province', $prov);
                $m = $all->filter(fn($x) => $x->sodium_before > 0 && $x->sodium_after > 0);
                $b = $m->count() > 0 ? round($m->avg('sodium_before'), 1) : null;
                $a = $m->count() > 0 ? round($m->avg('sodium_after'), 1) : null;
                return [
                    'province' => $prov,
                    'total' => $all->count(),
                    'measured' => $m->count(),
                    'avg_before' => $b,
                    'avg_after' => $a,
                    'reduction_percent' => ($b !== null && $b > 0) ? round(($b - $a) / $b * 100, 1) : null,
                ];
            })->values(),
            'places' => $places->map(fn($c, $name) => ['name' => $name, 'count' => $c])->values(),
        ];

        // -------------------------------------------------------------------- HT
        $hiYears = Hi::whereNotNull('year')->distinct()->pluck('year');
        // ไม่ได้เลือกปี -> ใช้ปีล่าสุดที่ "มีข้อมูลผู้ป่วยแล้ว" (ปีใหม่ที่เพิ่งเปิดแต่ยังไม่มีข้อมูลจะไม่ทำให้การ์ดว่าง)
        $hiYearsWithCases = Hi::whereNotNull('year')->where('total_a', '>', 0)->distinct()->pluck('year');
        $htYear = $year !== '' ? $year : (string) ($hiYearsWithCases->max() ?? $hiYears->max());
        $provinceIds = Province::whereIn('province_name', $scopeProvinces)->pluck('province_id', 'province_name');
        $hiRows = Hi::query()
            ->when($htYear !== '', fn($q) => $q->where('year', $htYear))
            ->whereIn('Province_id', $provinceIds->values())
            ->get();

        $monthCols = [
            'm10_oct' => 'ต.ค.', 'm11_nov' => 'พ.ย.', 'm12_dec' => 'ธ.ค.', 'm01_jan' => 'ม.ค.',
            'm02_feb' => 'ก.พ.', 'm03_mar' => 'มี.ค.', 'm04_apr' => 'เม.ย.', 'm05_may' => 'พ.ค.',
            'm06_jun' => 'มิ.ย.', 'm07_jul' => 'ก.ค.', 'm08_aug' => 'ส.ค.', 'm09_sep' => 'ก.ย.',
        ];
        $trend = [];
        foreach ($monthCols as $col => $label) {
            $trend[] = ['label' => $label, 'value' => (int) $hiRows->sum($col)];
        }
        // Latest month = last fiscal-order month that already has cases.
        $latestIdx = null;
        foreach ($trend as $i => $t) {
            if ($t['value'] > 0) {
                $latestIdx = $i;
            }
        }
        $latestMonth = $latestIdx !== null ? $trend[$latestIdx] : null;
        $prevMonth = ($latestIdx !== null && $latestIdx > 0) ? $trend[$latestIdx - 1] : null;
        $monthChange = ($latestMonth && $prevMonth && $prevMonth['value'] > 0)
            ? round(($latestMonth['value'] - $prevMonth['value']) / $prevMonth['value'] * 100, 1)
            : null;

        $htProvinces = collect($scopeProvinces)->map(function ($prov) use ($provinceIds, $hiRows) {
            $items = $hiRows->where('Province_id', $provinceIds->get($prov));
            $cases = (int) $items->sum('total_a');
            $target = (int) $items->sum('target_b');
            return [
                'province' => $prov,
                'cases' => $cases,
                'target' => $target,
                'rate' => $target > 0 ? round($cases / $target * 100000, 2) : null,
            ];
        })->values();
        $rated = $htProvinces->filter(fn($r) => $r['rate'] !== null);
        $totalCases = (int) $htProvinces->sum('cases');
        // Like-for-like regional rate: numerator AND denominator both come
        // only from provinces that actually have a target population.
        // /new-ht-cases divides ALL cases by only the populated provinces'
        // target, which overstates the rate whenever a province has cases
        // but no target_b (see 'unrated_provinces' below).
        $ratedCases = (int) $rated->sum('cases');
        $totalTarget = (int) $rated->sum('target');
        $unratedProvinces = $htProvinces->filter(fn($r) => $r['rate'] === null && $r['cases'] > 0)->pluck('province')->values();

        // Year-over-year trend of that same like-for-like rate.
        $hiAllYears = Hi::whereIn('Province_id', $provinceIds->values())->whereNotNull('year')->get()->groupBy('year');
        $yearTrend = $this->htYearTrend($hiAllYears);

        $trendPop = $this->buildHtTrendPop($scopeProvinces, $provinceIds, $hiAllYears, $yearTrend, $monthCols);

        $ht = [
            'year' => $htYear,
            'year_is_fallback' => $year === '',
            'total_cases' => $totalCases,
            'total_target' => $totalTarget,
            'rate' => $totalTarget > 0 ? round($ratedCases / $totalTarget * 100000, 2) : null,
            'unrated_provinces' => $unratedProvinces,
            'year_trend' => $yearTrend,
            'trend_pop' => $trendPop,
            'trend' => $trend,
            'latest_month' => $latestMonth,
            'prev_month' => $prevMonth,
            'month_change' => $monthChange,
            'provinces' => $htProvinces,
            'top_province' => $rated->sortByDesc('rate')->first(),
            'low_province' => $rated->sortBy('rate')->first(),
            // วันที่ข้อมูล HT ถูกอัปโหลด/แก้ไขล่าสุด (ปี+จังหวัดที่แสดงอยู่) - ใช้โชว์ที่หัวการ์ด
            'updated_at' => $hiRows->max('updated_at'),
        ];

        return compact('kidney', 'menu', 'ht');
    }

    /**
     * ข้อมูล + ข้อความวิเคราะห์ของ popup "สถานการณ์แนวโน้ม" (ใช้ร่วมกันที่หน้าแรกและ /new-ht-cases)
     * $hiAllYears = แถว Hi ทุกปีของจังหวัดในขอบเขต (groupBy year), $yearTrend = อัตรา like-for-like รายปี
     */
    private function buildHtTrendPop(array $scopeProvinces, $provinceIds, $hiAllYears, $yearTrend, array $monthCols): ?array
    {
        // ---- ข้อมูลสำหรับ popup "สถานการณ์แนวโน้ม" (ปุ่มที่หัวการ์ด HT) ----
        // ใช้ชุดข้อมูลเดียวกับ $hiAllYears / $yearTrend ด้านบน จึงตรงกับตัวเลขในการ์ด
        // ปีล่าสุดที่ข้อมูลยังไม่ครบ 12 เดือนจะไม่ถูกนำไปเทียบอัตรารายปีโดยตรง (เทียบเฉพาะช่วงเดือนเดียวกันกับปีก่อน)
        $monthKeys = array_keys($monthCols);
        $monthLabels = array_values($monthCols);
        $yearRateMap = $yearTrend->keyBy('year');
        $popYearsInfo = $hiAllYears->sortKeys()->map(function ($rows, $y) use ($monthKeys, $yearRateMap) {
            $months = array_map(fn($c) => (int) $rows->sum($c), $monthKeys);
            $lastIdx = null;
            foreach ($months as $i => $v) {
                if ($v > 0) {
                    $lastIdx = $i;
                }
            }
            return [
                'year' => (string) $y,
                'cases' => (int) $rows->sum('total_a'),
                'rate' => ($yearRateMap->get((string) $y)['rate'] ?? null),
                'months' => $months,
                'last_idx' => $lastIdx,
                'complete' => $lastIdx === null || $lastIdx === 11,
            ];
        })->filter(fn($r) => $r['cases'] > 0)->values();

        $trendPop = null;
        if ($popYearsInfo->isNotEmpty()) {
            $popYearKeys = $popYearsInfo->pluck('year')->all();
            $popProvinces = collect($scopeProvinces)->map(function ($prov) use ($provinceIds, $hiAllYears, $popYearKeys) {
                $pid = $provinceIds->get($prov);
                $rates = [];
                $casesArr = [];
                foreach ($popYearKeys as $y) {
                    $rows = ($hiAllYears->get($y) ?? collect())->where('Province_id', $pid);
                    $c = (int) $rows->sum('total_a');
                    $t = (int) $rows->sum('target_b');
                    $casesArr[] = $c;
                    $rates[] = $t > 0 ? round($c / $t * 100000, 2) : null;
                }
                return ['name' => $prov, 'rate' => $rates, 'cases' => $casesArr];
            })->values();

            $nY = $popYearsInfo->count();
            $Linfo = $popYearsInfo->last();
            $Pinfo = $nY >= 2 ? $popYearsInfo[$nY - 2] : null;
            $pctOf = fn($new, $old) => $old > 0 ? ($new - $old) / $old * 100 : null;
            $toneOf = fn($p) => $p === null ? 'info' : (abs($p) < 2 ? 'flat' : ($p > 0 ? 'up' : 'down'));
            $verbOf = fn($p) => abs($p) < 2 ? 'ใกล้เคียงเดิม' : ($p > 0 ? 'เพิ่มขึ้น' : 'ลดลง');
            $sgn = fn($p) => ($p > 0 ? '+' : '') . number_format($p, 1) . '%';
            $insights = [];
            $summary = null;

            // 1) เทียบอัตรารายปี (เฉพาะปีที่ข้อมูลครบ 12 เดือน)
            $comp = $popYearsInfo->filter(fn($r) => $r['complete'] && $r['rate'] !== null)->values();
            $c1 = $c2 = null;
            if ($comp->count() >= 2) {
                $c1 = $comp[$comp->count() - 2];
                $c2 = $comp[$comp->count() - 1];
                $p = $pctOf($c2['rate'], $c1['rate']);
                if ($p !== null) {
                    // (ไม่แสดงเป็นการ์ดวิเคราะห์แล้ว - การ์ดแนวโน้มหน้าแรกแสดงเฉพาะข้อมูลรายเดือน แต่ยังใช้คำนวณป้ายสรุปหัวการ์ด)
                    $summary = ['tone' => $toneOf($p), 'label' => 'แนวโน้ม' . (abs($p) < 2 ? 'ทรงตัว' : ($p > 0 ? 'เพิ่มขึ้น' : 'ลดลง')), 'pct' => $sgn($p)];
                }
            }

            // 2) เทียบช่วงเดือนเดียวกันกับปีก่อน (ใช้ได้แม้ปีล่าสุดยังไม่ครบ)
            if ($Pinfo && $Linfo['last_idx'] !== null) {
                $k = $Linfo['last_idx'] + 1;
                $ytdL = array_sum(array_slice($Linfo['months'], 0, $k));
                $ytdP = array_sum(array_slice($Pinfo['months'], 0, $k));
                if ($ytdP > 0) {
                    $p = $pctOf($ytdL, $ytdP);
                    $span = $Linfo['complete'] ? 'ตลอดปี' : 'สะสมถึง' . $monthLabels[$Linfo['last_idx']];
                    $insights[] = [
                        'tone' => $toneOf($p),
                        'title' => 'เทียบช่วงเดียวกัน',
                        'text' => 'ปีงบ ' . $Linfo['year'] . ' ' . $span . ' มีผู้ป่วยใหม่ ' . number_format($ytdL) . ' คน เทียบช่วงเดียวกันของปีงบ ' . $Pinfo['year']
                            . ' (' . number_format($ytdP) . ' คน) ' . $verbOf($p) . ' (' . $sgn($p) . ')',
                    ];
                    if ($summary === null) {
                        $summary = ['tone' => $toneOf($p), 'label' => 'แนวโน้ม' . (abs($p) < 2 ? 'ทรงตัว' : ($p > 0 ? 'เพิ่มขึ้น' : 'ลดลง')), 'pct' => $sgn($p)];
                    }
                }
            }

            // 3) เดือนที่สูงสุดของปีล่าสุด
            if ($Linfo['last_idx'] !== null && $Linfo['last_idx'] >= 2) {
                $reported = array_slice($Linfo['months'], 0, $Linfo['last_idx'] + 1);
                $maxV = max($reported);
                $maxI = array_search($maxV, $reported);
                if ($maxV > 0) {
                    $insights[] = [
                        'tone' => 'info',
                        'title' => 'เดือนที่พบมากสุด',
                        'text' => 'ปีงบ ' . $Linfo['year'] . ' พบผู้ป่วยใหม่สูงสุดในเดือน' . $monthLabels[$maxI] . ' ' . number_format($maxV) . ' คน (เฉลี่ย ' . number_format(array_sum($reported) / count($reported)) . ' คนต่อเดือน)',
                    ];
                }
            }

            // 4) เดือนที่เพิ่มขึ้น/ลดลงจากเดือนก่อนมากสุด (เฉพาะเดือนที่มีข้อมูลแล้ว ของปีล่าสุด)
            if ($Linfo['last_idx'] !== null && $Linfo['last_idx'] >= 2) {
                $reportedM = array_slice($Linfo['months'], 0, $Linfo['last_idx'] + 1);
                $bestUp = $bestDown = null;
                for ($mi = 1; $mi < count($reportedM); $mi++) {
                    $d = $reportedM[$mi] - $reportedM[$mi - 1];
                    if ($d > 0 && ($bestUp === null || $d > $bestUp['d'])) {
                        $bestUp = ['i' => $mi, 'd' => $d];
                    }
                    if ($d < 0 && ($bestDown === null || $d < $bestDown['d'])) {
                        $bestDown = ['i' => $mi, 'd' => $d];
                    }
                }
                if ($bestUp) {
                    $pp = $pctOf($reportedM[$bestUp['i']], $reportedM[$bestUp['i'] - 1]);
                    $insights[] = [
                        'tone' => 'up',
                        'title' => 'เดือนที่เพิ่มขึ้นมากสุด',
                        'text' => 'ปีงบ ' . $Linfo['year'] . ' เดือน' . $monthLabels[$bestUp['i']] . ' เพิ่มขึ้น ' . number_format($bestUp['d']) . ' คน เทียบกับก่อนหน้า' . ($pp !== null ? ' (' . $sgn($pp) . ')' : ''),
                    ];
                }
                if ($bestDown) {
                    $pp = $pctOf($reportedM[$bestDown['i']], $reportedM[$bestDown['i'] - 1]);
                    $insights[] = [
                        'tone' => 'down',
                        'title' => 'เดือนที่ลดลงมากสุด',
                        'text' => 'ปีงบ ' . $Linfo['year'] . ' เดือน' . $monthLabels[$bestDown['i']] . ' ลดลง ' . number_format(abs($bestDown['d'])) . ' คน เทียบกับก่อนหน้า' . ($pp !== null ? ' (' . $sgn($pp) . ')' : ''),
                    ];
                }
            }

            // 5) ข้อควรระวัง: ปีล่าสุดยังไม่ครบปี
            if (!$Linfo['complete'] && $Linfo['last_idx'] !== null) {
                $insights[] = [
                    'tone' => 'info',
                    'title' => 'ข้อควรระวัง',
                    'text' => 'ปีงบ ' . $Linfo['year'] . ' มีข้อมูลถึงเดือน' . $monthLabels[$Linfo['last_idx']] . ' เท่านั้น ตัวเลขรายปีของปีนี้จึงยังต่ำกว่าปีเต็ม ไม่ควรเทียบตรงกับปีก่อน (ดูการเทียบช่วงเดียวกันแทน)',
                ];
            }
            if ($nY < 2) {
                $insights[] = [
                    'tone' => 'info',
                    'title' => 'ข้อมูลยังน้อย',
                    'text' => 'ตอนนี้มีข้อมูลเพียงปี ' . $Linfo['year'] . ' จึงยังเทียบแนวโน้มข้ามปีไม่ได้ เมื่อมีข้อมูลปีถัดไประบบจะคำนวณให้อัตโนมัติ',
                ];
            }

            $fillMonths = fn($info, $cut) => $info === null ? null : array_map(
                fn($v, $i) => ($cut !== null && $i > $cut) ? null : $v,
                $info['months'],
                array_keys($info['months'])
            );
            $trendPop = [
                'years' => $popYearKeys,
                'partial_year' => $Linfo['complete'] ? null : $Linfo['year'],
                'partial_label' => ($Linfo['last_idx'] !== null) ? $monthLabels[$Linfo['last_idx']] : null,
                'overall' => [
                    'rate' => $popYearsInfo->pluck('rate')->all(),
                    'cases' => $popYearsInfo->pluck('cases')->all(),
                ],
                'provinces' => $popProvinces->all(),
                'month_labels' => $monthLabels,
                'last_idx' => $Linfo['last_idx'],
                'month_latest' => ['year' => $Linfo['year'], 'values' => $fillMonths($Linfo, $Linfo['last_idx'])],
                'month_prev' => $Pinfo ? ['year' => $Pinfo['year'], 'values' => $fillMonths($Pinfo, null)] : null,
                // รายเดือนของ "ทุกปี" ที่มีข้อมูลรายเดือน (เรียงเก่า -> ใหม่) ให้กราฟรายเดือนแสดงครบทุกปีงบประมาณ
                'month_series' => $popYearsInfo->filter(fn($i) => $i['last_idx'] !== null)->map(fn($i) => [
                    'year' => $i['year'],
                    'values' => $fillMonths($i, $i['last_idx']),
                ])->values()->all(),
                'insights' => $insights,
                'summary' => $summary,
            ];
        }

        return $trendPop;
    }


    /**
     * ข้อมูลเทียบปีก่อนของกราฟ/ตารางรายเดือนในหน้า /new-ht-cases
     * - series: สูงสุด 2 ปีก่อนหน้าปีที่เลือก (เก่า -> ใหม่) พร้อมยอดรายเดือน/ยอดสะสม (ตัดเดือนที่ยังไม่มีข้อมูลเป็น null)
     * - insights: ข้อความวิเคราะห์ (tone/mode/title/text) คำนวณจากข้อมูลจริงชุดเดียวกับกราฟ
     *   mode: 'm' = แสดงเฉพาะมุมมองรายเดือน, 'c' = เฉพาะยอดสะสม, 'both' = ทั้งคู่
     */
    private function buildMonthlyCompare($selectedYear, $provinceName, $districtName, array $months, array $monthlyTrend): array
    {
        $keys = array_keys($months);
        $labels = array_values($months);
        $hasYear = $selectedYear && $selectedYear != 'ทั้งหมด';
        $series = [];
        if ($hasYear) {
            $q = Hi::query()->whereNotNull('year')->where('year', '<', $selectedYear);
            if ($provinceName && $provinceName != 'ทั้งหมด') {
                $pid = Province::where('province_name', $provinceName)->value('province_id');
                $pid ? $q->where('Province_id', $pid) : $q->whereRaw('1 = 0');
            }
            if ($districtName && $districtName != 'ทั้งหมด') {
                $q->where('District_name', $districtName);
            }
            foreach ($q->get(array_merge(['year'], $keys))->groupBy('year')->sortKeys() as $y => $items) {
                $vals = array_map(fn($k) => (int) $items->sum($k), $keys);
                $last = null;
                foreach ($vals as $i => $v) {
                    if ($v > 0) {
                        $last = $i;
                    }
                }
                if ($last === null) {
                    continue;
                }
                $run = 0;
                $cum = [];
                foreach ($vals as $i => $v) {
                    $run += $v;
                    $cum[] = $i <= $last ? $run : null;
                }
                $series[] = [
                    'year' => (string) $y,
                    'last_idx' => $last,
                    'complete' => $last === 11,
                    'total' => array_sum($vals),
                    'values' => array_map(fn($v, $i) => $i <= $last ? $v : null, $vals, array_keys($vals)),
                    'cum' => $cum,
                ];
            }
            $series = array_slice($series, -2);
        }

        $cur = array_values($monthlyTrend);
        $curLast = null;
        foreach ($cur as $i => $v) {
            if ($v > 0) {
                $curLast = $i;
            }
        }
        $prev = $series ? end($series) : null;
        $yLabel = $hasYear ? 'ปีงบ ' . $selectedYear : 'ทุกปีรวมกัน';
        $pctOf = fn($new, $old) => $old > 0 ? ($new - $old) / $old * 100 : null;
        $toneOf = fn($p) => $p === null ? 'info' : (abs($p) < 2 ? 'flat' : ($p > 0 ? 'up' : 'down'));
        $verbOf = fn($p) => abs($p) < 2 ? 'ใกล้เคียงเดิม' : ($p > 0 ? 'เพิ่มขึ้น' : 'ลดลง');
        $sgn = fn($p) => ($p > 0 ? '+' : '') . number_format($p, 1) . '%';
        $ins = [];

        if ($curLast === null) {
            $ins[] = ['tone' => 'info', 'mode' => 'both', 'title' => 'ยังไม่มีข้อมูล', 'text' => 'ตัวกรองที่เลือกยังไม่มีข้อมูลผู้ป่วยรายใหม่รายเดือน'];
        } else {
            $k = $curLast + 1;
            $ytd = array_sum(array_slice($cur, 0, $k));
            $span = $curLast === 11 ? 'ตลอดปี' : 'สะสมถึงเดือน' . $labels[$curLast];

            // 1) เทียบช่วงเดือนเดียวกันกับปีก่อน (เทียบเมื่อปีก่อนมีข้อมูลถึงเดือนเดียวกันเท่านั้น)
            if ($prev && $prev['last_idx'] >= $curLast) {
                $ytdP = array_sum(array_slice($prev['values'], 0, $k));
                $p = $pctOf($ytd, $ytdP);
                if ($p !== null) {
                    $ins[] = [
                        'tone' => $toneOf($p), 'mode' => 'both', 'title' => 'เทียบช่วงเดียวกัน',
                        'text' => $yLabel . ' ' . $span . ' มีผู้ป่วยใหม่ ' . number_format($ytd) . ' คน เทียบช่วงเดียวกันของปีงบ ' . $prev['year']
                            . ' (' . number_format($ytdP) . ' คน) ' . $verbOf($p) . ' (' . $sgn($p) . ')',
                    ];
                }
            }

            // 2) ยอดสะสม เทียบกับยอดรวมทั้งปีก่อน (ใช้เมื่อปีก่อนมีข้อมูลครบ 12 เดือน)
            if ($prev && $prev['complete'] && $prev['total'] > 0) {
                $ins[] = [
                    'tone' => 'info', 'mode' => 'c', 'title' => 'ยอดสะสมเทียบทั้งปีก่อน',
                    'text' => $yLabel . ' สะสมถึงเดือน' . $labels[$curLast] . ' แล้ว ' . number_format($ytd) . ' คน คิดเป็น '
                        . number_format($ytd / $prev['total'] * 100, 1) . '% ของยอดรวมทั้งปี ' . $prev['year'] . ' (' . number_format($prev['total']) . ' คน)',
                ];
            } elseif (!$prev) {
                $ins[] = [
                    'tone' => 'info', 'mode' => 'c', 'title' => 'ยอดสะสม',
                    'text' => $yLabel . ' สะสมถึงเดือน' . $labels[$curLast] . ' แล้ว ' . number_format($ytd) . ' คน (เฉลี่ย ' . number_format($ytd / $k) . ' คนต่อเดือน)',
                ];
            }

            // 3) เดือนที่พบมากสุด + 4) เดือนที่เปลี่ยนจากเดือนก่อนมากสุด (เฉพาะเดือนที่มีข้อมูลแล้ว)
            $reported = array_slice($cur, 0, $k);
            if (count($reported) >= 2 && max($reported) > 0) {
                $maxV = max($reported);
                $maxI = array_search($maxV, $reported);
                $ins[] = [
                    'tone' => 'info', 'mode' => 'm', 'title' => 'เดือนที่พบมากสุด',
                    'text' => $yLabel . ' พบผู้ป่วยใหม่สูงสุดในเดือน' . $labels[$maxI] . ' ' . number_format($maxV) . ' คน (เฉลี่ย ' . number_format(array_sum($reported) / count($reported)) . ' คนต่อเดือน)',
                ];
            }
            if (count($reported) >= 3) {
                $bestUp = $bestDown = null;
                for ($i = 1; $i < count($reported); $i++) {
                    $d = $reported[$i] - $reported[$i - 1];
                    if ($d > 0 && ($bestUp === null || $d > $bestUp['d'])) {
                        $bestUp = ['i' => $i, 'd' => $d];
                    }
                    if ($d < 0 && ($bestDown === null || $d < $bestDown['d'])) {
                        $bestDown = ['i' => $i, 'd' => $d];
                    }
                }
                if ($bestUp) {
                    $pp = $pctOf($reported[$bestUp['i']], $reported[$bestUp['i'] - 1]);
                    $ins[] = [
                        'tone' => 'up', 'mode' => 'm', 'title' => 'เดือนที่เพิ่มขึ้นมากสุด',
                        'text' => $yLabel . ' เดือน' . $labels[$bestUp['i']] . ' เพิ่มขึ้น ' . number_format($bestUp['d']) . ' คน เทียบกับก่อนหน้า' . ($pp !== null ? ' (' . $sgn($pp) . ')' : ''),
                    ];
                }
                if ($bestDown) {
                    $pp = $pctOf($reported[$bestDown['i']], $reported[$bestDown['i'] - 1]);
                    $ins[] = [
                        'tone' => 'down', 'mode' => 'm', 'title' => 'เดือนที่ลดลงมากสุด',
                        'text' => $yLabel . ' เดือน' . $labels[$bestDown['i']] . ' ลดลง ' . number_format(abs($bestDown['d'])) . ' คน เทียบกับก่อนหน้า' . ($pp !== null ? ' (' . $sgn($pp) . ')' : ''),
                    ];
                }
            }

            // 5) ข้อควรระวัง
            if ($hasYear && $curLast < 11) {
                $ins[] = [
                    'tone' => 'info', 'mode' => 'both', 'title' => 'ข้อควรระวัง',
                    'text' => 'ปีงบ ' . $selectedYear . ' มีข้อมูลถึงเดือน' . $labels[$curLast] . ' เท่านั้น ยอดสะสมจึงยังต่ำกว่าปีเต็ม ควรดูการเทียบช่วงเดียวกันกับปีก่อน',
                ];
            }
            if ($hasYear && !$prev) {
                $ins[] = ['tone' => 'info', 'mode' => 'both', 'title' => 'ยังไม่มีปีก่อนให้เทียบ', 'text' => 'ไม่พบข้อมูลรายเดือนของปีก่อนหน้า ตามตัวกรองจังหวัด/อำเภอที่เลือก จึงยังไม่แสดงเส้นเทียบ'];
            }
        }

        return ['year' => $hasYear ? (string) $selectedYear : null, 'last_idx' => $curLast, 'series' => $series, 'insights' => $ins];
    }
    /** อัตรา HT รายปีแบบ like-for-like (ตัวตั้งและตัวหารนับเฉพาะจังหวัดที่มีเป้าหมายประชากร) */
    private function htYearTrend($hiAllYears)
    {
        return $hiAllYears->sortKeys()->map(function ($rows, $y) {
            $byProv = $rows->groupBy('Province_id')->map(fn($r) => ['c' => (int) $r->sum('total_a'), 't' => (int) $r->sum('target_b')]);
            $withTarget = $byProv->filter(fn($x) => $x['t'] > 0);
            $t = $withTarget->sum('t');
            return [
                'year' => (string) $y,
                'cases' => (int) $byProv->sum('c'),
                'rate' => $t > 0 ? round($withTarget->sum('c') / $t * 100000, 2) : null,
            ];
        })->values()->filter(fn($r) => $r['cases'] > 0)->values();
    }

    /**
     * ฟีดสำหรับการ์ด/popup "สถานการณ์แนวโน้ม HT" ให้หน้าเว็บอัปเดตตัวเองเมื่อข้อมูลเปลี่ยน (poll เบาๆ ด้วย stamp)
     * - ?province=all|ชื่อจังหวัด (ขอบเขตเดียวกับที่หน้ากำลังแสดง)  - ?stamp=ค่าที่ได้ครั้งก่อน
     * - stamp ไม่เปลี่ยน -> ตอบ changed:false (ไม่คำนวณใหม่); เปลี่ยน -> ส่งข้อมูลทั้งชุด (แคชตาม stamp)
     */
    public function htTrendFeed(Request $request)
    {
        $targets = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];
        $province = (string) $request->query('province', 'all');
        $scope = in_array($province, $targets, true) ? [$province] : $targets;

        $row = DB::table((new Hi)->getTable())->selectRaw('COUNT(*) as c, MAX(updated_at) as u, COALESCE(SUM(total_a), 0) as s')->first();
        $stamp = md5(($row->c ?? 0) . '|' . ($row->u ?? '') . '|' . ($row->s ?? 0) . '|' . implode(',', $scope));
        $headers = ['Cache-Control' => 'no-store'];

        if ($request->boolean('only')) {
            return response()->json(['stamp' => $stamp], 200, $headers);
        }

        if ((string) $request->query('stamp') === $stamp) {
            return response()->json(['changed' => false, 'stamp' => $stamp], 200, $headers);
        }

        $trend = Cache::remember('ht_trend_feed_v1:' . $stamp, now()->addMinutes(10), fn () => $this->htTrendPopForScope($scope));

        return response()->json(['changed' => true, 'stamp' => $stamp, 'trend' => $trend], 200, $headers);
    }

    /** popup แนวโน้มของ /new-ht-cases: ดึงข้อมูลทุกปีของจังหวัดในขอบเขตเอง */
    private function htTrendPopForScope(array $scopeProvinces): ?array
    {
        $monthCols = [
            'm10_oct' => 'ต.ค.', 'm11_nov' => 'พ.ย.', 'm12_dec' => 'ธ.ค.', 'm01_jan' => 'ม.ค.',
            'm02_feb' => 'ก.พ.', 'm03_mar' => 'มี.ค.', 'm04_apr' => 'เม.ย.', 'm05_may' => 'พ.ค.',
            'm06_jun' => 'มิ.ย.', 'm07_jul' => 'ก.ค.', 'm08_aug' => 'ส.ค.', 'm09_sep' => 'ก.ย.',
        ];
        $provinceIds = Province::whereIn('province_name', $scopeProvinces)->pluck('province_id', 'province_name');
        $hiAllYears = Hi::whereIn('Province_id', $provinceIds->values())->whereNotNull('year')->get()->groupBy('year');
        return $this->buildHtTrendPop($scopeProvinces, $provinceIds, $hiAllYears, $this->htYearTrend($hiAllYears), $monthCols);
    }

    public function consumptionReport(Request $request)
    {
        // ข้อมูลของหน้านี้เป็นฟังก์ชันของตัวกรอง (query string) + เนื้อหาตาราง - แคชไว้ตามนั้น
        // คีย์ผูกกับจำนวนแถว/เวลาแก้ไขล่าสุดของตารางที่เกี่ยวข้อง จึงอัปเดตทันทีเมื่อข้อมูลเปลี่ยน
        $data = $this->cachedPageData('consumption-report', $request, [SaltAssessment::class, User::class, FiscalYear::class], fn () => $this->consumptionReportData($request), false);

        return view('pages.consumption-report', $data);
    }

    private function consumptionReportData(Request $request): array
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

        $agencyStatsRecords = $regionalAgencies->map(function ($agencyUser) use ($fiscalYear, $statsQLimit, $milestoneFields, $regionalReporters, $stepDefs) {
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

            // ขั้นตอน (1-5) ที่หน่วยงานนี้ดำเนินการแล้ว: ขั้น 5 นับเมื่อทำข้อย่อย 5.1-5.5 ข้อใดข้อหนึ่ง
            // (กติกาเดียวกับการ์ด "ขั้นตอนดำเนินงาน 5 ข้อ") - ใช้ทำการ์ดตัวชี้วัดด้านบน
            $doneSteps = [];
            foreach ($stepDefs as $step) {
                foreach ($step['fields'] as $f) {
                    if ($assessments->contains(fn($a) => !empty($a->$f))) {
                        $doneSteps[] = (string) $step['num'];
                        break;
                    }
                }
            }

            return (object) [
                'agency_key' => $agencyUser->agency_key,
                'province' => $agencyUser->province->province_name ?? 'N/A',
                'done_steps' => $doneSteps,
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

        // การ์ด "ตัวชี้วัดที่ดำเนินการได้ดี / ต้องเร่งดำเนินการ": นับว่ามีกี่หน่วยงาน (ตามตัวกรองเดียวกับกราฟ)
        // ที่ดำเนินการแต่ละขั้นตอนแล้ว - ได้ดี = 3 ขั้นที่หน่วยงานทำมากที่สุด,
        // ต้องเร่ง = ขั้นที่หน่วยงานทำน้อยกว่า 60% (เกณฑ์เดียวกับสีเขียว/ส้มของอันดับรายจังหวัด) เรียงจากน้อยสุด
        $indicatorTotal = $agencyStatsRecords->count();
        $indicatorRows = collect($stepDefs)->map(function ($step) use ($agencyStatsRecords, $indicatorTotal) {
            $done = $agencyStatsRecords->filter(fn($r) => in_array((string) $step['num'], $r->done_steps, true))->count();

            return [
                'key' => (string) $step['num'],
                'title' => $step['title'],
                'done' => $done,
                'total' => $indicatorTotal,
                'percent' => $indicatorTotal > 0 ? round($done / $indicatorTotal * 100, 1) : 0,
            ];
        });
        $indicatorTop = $indicatorRows->filter(fn($i) => $i['done'] > 0)->sortByDesc('percent')->take(3)->values();
        $indicatorPending = $indicatorRows->filter(fn($i) => $i['percent'] < 60)->sortBy('percent')->values();

        $years = FiscalYear::selectableYearsFor('salt_assessment', SaltAssessment::distinct()->pluck('fiscal_year'));

        return compact(
            'indicatorTop',
            'indicatorPending',
            'indicatorTotal',
            'paginatedSalt',
            'years',
            'fiscalYear',
            'selectedQuarter',
            'dbProvinces',
            'selectedProvince',
            'dbAgencies',
            'selectedAgency',
            'stats'
        );
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
        // แคช 5 นาที: ใช้ทุกครั้งที่เปิดหน้า/เปลี่ยนตัวกรอง แต่ปีใหม่เพิ่มนาน ๆ ครั้ง
        $allFiscalYears = Cache::remember('awareness_fiscal_years', now()->addMinutes(5), function () {
            return SodiumSurvey::query()->distinct()->pluck('fiscal_year');
        });

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

        $dashboardData = Cache::remember($dashboardCacheKey, now()->addMinutes(60), function () use ($statsQueryAll, $effectiveFiscalYear, $allFiscalYears, $request) {
        // --- ตารางสรุปรายปี (AwarenessYearGrid) ---------------------------
        // สแกน sodium_surveys ครั้งเดียวต่อปี (แคชไว้) แล้วตัวกรองจังหวัด/อำเภอแค่กรองจากตารางนั้นใน PHP
        $filterProvinceValue = $request->filled('province') ? (string) $request->input('province') : null;
        $filterDistrictValue = $request->filled('district') ? (string) $request->input('district') : null;
        $cellMatches = function ($province, $district) use ($filterProvinceValue, $filterDistrictValue) {
            return ($filterProvinceValue === null || (string) $province === $filterProvinceValue)
                && ($filterDistrictValue === null || (string) $district === $filterDistrictValue);
        };
        $scopeYears = $effectiveFiscalYear !== ''
            ? [(string) $effectiveFiscalYear]
            : $allFiscalYears->map(fn ($y) => (string) $y)->all();

        // จัดกลุ่มอายุเป็น 5 ช่วง (ลอจิกเดิม - ทำกับค่า age_range ที่ไม่ซ้ำ ไม่ใช่ทุกแถว)
        $ageBucket = function ($ageText) {
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
        };

        $provinceCounts = [];
        $genderCounts = [];
        $educationCounts = [];
        $ageCounts = [];
        foreach ($scopeYears as $scopeYear) {
            foreach (AwarenessYearGrid::forYear($scopeYear)['demo'] as [$gProvince, $gDistrict, $gGender, $gAge, $gEducation, $gCount]) {
                if (!$cellMatches($gProvince, $gDistrict)) {
                    continue;
                }
                $kProvince = $gProvince ?? '';
                $kGender = $gGender ?? '';
                $kEducation = $gEducation ?? '';
                $kAge = $ageBucket($gAge);
                $provinceCounts[$kProvince] = ($provinceCounts[$kProvince] ?? 0) + $gCount;
                $genderCounts[$kGender] = ($genderCounts[$kGender] ?? 0) + $gCount;
                $educationCounts[$kEducation] = ($educationCounts[$kEducation] ?? 0) + $gCount;
                $ageCounts[$kAge] = ($ageCounts[$kAge] ?? 0) + $gCount;
            }
        }
        $provinceStats = collect($provinceCounts);
        $genderStats = collect($genderCounts);
        $educationStats = collect($educationCounts);
        $ageStats = collect($ageCounts);

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
        $buildPassFailStats = function () use ($questionYears, $scoreYears, $scoreCalculators, $cellMatches, $request, $effectiveFiscalYear) {
            $byProvince = [];
            $byDistrict = [];

            foreach ($questionYears as $year) {
                if ($effectiveFiscalYear !== '' && (string) $year !== (string) $effectiveFiscalYear) {
                    continue;
                }
                foreach (AwarenessYearGrid::forYear($year)['pass'] ?? [] as [$gProvince, $gDistrict, $pass, $total]) {
                    if (!$cellMatches($gProvince, $gDistrict)) {
                        continue;
                    }

                    $byProvince[$gProvince] = $byProvince[$gProvince] ?? ['pass' => 0, 'total' => 0];
                    $byProvince[$gProvince]['pass'] += $pass;
                    $byProvince[$gProvince]['total'] += $total;

                    $byDistrict[$gDistrict] = $byDistrict[$gDistrict] ?? ['pass' => 0, 'total' => 0];
                    $byDistrict[$gDistrict]['pass'] += $pass;
                    $byDistrict[$gDistrict]['total'] += $total;
                }
            }

            // ปีแบบ "score": การให้คะแนนทีละแถวใน PHP คือส่วนที่แพงที่สุดของหน้านี้ (ถอด survey_data +
            // คำนวณราว 26 บทบาทต่อผู้ตอบ) และผลลัพธ์ "ไม่ขึ้นกับตัวกรองจังหวัด/อำเภอ" เลย -
            // จึงคำนวณครั้งเดียวต่อปี เก็บเป็นตาราง จังหวัด x อำเภอ -> [ผ่าน, ทั้งหมด] ไว้ในแคช แล้วให้ทุกตัวกรอง
            // (รวมถึง "ทั้งหมด") แค่กรองจากตารางนี้ แทนที่จะให้คะแนนทุกแถวใหม่ทุกครั้งที่เปลี่ยนจังหวัด/อำเภอ.
            // คีย์แคชผูกกับเวลาแก้ไขการตั้งค่าเช่นเดียวกับแคชหลักด้านบน (บันทึกตั้งค่าแล้วคำนวณใหม่ทันที).
            $filterProvince = $request->filled('province') ? (string) $request->input('province') : null;
            $filterDistrict = $request->filled('district') ? (string) $request->input('district') : null;
            $passSettingsVersion = AwarenessPassSetting::max('updated_at');

            foreach ($scoreYears as $year) {
                // เลือกปีงบประมาณเฉพาะปี -> นับเฉพาะปีนั้น (เหมือนเดิมที่ $statsQueryAll กรองด้วยปีอยู่แล้ว)
                if ($effectiveFiscalYear !== '' && (string) $year !== (string) $effectiveFiscalYear) {
                    continue;
                }
                $calculator = $scoreCalculators[$year];
                $grid = AwarenessYearGrid::scoreGrid($year, $calculator);

                foreach ($grid as $province => $districts) {
                    $province = (string) $province;
                    if ($filterProvince !== null && $province !== $filterProvince) {
                        continue;
                    }
                    foreach ($districts as $district => $cell) {
                        $district = (string) $district;
                        if ($filterDistrict !== null && $district !== $filterDistrict) {
                            continue;
                        }

                        $byProvince[$province] = $byProvince[$province] ?? ['pass' => 0, 'total' => 0];
                        $byProvince[$province]['pass'] += $cell['pass'];
                        $byProvince[$province]['total'] += $cell['total'];

                        $byDistrict[$district] = $byDistrict[$district] ?? ['pass' => 0, 'total' => 0];
                        $byDistrict[$district]['pass'] += $cell['pass'];
                        $byDistrict[$district]['total'] += $cell['total'];
                    }
                }
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
        $statsForPanel = function (int $panel) use ($effectiveFiscalYear, $cellMatches) {
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
            $rowsByBranch = [];
            $i = 0;
            foreach ($mappings as $m) {
                $branch = 'p' . $i++;
                $labelByBranch[$branch] = \App\Support\DefaultQuestionPanels::shortLabelFor($m->question_label ?: $m->question_key);
                $polarityByBranch[$branch] = $m->chart_polarity;
                $extremeValuesByBranch[$branch] = $m->chart_polarity_values;

                // ค่าคำตอบของข้อนี้ในปีของมัน จากตารางสรุปรายปี (แคช) กรองด้วยจังหวัด/อำเภอใน PHP
                // - ไม่ต้องสแกน JSON_EXTRACT ใหม่ทุกครั้งที่เปลี่ยนตัวกรอง
                $dist = [];
                $gridRows = AwarenessYearGrid::forYear($m->fiscal_year)['panels'][$m->question_key] ?? [];
                foreach ($gridRows as [$gProvince, $gDistrict, $gValue, $gCount]) {
                    if (!$cellMatches($gProvince, $gDistrict)) {
                        continue;
                    }
                    $gKey = $gValue ?? '';
                    $dist[$gKey] = ($dist[$gKey] ?? 0) + $gCount;
                }
                $rowsByBranch[$branch] = $dist;
            }

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
                $dist = $rowsByBranch[$branch] ?? [];
                if (isset($result[$label])) {
                    foreach ($dist as $value => $count) {
                        $result[$label][$value] = ($result[$label][$value] ?? 0) + $count;
                    }
                } else {
                    $result[$label] = $dist;
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
        $districts = Cache::remember('awareness_districts_list:' . md5((string) $request->input('province')), now()->addMinutes(30), function () use ($request) {
            return SodiumSurvey::when($request->filled('province'), function ($q) use ($request) {
                return $q->where('province_name', $request->province);
            })->distinct()->orderBy('district_name')->pluck('district_name');
        });

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
        // ข้อมูลของหน้านี้เป็นฟังก์ชันของตัวกรอง (query string) + เนื้อหาตาราง - แคชไว้ตามนั้น
        // คีย์ผูกกับจำนวนแถว/เวลาแก้ไขล่าสุดของตารางที่เกี่ยวข้อง จึงอัปเดตทันทีเมื่อข้อมูลเปลี่ยน
        $data = $this->cachedPageData('reduced-sodium-menu', $request, [ReducedSodiumMenu::class, User::class, FiscalYear::class], fn () => $this->reducedSodiumMenuData($request), $request->get('org_lock') == 1);

        return view('pages.reduced-sodium-menu', $data);
    }

    private function reducedSodiumMenuData(Request $request): array
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

        // เลือกเฉพาะคอลัมน์ที่ใช้คำนวณกราฟ - ไม่ต้องสร้างโมเดลเต็มทุกแถว
        $allData = (clone $query)->get(['id', 'province', 'sodium_before', 'sodium_after', 'kitchen_type', 'org_name', 'agency']);

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

        // ตารางเปรียบเทียบรายจังหวัด + ระดับการประเมิน (การ์ดใต้กราฟ 3 ใบ): คำนวณจากชุดข้อมูลเดียวกับกราฟด้านบน
        // (ตามตัวกรองของหน้านี้) - ค่าเฉลี่ยก่อน/หลังคิดเฉพาะเมนูที่วัดครบทั้งสองค่าเท่านั้น ไม่ใช้คนละชุดเมนู
        $insightTiers = ['ดีมาก' => 0, 'ดี' => 0, 'ปานกลาง' => 0, 'ควรปรับปรุง' => 0];
        foreach ($measuredMenus as $m) {
            $insightTiers[$evaluationTier($m->sodium_after)]++;
        }
        $insightProvinces = collect(['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'])
            ->map(function ($prov) use ($allData) {
                $all = $allData->where('province', $prov);
                $m = $all->filter(fn($x) => $x->sodium_before > 0 && $x->sodium_after > 0);
                $b = $m->count() > 0 ? round($m->avg('sodium_before'), 1) : null;
                $a = $m->count() > 0 ? round($m->avg('sodium_after'), 1) : null;
                return [
                    'province' => $prov,
                    'total' => $all->count(),
                    'measured' => $m->count(),
                    'avg_before' => $b,
                    'avg_after' => $a,
                    'reduction_percent' => ($b !== null && $b > 0) ? round(($b - $a) / $b * 100, 1) : null,
                ];
            })->values();
        $sodiumInsight = [
            'total' => $allData->count(),
            'measured' => $evaluatedCount,
            'avg_before' => $barChartData['before'],
            'avg_after' => $barChartData['after'],
            'reduction_percent' => $barChartData['before'] > 0 ? round(($barChartData['before'] - $barChartData['after']) / $barChartData['before'] * 100, 1) : null,
            'best_percent' => $evaluatedCount > 0 ? round($insightTiers['ดีมาก'] / $evaluatedCount * 100, 1) : null,
            'tiers' => $insightTiers,
            'provinces' => $insightProvinces,
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

        return compact(
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
            'sodiumEvalSummary',
            'sodiumInsight'
        );
    }

    public function reducedSodiumProducts(Request $request)
    {
        // ข้อมูลของหน้านี้เป็นฟังก์ชันของตัวกรอง (query string) + เนื้อหาตาราง - แคชไว้ตามนั้น
        // คีย์ผูกกับจำนวนแถว/เวลาแก้ไขล่าสุดของตารางที่เกี่ยวข้อง จึงอัปเดตทันทีเมื่อข้อมูลเปลี่ยน
        $data = $this->cachedPageData('reduced-sodium-products', $request, [\App\Models\ReducedSodiumProduct::class, User::class, FiscalYear::class], fn () => $this->reducedSodiumProductsData($request), $request->get('org_lock') == 1);

        return view('pages.reduced-sodium-products', $data);
    }

    private function reducedSodiumProductsData(Request $request): array
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

        // เฉพาะคอลัมน์ที่ใช้ทำกราฟ + จังหวัดของเจ้าของ (ไม่ต้องโหลดอำเภอ/ทุกคอลัมน์ของทุกแถว)
        $allData = (clone $query)->setEagerLoads([])->with('user.province')
            ->get(['user_id', 'product_type', 'standard_certification', 'province_name']);

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

        return compact(
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
        );
    }

    public function kidneyDHB()
    {
        return view('pages.kidney-dhb');
    }

    public function newHTCases(Request $request)
    {
        // ข้อมูลของหน้านี้เป็นฟังก์ชันของตัวกรอง (query string) + เนื้อหาตาราง - แคชไว้ตามนั้น
        // คีย์ผูกกับจำนวนแถว/เวลาแก้ไขล่าสุดของตารางที่เกี่ยวข้อง จึงอัปเดตทันทีเมื่อข้อมูลเปลี่ยน
        $data = $this->cachedPageData('new-ht-cases', $request, [Hi::class, User::class, FiscalYear::class], fn () => $this->newHTCasesData($request), $request->get('org_lock') == 1);

        return view('pages.new-ht-cases', $data);
    }

    /**
     * ปีเริ่มต้นของหน้า /new-ht-cases = ปีล่าสุดที่เปิดให้เลือก "และมีข้อมูลผู้ป่วยแล้ว"
     * (ปีใหม่เข้ามาแล้วจะกลายเป็นค่าเริ่มต้นเองเมื่อมีข้อมูล ไม่ต้องแก้โค้ด; ถ้ายังไม่มีข้อมูลปีไหนเลยใช้ปีล่าสุดที่เลือกได้)
     */
    private function defaultHiYear()
    {
        $withCases = Hi::whereNotNull('year')->where('total_a', '>', 0)->distinct()->pluck('year')->map(fn($y) => (int) $y);
        $selectable = FiscalYear::selectableYearsFor('hi', Hi::whereNotNull('year')->distinct()->pluck('year'));

        return $selectable->first(fn($y) => $withCases->contains((int) $y)) ?? $selectable->first();
    }

    private function newHTCasesData(Request $request): array
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
        // ตัวกรองไตรมาสถูกนำออกจากหน้านี้แล้ว - ใช้ยอดทั้งปีเสมอ (ลิงก์เก่าที่มี ?quarter= จะไม่มีผล)
        $selectedQuarter = 'ทั้งหมด';

        // Defaults if not provided (Only on first load without query params)
        if (!$request->has('year') && !$request->has('province') && !$lockedProvinceName) {
            $selectedYear = $this->defaultHiYear();
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

        $hiChartCols = ['Province_id', 'District_name', 'target_b', 'total_a',
            'm10_oct', 'm11_nov', 'm12_dec', 'm01_jan', 'm02_feb', 'm03_mar',
            'm04_apr', 'm05_may', 'm06_jun', 'm07_jul', 'm08_aug', 'm09_sep'];
        $allData = $query->get($hiChartCols);

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

        // กราฟ/ตารางรายเดือน: เส้นเทียบ 2 ปีก่อนหน้า (ตัวกรองจังหวัด/อำเภอเดียวกัน) + ข้อความวิเคราะห์จากข้อมูลจริง
        $monthlyCompare = $this->buildMonthlyCompare($selectedYear, $selectedProvinceName, $selectedDistrict, $months, $monthlyTrend);

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
        $mapAllData = $mapQuery->get($hiChartCols)->groupBy('Province_id');
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

        // เกณฑ์แบ่งสี "แผนที่อัตราป่วยต่อแสนประชากร (รายอำเภอ)" = ค่าเฉลี่ย ± 1 SD ของอัตราป่วยรายอำเภอ
        // "ภายในจังหวัดที่เลือก" (เกณฑ์เปลี่ยนอัตโนมัติตามข้อมูลของแต่ละจังหวัด/ปีงบประมาณ/ไตรมาส)
        //   แดง = เฉลี่ย + SD ขึ้นไป · เขียว = เฉลี่ย - SD ลงมา · เหลือง = ระหว่างสองค่านี้
        // SD = ส่วนเบี่ยงเบนมาตรฐานของตัวอย่าง (n-1) เหมือนฟังก์ชัน STDEV ของ Excel
        // อำเภอที่ไม่มีเป้าหมายประชากร (หาอัตราไม่ได้) ไม่นำมาคิด
        // อัตราปัดทศนิยม 2 ตำแหน่งก่อนคิด เพื่อให้ตรงกับตัวเลขที่แสดงบนหน้าจอ/ไฟล์ Excel
        $districtRateStats = null;
        if ($selectedProvinceName && $selectedProvinceName != 'ทั้งหมด') {
            $selProvId = $mapProvinceIdsByName->get($selectedProvinceName);
            $districtRates = collect($selProvId ? ($mapAllData->get($selProvId) ?? collect()) : [])
                ->groupBy('District_name')
                ->map(function ($items) use ($sumForSelectedPeriod) {
                    $target = $items->sum('target_b');
                    return $target > 0 ? round(($sumForSelectedPeriod($items) / $target) * 100000, 2) : null;
                })
                ->filter(fn($r) => $r !== null)
                ->values();
            $rateCount = $districtRates->count();
            $rateMean = $rateCount > 0 ? $districtRates->avg() : 0;
            $rateSd = $rateCount > 1
                ? sqrt($districtRates->sum(fn($r) => ($r - $rateMean) ** 2) / ($rateCount - 1))
                : 0;
            $hasBands = $rateCount > 1 && $rateSd > 0;
            $districtRateStats = [
                'province' => $selectedProvinceName,
                'count' => $rateCount,
                'mean' => round($rateMean, 2),
                'sd' => round($rateSd, 2),
                'low' => $hasBands ? round($rateMean - $rateSd, 2) : null,
                'high' => $hasBands ? round($rateMean + $rateSd, 2) : null,
            ];
        }

        // เกณฑ์แบ่งสี "แผนที่อัตราป่วยต่อแสนประชากร (รายจังหวัด)" - วิธีเดียวกับรายอำเภอ แต่คิดจาก 5 จังหวัดของเขต 10:
        //   แดง = เฉลี่ย + SD ขึ้นไป · เขียว = เฉลี่ย - SD ลงมา · เหลือง = ระหว่างสองค่านี้ (SD แบบตัวอย่าง n-1 เหมือน STDEV ใน Excel)
        // นับเฉพาะจังหวัดที่มีเป้าหมายประชากร และปัดอัตราเป็น 2 ตำแหน่งให้ตรงกับตัวเลขที่แสดงบนหน้าจอ
        $provRates = $mapProvinceTotals->filter(fn($p) => ($p['target_b'] ?? 0) > 0)
            ->map(fn($p) => round($p['rate'], 2))->values();
        $provRateCount = $provRates->count();
        $provRateMean = $provRateCount > 0 ? $provRates->avg() : 0;
        $provRateSd = $provRateCount > 1
            ? sqrt($provRates->sum(fn($r) => ($r - $provRateMean) ** 2) / ($provRateCount - 1))
            : 0;
        $provHasBands = $provRateCount > 1 && $provRateSd > 0;
        $provinceRateStats = [
            'count' => $provRateCount,
            'mean' => round($provRateMean, 2),
            'sd' => round($provRateSd, 2),
            'low' => $provHasBands ? round($provRateMean - $provRateSd, 2) : null,
            'high' => $provHasBands ? round($provRateMean + $provRateSd, 2) : null,
        ];

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

        // ปุ่ม "สถานการณ์แนวโน้ม" + ป้าย "ข้อมูลอัปเดตล่าสุด" ที่หัวหน้า (ใช้ตัวเลขชุดเดียวกับหน้าแรก)
        $htTargetProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];
        $htScopeProvinces = ($selectedProvinceName && $selectedProvinceName != 'ทั้งหมด') ? [$selectedProvinceName] : $htTargetProvinces;
        $htTrendPop = $this->htTrendPopForScope($htScopeProvinces);
        $htUpdatedQuery = Hi::query();
        if ($selectedYear && $selectedYear != 'ทั้งหมด') {
            $htUpdatedQuery->where('year', $selectedYear);
        }
        if ($selectedProvinceName && $selectedProvinceName != 'ทั้งหมด') {
            $htUpdatedProvId = Province::where('province_name', $selectedProvinceName)->value('province_id');
            $htUpdatedProvId ? $htUpdatedQuery->where('Province_id', $htUpdatedProvId) : $htUpdatedQuery->whereRaw('1 = 0');
        }
        $htUpdatedAt = $htUpdatedQuery->max('updated_at');

        return compact(
            'htTrendPop',
            'htUpdatedAt',
            'monthlyCompare',
            'monthlyTrend',
            'cumulativeTrend',
            'provinceTotals',
            'districtTotals',
            'mapProvinceTotals',
            'districtRateStats',
            'provinceRateStats',
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
        );
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
