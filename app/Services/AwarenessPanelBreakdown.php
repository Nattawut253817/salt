<?php

namespace App\Services;

use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use App\Support\DefaultQuestionPanels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The same 4 behavior/attitude "หมวด" (panel) breakdown already charted on
 * the public /awareness dashboard (MainController::awareness()'s own
 * statsForPanel() closure, driven by SurveyYearMapping::dashboard_panel -
 * see App\Support\DefaultQuestionPanels for what each panel means) - reused
 * here for "แปลงผล" so admins get a categorized summary of every OTHER
 * question a fiscal year's form asked, beyond whichever 2 (METHOD_QUESTIONS)
 * or 26 (METHOD_SCORE) questions that year's own pass/fail method already
 * covers. A respondent's individual answers to these questions are already
 * visible one at a time via the "รายละเอียดการประเมิน" modal on the admin
 * list - this is the fiscal-year-wide "how did everyone answer" summary of
 * the same questions, categorized instead of one long flat list.
 *
 * Unlike MainController::awareness()'s version (which can merge several
 * fiscal years' identically-worded questions into one bar for an "all
 * years" view, via a UNION ALL across years), this is always scoped to
 * exactly ONE fiscal year - the one currently selected on "แปลงผล" - so
 * it's a much simpler one-query-per-question GROUP BY, no cross-year
 * merging needed. A fiscal year's questions are typically a handful (well
 * under 20), so the per-question query count here is not a concern for an
 * admin-only, on-demand page.
 */
class AwarenessPanelBreakdown
{
    /**
     * [panel => ['title', 'icon', 'color', 'questions' => [
     *     ['label', 'total', 'distribution' => [['value', 'count', 'pct'], ...]],
     *     ...
     * ]]] for every panel (1-4) that has at least one question mapped for
     * $fiscalYear, ordered by panel then by that question's own sort_order.
     * A panel with nothing mapped this year is simply absent from the
     * returned array - empty entirely when this fiscal year has no
     * dashboard_panel-tagged questions at all yet (nothing to add).
     */
    public static function forYear($fiscalYear): array
    {
        return self::compute($fiscalYear)['all'];
    }

    /**
     * Same panel/question/distribution shape as forYear(), plus a
     * 'by_province' breakdown - [province_name => same panel shape as
     * 'all'] - so the "แปลงผล" page's province filter can swap this card's
     * content client-side, exactly like it already does for the province
     * table/chart and the total/pass/fail counts, instead of the panel
     * breakdown staying stuck showing every province at once regardless of
     * what's selected. Built from the SAME per-question queries as
     * forYear() (each one now also grouped by province_name), so this
     * costs no extra SQL round-trips over calling forYear() alone.
     */
    public static function forYearWithProvinces($fiscalYear): array
    {
        return self::compute($fiscalYear);
    }

    /**
     * Cached for 5 minutes per fiscal year - same TTL as
     * AwarenessAssessmentController::interpretationStats(), and
     * deliberately shared by BOTH forYear() and forYearWithProvinces()
     * (rather than caching at the public-method level) so the two call
     * sites reuse one another's cached result instead of each computing
     * their own copy: the "แปลงผล" dashboard page reads this via
     * forYearWithProvinces() inside its own outer cache, while "Export
     * แปลงผล" (AwarenessInterpretationQuestionsExport::sheets()) calls
     * forYear() directly with no outer cache of its own - before this, an
     * export click always re-ran every one of this fiscal year's
     * per-question GROUP BY queries from scratch, even seconds after the
     * dashboard had just computed and cached the very same breakdown. Now
     * an export right after viewing the dashboard (or a second export
     * shortly after the first) hits this cache and skips the SQL
     * entirely. A fresh import or settings change still shows up within a
     * few minutes on its own, same trade-off as the outer cache.
     */
    private static function compute($fiscalYear): array
    {
        return Cache::remember('awareness_panel_breakdown:' . $fiscalYear, now()->addMinutes(5), function () use ($fiscalYear) {
            return self::computeUncached($fiscalYear);
        });
    }

