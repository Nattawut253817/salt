<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use App\Models\FiscalYear;
use App\Models\Province;
use App\Models\AwarenessPassSetting;
use App\Imports\SodiumSurveyImport;
use App\Services\AwarenessPassResolver;
use App\Services\AwarenessScoreCalculator;
use App\Services\AwarenessPanelBreakdown;
use App\Exports\AwarenessInterpretationExport;
use App\Exports\AwarenessInterpretationQuestionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Admin screen for the awareness/sodium survey upload - list, filter,
 * import, and bulk-delete against sodium_surveys. Every fiscal year is the
 * same table now (see App\Models\SodiumSurvey), so nothing here branches
 * on which year is selected the way the old awareness_assessments /
 * awareness_assessments_fy69 split required.
 */
class AwarenessAssessmentController extends Controller
{
    /**
     * Builds the same filtered query used by index(), deleteFiltered() and
     * the export - kept in one place so the three stay in sync.
     */
    private function filteredQuery(Request $request)
    {
        $query = SodiumSurvey::query();

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->get('fiscal_year'));
        }
        if ($request->filled('province_name')) {
            $query->where('province_name', 'like', '%' . $request->get('province_name') . '%');
        }
        if ($request->filled('survey_start_date')) {
            $query->whereDate('survey_date', '>=', $request->get('survey_start_date'));
        }
        if ($request->filled('survey_end_date')) {
            $query->whereDate('survey_date', '<=', $request->get('survey_end_date'));
        }

        // "ตระหนักรู้สุขภาพ" (ผ่าน/ไม่ผ่าน) isn't a real column - it's the
        // same per-row AwarenessPassResolver verdict the "ตระหนักรู้สุขภาพ"
        // column on the table shows (see isAwarePass() below), which itself
        // depends on whichever method that row's own fiscal year has
        // chosen. There's no single SQL expression for that (the score
        // method walks all 26 rubric roles in PHP), so filtering on it
        // walks every row still matching the filters above once to collect
        // the matching ids, then narrows the query to just those - the same
        // "chunk through it" approach the /awareness report already uses
        // for the score method. A row whose year hasn't decided pass/fail
        // yet (the resolver returns null) never matches either choice.
        if ($request->filled('is_aware_pass')) {
            $wantPass = $request->get('is_aware_pass') === 'pass';
            $matchingIds = [];
            $mappingCache = [];
            (clone $query)->select(['id', 'fiscal_year', 'survey_data'])
                ->chunkById(500, function ($rows) use (&$matchingIds, &$mappingCache, $wantPass) {
                    foreach ($rows as $row) {
                        if (AwarenessPassResolver::isPass($row, $mappingCache) === $wantPass) {
                            $matchingIds[] = $row->id;
                        }
                    }
                });
            $query->whereIn('id', $matchingIds);
        }

        return $query;
    }

    // Show the import form and list of data
    public function index(Request $request)
    {
        $assessments = $this->filteredQuery($request)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Attach the "aware/pass" badge state for the rows on this page
        // (cheap - at most 15 rows) so the view doesn't need to know each
        // row's fiscal-year question layout to render it.
        $mappingCache = [];
        $calculatorCache = [];
        foreach ($assessments as $item) {
            $item->is_aware_pass = $this->isAwarePass($item, $mappingCache);
            $item->awareness_score = $this->awarenessScoreFor($item, $calculatorCache);
        }

        // Question labels for every fiscal year represented on this page,
        // so the detail modal (see the blade's SURVEY_QUESTION_LABELS) can
        // label any year's survey_data answers without knowing that year's
        // form shape ahead of time.
        $questionLabels = [];
        foreach ($assessments->pluck('fiscal_year')->unique() as $year) {
            $questionLabels[$year] = SurveyYearMapping::labelsFor($year);
        }

        // ปีงบประมาณ options for the filter dropdown AND the "นำเข้า Excel"
        // modal - real distinct years already in the data, plus any
        // admin-enabled years and minus any admin-hidden years from
        // "จัดการปีงบประมาณ" (FiscalYearController).
        $years = FiscalYear::selectableYearsFor('awareness', SodiumSurvey::query()->distinct()->pluck('fiscal_year'));
        $provinces = Province::orderBy('province_name')->pluck('province_name');

        return view('admin.awareness.import', compact('assessments', 'years', 'provinces', 'questionLabels'));
    }

    /**
     * Whether a row counts as "aware/pass" - delegates to
     * AwarenessPassResolver, which reads that fiscal year's own chosen
     * method ("ตั้งค่าเกณฑ์ความตระหนักรู้"'s "วิธีตั้งเกณฑ์" picker): either the
     * original 2-specific-questions rule, or (starting FY2569) the scoring
     * rubric's own pass/fail. A fiscal year with nothing configured yet for
     * its chosen method is reported as "pending" (null) rather than pass/
     * fail - same as always. $mappingCache is just handed straight through
     * as AwarenessPassResolver::isPass()'s own scratch cache, so a fiscal
     * year's mappings/calculator are still only built once per page no
     * matter how many rows on it share that year.
     */
    private function isAwarePass(SodiumSurvey $item, array &$mappingCache): ?bool
    {
        return AwarenessPassResolver::isPass($item, $mappingCache);
    }

    /**
     * The FY2569+ awareness scoring rubric's result for one row (sum_all +
     * ผ่าน/ไม่ผ่าน), or null when this fiscal year has no scoring rubric
     * configured at all yet ("ตั้งค่าคะแนนความตระหนักรู้") - same "pending"
     * spirit as isAwarePass(). $calculatorCache holds one AwarenessScore
     * Calculator instance per fiscal year (it loads that year's role->
     * question mappings once in its constructor), so a page of 15 rows
     * across a couple of years still only builds it a couple of times.
     */
    private function awarenessScoreFor(SodiumSurvey $item, array &$calculatorCache): ?array
    {
        $year = $item->fiscal_year;

        if (!array_key_exists($year, $calculatorCache)) {
            $calculatorCache[$year] = new AwarenessScoreCalculator($year);
        }

        return $calculatorCache[$year]->compute($item);
    }

    public function deleteFiltered(Request $request)
    {
        try {
            $query = $this->filteredQuery($request);
            $count = $query->count();

            if ($count === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลที่ตรงกับเงื่อนไขการกรอง'
                ], 404);
            }

            $query->delete();

            if ($request->filled('fiscal_year')) {
                Cache::forget("scoring_distinct_answers:{$request->get('fiscal_year')}");
            }

            return response()->json([
                'success' => true,
                'message' => 'ลบทิ้งข้อมูลสำเร็จ จำนวน ' . $count . ' รายการ',
                'deleted_count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Sodium Survey Delete Filtered Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    // Handle the import
    public function store(Request $request)
    {
        // Increase execution time and memory for large files
        ini_set('max_execution_time', 600); // 10 minutes
        ini_set('memory_limit', '512M');

        $request->validate([
            'fiscal_year' => 'required',
            'file' => 'required|mimes:xlsx,xls,csv',
            'duplicate_action' => 'required|in:skip,replace',
        ]);

        // The upload-progress XHR always sends this header - answer it
        // with JSON (the summary counts) instead of a redirect, so the
        // page doesn't have to burn its one-shot session flash on an
        // internal redirect the browser never actually shows.
        $wantsJson = $request->ajax() || $request->wantsJson();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $import = new SodiumSurveyImport($request->fiscal_year, $request->duplicate_action);

            Excel::import($import, $request->file('file'));

            \Illuminate\Support\Facades\DB::commit();

            Cache::forget("scoring_distinct_answers:{$request->fiscal_year}");

            $result = [
                'imported' => $import->getImportedCount(),
                'updated'  => $import->getUpdatedCount(),
                'skipped'  => $import->getSkippedCount(),
                'replaced' => $import->getReplacedCount(),
                'mode'     => $request->duplicate_action,
            ];

            if ($wantsJson) {
                return response()->json(['success' => true] + $result);
            }

            return redirect()->back()->with('import_result', $result);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $failures = $e->failures();
            foreach ($failures as $failure) {
                Log::warning("Import Validation Failure - Row {$failure->row()}: " . implode(', ', $failure->errors()));
            }
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้'], 422);
            }
            return redirect()->back()->with('error', 'ไม่สามารถบันทึกข้อมูลได้');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Sodium Survey Import Error: ' . $e->getMessage());
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage());
        }
    }

    /**
     * "แปลงผล" dashboard - a per-fiscal-year summary of that year's own
     * pass/fail method (AwarenessPassSetting::methodFor()): the FY2569+
     * scoring rubric (AwarenessScoreCalculator, "วิธีที่ 2: คิดจากคะแนนที่
     * ตั้งค่าไว้") for a METHOD_SCORE year, or the original two-criteria-
     * questions rule ("วิธีที่ 1: เลือกคำถามเฉพาะ") for a METHOD_QUESTIONS
     * year like FY2568 - either way: total ผ่าน/ไม่ผ่าน plus a province/
     * อำเภอ breakdown, and (METHOD_SCORE only) the average of each rubric
     * sub-total across every respondent that year. Only offered for a
     * fiscal year that has something genuinely usable configured for
     * whichever method it uses - see eligibleInterpretationYears().
     */
    public function interpretation(Request $request)
    {
        $eligibleYears = $this->eligibleInterpretationYears();

        $fiscalYear = $request->get('fiscal_year');
        if (!$fiscalYear || !$eligibleYears->contains($fiscalYear)) {
            $fiscalYear = $eligibleYears->max();
        }

        $method = $fiscalYear ? AwarenessPassSetting::methodFor($fiscalYear) : null;
        $stats = $fiscalYear ? $this->interpretationStats($fiscalYear) : null;

        return view('admin.awareness.interpretation', [
            'eligibleYears' => $eligibleYears,
            'fiscalYear' => $fiscalYear,
            'method' => $method,
            'stats' => $stats,
        ]);
    }

    /**
     * "Export แปลงผล" - one .xlsx for a single fiscal year, gated by the
     * same eligibility check as the dashboard above (never export a year
     * that's still missing its own method's configuration - see
     * interpretation()'s docblock). Which export class gets built depends on
     * that year's own method: AwarenessInterpretationExport (the 67-column
     * scoring-rubric layout) for METHOD_SCORE, or the much simpler
     * AwarenessInterpretationQuestionsExport for METHOD_QUESTIONS.
     */
    public function exportInterpretation(Request $request)
    {
        $fiscalYear = $request->get('fiscal_year');
        $eligibleYears = $this->eligibleInterpretationYears();

        if (!$fiscalYear || !$eligibleYears->contains($fiscalYear)) {
            return redirect()->route('admin.awareness.interpretation')
                ->with('error', 'กรุณาเลือกปีงบประมาณที่ตั้งค่าเกณฑ์ความตระหนักรู้ครบถ้วนแล้วก่อน Export');
        }

        // This walks every row of the fiscal year through the scoring
        // rubric AND builds 67 columns per row (up from 42 - every role got
        // its "คะแนนเต็ม" raw-code column back alongside its converted
        // score), so PhpSpreadsheet ends up holding a lot of cells in
        // memory while it builds the sheet - and sodium_surveys only ever
        // grows year over year, so any FIXED ceiling here is just a matter
        // of time before it's hit again. Rather than keep re-bumping this
        // number, remove the PHP-side memory ceiling entirely for this one
        // request (memory_limit -1 = bounded only by what the machine
        // actually has free, same as any other desktop app) - this route is
        // an admin-only, on-demand download, never a public/concurrent one,
        // so there's no risk of many requests competing for RAM at once.
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '-1');

        $fileName = 'แปลงผลความตระหนักรู้_ปี_' . $fiscalYear . '_' . now()->format('Ymd_His') . '.xlsx';

        $export = AwarenessPassSetting::methodFor($fiscalYear) === AwarenessPassSetting::METHOD_SCORE
            ? new AwarenessInterpretationExport($fiscalYear)
            : new AwarenessInterpretationQuestionsExport($fiscalYear);

        // "Export แปลงผล"'s own JS fetch()es this route directly and hides
        // its loading overlay once the response body finishes arriving, so
        // no server-side "ready" signal (cookie, header, etc.) is needed
        // here - the browser resolving the fetch promise is itself the
        // signal.
        return Excel::download($export, $fileName);
    }

    /**
     * Fiscal years eligible for "แปลงผล" - has real survey rows AND has
     * something genuinely usable configured for whichever method it has
     * chosen (AwarenessPassResolver::isConfiguredFor()): every one of the 26
     * rubric roles mapped for a METHOD_SCORE year, or both criteria
     * questions (เกณฑ์ข้อ 1 / เกณฑ์ข้อ 2) mapped for a METHOD_QUESTIONS year.
     * A year with nothing configured yet is left out of the picker entirely
     * rather than shown "pending".
     */
    private function eligibleInterpretationYears()
    {
        return SodiumSurvey::query()->distinct()->pluck('fiscal_year')
            ->filter(function ($year) {
                return AwarenessPassResolver::isConfiguredFor($year);
            })
            ->sort()
            ->values();
    }

    /**
     * "แปลงผล" stats for one fiscal year, branching on that year's own
     * chosen method - see interpretationStatsForScoreMethod() (METHOD_SCORE)
     * and interpretationStatsForQuestionsMethod() (METHOD_QUESTIONS) below.
     * 'panel_breakdown' (the "วิเคราะห์คำถามอื่นๆ แยกตามหมวด" summary, from
     * SurveyYearMapping::dashboard_panel - the same categorization the
     * public /awareness dashboard already charts) is only added for
     * METHOD_QUESTIONS years - a METHOD_SCORE year's own scoring rubric
     * already covers this ground (its 26-role breakdown), so this stays
     * intentionally METHOD_QUESTIONS-only rather than shown for both.
     *
     * Cached for a few minutes per fiscal year (same short-TTL-no-
     * invalidation approach MainController::awareness() already uses for
     * its own dashboard bundle): a METHOD_SCORE year still has to walk
     * every respondent row through the 26-role scoring rubric in PHP (no
     * single SQL expression captures it), which is the genuinely slow part
     * of switching "ปีงบประมาณ" on this page - caching means every OTHER
     * admin re-viewing the same year in that window gets it back instantly
     * instead of re-running the whole thing, and a fresh import or a
     * settings change still shows up within a few minutes on its own.
     */
    private function interpretationStats($fiscalYear): array
    {
        return Cache::remember('awareness_interpretation_stats:' . $fiscalYear, now()->addMinutes(5), function () use ($fiscalYear) {
            if (AwarenessPassSetting::methodFor($fiscalYear) === AwarenessPassSetting::METHOD_SCORE) {
                return $this->interpretationStatsForScoreMethod($fiscalYear);
            }

            $stats = $this->interpretationStatsForQuestionsMethod($fiscalYear);
            $panelData = AwarenessPanelBreakdown::forYearWithProvinces($fiscalYear);
            $stats['panel_breakdown'] = $panelData['all'];
            $stats['panel_breakdown_by_province'] = $panelData['by_province'];

            return $stats;
        });
    }

    /**
     * One pass over every respondent row of $fiscalYear, computing everything
     * the "แปลงผล" dashboard shows at once (rather than three separate
     * chunked walks) - total/pass/fail counts, a province and an อำเภอ
     * breakdown, and the average of each rubric sub-total. METHOD_SCORE
     * years only - see interpretationStatsForQuestionsMethod() for the
     * METHOD_QUESTIONS equivalent.
     */
    private function interpretationStatsForScoreMethod($fiscalYear): array
    {
        $calculator = new AwarenessScoreCalculator($fiscalYear);

        $total = 0;
        $pass = 0;
        $byProvince = [];
        $byDistrict = [];
        // Same district/pass/total shape as $byDistrict, but nested one more
        // level under each province - lets the "แยกตามอำเภอ" table on the
        // interpretation view filter down to just one province's districts
        // when its row is clicked, without a second query round-trip (the
        // whole nested structure is small enough to embed as JSON on the
        // page). Kept alongside the flat $byDistrict (rather than replacing
        // it) since the "all provinces" view still needs one flat,
        // alphabetically-sorted district list.
        $byProvinceDistrict = [];
        $categoryKeys = ['sum_2_1_to_2_5', 'sum_individual_belief', 'sum_environment_factor', 'sum_all_factor', 'sum_all'];
        $categorySums = array_fill_keys($categoryKeys, 0.0);
        // Same category sums as $categorySums above, but kept per-province
        // too - lets the "แปลงผล" page's province filter swap the "คะแนน
        // เฉลี่ยแยกตามหมวด" chart/numbers to just one province's average
        // client-side, without a second pass over every row.
        $categorySumsByProvince = [];

        SodiumSurvey::where('fiscal_year', $fiscalYear)
            ->select(['id', 'province_name', 'district_name', 'survey_data'])
            ->chunkById(500, function ($rows) use (&$total, &$pass, &$byProvince, &$byDistrict, &$byProvinceDistrict, &$categorySums, &$categorySumsByProvince, $calculator, $categoryKeys) {
                foreach ($rows as $row) {
                    $score = $calculator->compute($row);
                    if (!$score) {
                        continue;
                    }

                    $total++;
                    $isPass = $score['is_pass'];
                    if ($isPass) {
                        $pass++;
                    }

                    $province = $row->province_name ?: 'ไม่ระบุจังหวัด';
                    $byProvince[$province] = $byProvince[$province] ?? ['pass' => 0, 'total' => 0];
                    $byProvince[$province]['total']++;
                    if ($isPass) {
                        $byProvince[$province]['pass']++;
                    }

                    $district = $row->district_name ?: 'ไม่ระบุอำเภอ';
                    $byDistrict[$district] = $byDistrict[$district] ?? ['pass' => 0, 'total' => 0];
                    $byDistrict[$district]['total']++;
                    if ($isPass) {
                        $byDistrict[$district]['pass']++;
                    }

                    $byProvinceDistrict[$province] = $byProvinceDistrict[$province] ?? [];
                    $byProvinceDistrict[$province][$district] = $byProvinceDistrict[$province][$district] ?? ['pass' => 0, 'total' => 0];
                    $byProvinceDistrict[$province][$district]['total']++;
                    if ($isPass) {
                        $byProvinceDistrict[$province][$district]['pass']++;
                    }

                    $categorySumsByProvince[$province] = $categorySumsByProvince[$province] ?? array_fill_keys($categoryKeys, 0.0);

                    foreach ($categoryKeys as $key) {
                        $categorySums[$key] += $score[$key];
                        $categorySumsByProvince[$province][$key] += $score[$key];
                    }
                }
            });

        $categoryAverages = [];
        foreach ($categoryKeys as $key) {
            $categoryAverages[$key] = $total > 0 ? round($categorySums[$key] / $total, 2) : 0.0;
        }

        $categoryAveragesByProvince = [];
        foreach ($categorySumsByProvince as $province => $sums) {
            $provinceCount = $byProvince[$province]['total'] ?? 0;
            $categoryAveragesByProvince[$province] = [];
            foreach ($categoryKeys as $key) {
                $categoryAveragesByProvince[$province][$key] = $provinceCount > 0 ? round($sums[$key] / $provinceCount, 2) : 0.0;
            }
        }

        ksort($byProvince);
        ksort($byDistrict);
        ksort($byProvinceDistrict);
        ksort($categoryAveragesByProvince);
        foreach ($byProvinceDistrict as &$districtsInProvince) {
            ksort($districtsInProvince);
        }
        unset($districtsInProvince);

        return [
            'total' => $total,
            'pass' => $pass,
            'fail' => $total - $pass,
            'by_province' => $byProvince,
            'by_district' => $byDistrict,
            'by_province_district' => $byProvinceDistrict,
            'category_averages' => $categoryAverages,
            'category_averages_by_province' => $categoryAveragesByProvince,
        ];
    }

    /**
     * Same total/pass/fail + province/อำเภอ breakdown shape as
     * interpretationStatsForScoreMethod() above, but for a METHOD_QUESTIONS
     * fiscal year (like FY2568): there's no scoring rubric here, just the
     * two criteria questions (เกณฑ์ข้อ 1 = is_aware_health, เกณฑ์ข้อ 2 =
     * is_know_limit), so unlike the score method this CAN be answered with
     * one SQL aggregate query (AwarenessPassResolver::
     * passFailCountsByProvinceAndDistrict()) instead of walking every
     * respondent row in PHP - the old per-row chunkById() version of this
     * method was the slow part of switching "ปีงบประมาณ" to a
     * METHOD_QUESTIONS year with several thousand rows.
     * 'category_averages' comes back empty - the dashboard view hides the
     * rubric-only sections whenever it does.
     */
    private function interpretationStatsForQuestionsMethod($fiscalYear): array
    {
        $total = 0;
        $pass = 0;
        $byProvince = [];
        $byDistrict = [];
        $byProvinceDistrict = [];

        foreach (AwarenessPassResolver::passFailCountsByProvinceAndDistrict($fiscalYear) as $row) {
            $rowPass = (int) $row->pass_count;
            $rowTotal = (int) $row->total_count;
            if ($rowTotal === 0) {
                continue;
            }

            $total += $rowTotal;
            $pass += $rowPass;

            $province = $row->province_name ?: 'ไม่ระบุจังหวัด';
            $byProvince[$province] = $byProvince[$province] ?? ['pass' => 0, 'total' => 0];
            $byProvince[$province]['pass'] += $rowPass;
            $byProvince[$province]['total'] += $rowTotal;

            $district = $row->district_name ?: 'ไม่ระบุอำเภอ';
            $byDistrict[$district] = $byDistrict[$district] ?? ['pass' => 0, 'total' => 0];
            $byDistrict[$district]['pass'] += $rowPass;
            $byDistrict[$district]['total'] += $rowTotal;

            $byProvinceDistrict[$province] = $byProvinceDistrict[$province] ?? [];
            // Each (province, district) pair is its own GROUP BY row here
            // (unlike the old per-row walk, nothing else can add to it), so
            // a plain assignment is enough - no need to accumulate into an
            // existing entry.
            $byProvinceDistrict[$province][$district] = ['pass' => $rowPass, 'total' => $rowTotal];
        }

        ksort($byProvince);
        ksort($byDistrict);
        ksort($byProvinceDistrict);
        foreach ($byProvinceDistrict as &$districtsInProvince) {
            ksort($districtsInProvince);
        }
        unset($districtsInProvince);

        return [
            'total' => $total,
            'pass' => $pass,
            'fail' => $total - $pass,
            'by_province' => $byProvince,
            'by_district' => $byDistrict,
            'by_province_district' => $byProvinceDistrict,
            'category_averages' => [],
            'category_averages_by_province' => [],
        ];
    }
}