    private static function computeUncached($fiscalYear): array
    {
        $mappings = SurveyYearMapping::where('fiscal_year', $fiscalYear)
            ->whereNotNull('dashboard_panel')
            ->orderBy('dashboard_panel')
            ->orderBy('sort_order')
            ->get(['question_key', 'question_label', 'dashboard_panel']);

        if ($mappings->isEmpty()) {
            return ['all' => [], 'by_province' => []];
        }

        $panelTitles = SurveyYearMapping::DASHBOARD_PANEL_TITLES;
        $panelMeta = SurveyYearMapping::DASHBOARD_PANEL_META;

        $buildQuestion = function ($mapping, array $counts, int $total): array {
            // Most-common answer first - reads better than an arbitrary
            // GROUP BY order, and needs no admin-configured "polarity" to
            // decide a direction (unlike the /awareness chart, this is a
            // plain summary, not a "good vs bad" framing).
            arsort($counts);

            $distribution = [];
            foreach ($counts as $value => $count) {
                $distribution[] = [
                    'value' => $value,
                    'count' => $count,
                    'pct' => $total > 0 ? round($count / $total * 100, 1) : 0,
                ];
            }

            return [
                'label' => DefaultQuestionPanels::shortLabelFor($mapping->question_label ?: $mapping->question_key),
                'total' => $total,
                'distribution' => $distribution,
            ];
        };

        $ensurePanel = function (array &$panels, int $panel) use ($panelTitles, $panelMeta): void {
            if (!isset($panels[$panel])) {
                $meta = $panelMeta[$panel] ?? [];
                $panels[$panel] = [
                    'title' => $panelTitles[$panel] ?? ('หมวด ' . $panel),
                    'icon' => $meta['icon'] ?? 'fa-chart-simple',
                    'color' => $meta['color'] ?? '#4f46e5',
                    'questions' => [],
                ];
            }
        };

        $panelsAll = [];
        $panelsByProvince = [];

        foreach ($mappings as $mapping) {
            $panel = (int) $mapping->dashboard_panel;
            $expr = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$mapping->question_key}'))";

            // Grouped by province_name TOO (not just the answer value) - one
            // query per question either way, this just slices the same
            // result set finer so both the "all provinces" totals and every
            // individual province's counts come out of it together.
            $rows = SodiumSurvey::where('fiscal_year', $fiscalYear)
                ->select('province_name', DB::raw("{$expr} as field_value"), DB::raw('COUNT(*) as aggregate_count'))
                ->groupBy('province_name', DB::raw($expr))
                ->get();

            $countsAll = [];
            $totalAll = 0;
            $countsByProvince = [];
            $totalByProvince = [];

            foreach ($rows as $row) {
                $value = ($row->field_value !== null && $row->field_value !== '') ? $row->field_value : 'ไม่ระบุ';
                $count = (int) $row->aggregate_count;
                $province = $row->province_name ?: 'ไม่ระบุจังหวัด';

                $countsAll[$value] = ($countsAll[$value] ?? 0) + $count;
                $totalAll += $count;

                $countsByProvince[$province] = $countsByProvince[$province] ?? [];
                $countsByProvince[$province][$value] = ($countsByProvince[$province][$value] ?? 0) + $count;
                $totalByProvince[$province] = ($totalByProvince[$province] ?? 0) + $count;
            }

            $ensurePanel($panelsAll, $panel);
            $panelsAll[$panel]['questions'][] = $buildQuestion($mapping, $countsAll, $totalAll);

            foreach ($countsByProvince as $province => $counts) {
                if (!isset($panelsByProvince[$province])) {
                    $panelsByProvince[$province] = [];
                }
                $ensurePanel($panelsByProvince[$province], $panel);
                $panelsByProvince[$province][$panel]['questions'][] = $buildQuestion($mapping, $counts, $totalByProvince[$province]);
            }
        }

        ksort($panelsAll);
        ksort($panelsByProvince);
        foreach ($panelsByProvince as &$provincePanels) {
            ksort($provincePanels);
        }
        unset($provincePanels);

        return ['all' => $panelsAll, 'by_province' => $panelsByProvince];
    }
}
