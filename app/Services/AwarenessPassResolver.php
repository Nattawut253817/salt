<?php

namespace App\Services;

use App\Models\AwarenessPassSetting;
use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single place that decides "is this respondent ตระหนักรู้/ผ่านเกณฑ์", per
 * whichever method its fiscal year has chosen (AwarenessPassSetting::
 * methodFor()) - METHOD_QUESTIONS (the original "answer 2 specific
 * questions with a chosen value" rule) or METHOD_SCORE (the FY2569+ scoring
 * rubric, AwarenessScoreCalculator, passing at >= its own threshold).
 *
 * Every place in the app that used to hardcode the METHOD_QUESTIONS logic
 * (AwarenessAssessmentController's admin-list badge, MainController's home
 * map and its /awareness report charts) now goes through this instead, so a
 * fiscal year that switches its method changes consistently everywhere at
 * once - see "ตั้งค่าเกณฑ์ความตระหนักรู้"'s new "วิธีตั้งเกณฑ์" picker.
 *
 * METHOD_QUESTIONS stays a single SQL boolean condition (cheap to run as a
 * SUM(CASE WHEN ...) aggregate across thousands of rows at once).
 * METHOD_SCORE has no such single-expression shortcut - it's 26 separate
 * per-question scores, several of them averaged together first - so the
 * aggregate helper below computes it by walking actual rows in PHP via
 * AwarenessScoreCalculator (chunked, so a whole fiscal year is never loaded
 * into memory at once) instead of reimplementing that formula in raw SQL.
 */
class AwarenessPassResolver
{
    public static function methodFor($fiscalYear): string
    {
        return AwarenessPassSetting::methodFor($fiscalYear);
    }

    /**
     * Whether a fiscal year has ANYTHING usable configured yet for its
     * chosen method - drives the "รอเกณฑ์การประเมิน" / "pending" states that
     * used to check only the METHOD_QUESTIONS criteria.
     */
    public static function isConfiguredFor($fiscalYear): bool
    {
        if (self::methodFor($fiscalYear) === AwarenessPassSetting::METHOD_SCORE) {
            return (new AwarenessScoreCalculator($fiscalYear))->isFullyConfigured();
        }

        return SurveyYearMapping::criteriaMappingsFor($fiscalYear, 'is_aware_health')->isNotEmpty()
            && SurveyYearMapping::criteriaMappingsFor($fiscalYear, 'is_know_limit')->isNotEmpty();
    }

    /**
     * One respondent's pass/fail, or null if their fiscal year's chosen
     * method has nothing configured yet ("pending", same spirit as the
     * older isAwarePass()/AwarenessScoreCalculator::compute() both already
     * used). $cache is caller-owned scratch space - reuse the SAME array
     * across every row on a page so a fiscal year's mappings/calculator are
     * only built once no matter how many rows share it.
     */
    public static function isPass(SodiumSurvey $row, array &$cache): ?bool
    {
        $year = $row->fiscal_year;

        if (self::methodFor($year) === AwarenessPassSetting::METHOD_SCORE) {
            if (!array_key_exists($year, $cache['score'] ?? [])) {
                $cache['score'][$year] = new AwarenessScoreCalculator($year);
            }
            $result = $cache['score'][$year]->compute($row);
            return $result['is_pass'] ?? null;
        }

        if (!array_key_exists($year, $cache['questions'] ?? [])) {
            $cache['questions'][$year] = [
                SurveyYearMapping::criteriaMappingsFor($year, 'is_aware_health'),
                SurveyYearMapping::criteriaMappingsFor($year, 'is_know_limit'),
            ];
        }
        [$awareMappings, $limitMappings] = $cache['questions'][$year];
        if ($awareMappings->isEmpty() || $limitMappings->isEmpty()) {
            return null;
        }

        $data = $row->survey_data ?? [];
        $passesRole = function ($mappings) use ($data) {
            foreach ($mappings as $m) {
                $values = !empty($m->criteria_pass_values) ? $m->criteria_pass_values : SurveyYearMapping::DEFAULT_CRITERIA_PASS_VALUES;
                if (in_array($data[$m->question_key] ?? null, $values, true)) {
                    return true;
                }
            }
            return false;
        };

        return $passesRole($awareMappings) && $passesRole($limitMappings);
    }

    /**
     * pass/total counts grouped by an arbitrary column (e.g. 'province_name'
     * or 'district_name') for ONE fiscal year, respecting whichever method
     * it's configured for - [] when that method has nothing configured yet
     * for this year (treated as "pending" by callers, same as before).
     * $baseQuery should already carry every OTHER filter the caller wants
     * (province/district/etc) but NOT a fiscal_year condition - this adds
     * its own so the same $baseQuery can be reused across several years.
     */
    public static function passFailCountsByColumn($fiscalYear, Builder $baseQuery, string $groupColumn): array
    {
        if (self::methodFor($fiscalYear) === AwarenessPassSetting::METHOD_SCORE) {
            $calculator = new AwarenessScoreCalculator($fiscalYear);
            if (!$calculator->isFullyConfigured()) {
                return [];
            }

            $counts = [];
            // 2000 (was 500): compute() is CPU work, not I/O, so the real
            // cost here is per-row PHP looping, not per-chunk query time -
            // a bigger chunk just means fewer round trips to get through
            // the same total rows, with select() already keeping each row
            // narrow (3 columns, not a full model hydration of every
            // column).
            (clone $baseQuery)->where('fiscal_year', $fiscalYear)
                ->select([$groupColumn, 'survey_data', 'id'])
                ->chunkById(2000, function ($rows) use (&$counts, $calculator, $groupColumn) {
                    foreach ($rows as $row) {
                        $key = $row->$groupColumn;
                        $counts[$key] = $counts[$key] ?? ['pass' => 0, 'total' => 0];
                        $counts[$key]['total']++;
                        $score = $calculator->compute($row);
                        if ($score && $score['is_pass']) {
                            $counts[$key]['pass']++;
                        }
                    }
                });

            return $counts;
        }

        $conditions = self::questionsMethodSqlConditions($fiscalYear);
        if ($conditions === null) {
            return [];
        }
        [$awareCond, $limitCond] = $conditions;

        $rows = (clone $baseQuery)->where('fiscal_year', $fiscalYear)
            ->select($groupColumn)
            ->selectRaw("SUM(CASE WHEN {$awareCond} AND {$limitCond} THEN 1 ELSE 0 END) as pass_count")
            ->selectRaw('COUNT(*) as total_count')
            ->groupBy($groupColumn)
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row->$groupColumn] = ['pass' => (int) $row->pass_count, 'total' => (int) $row->total_count];
        }
        return $counts;
    }

    /**
     * pass/total counts grouped by BOTH province_name AND district_name at
     * once (one row per province+district pair actually present in the
     * data) for a METHOD_QUESTIONS fiscal year - a single SQL aggregate
     * query instead of walking every respondent row in PHP. Used by
     * "แปลงผล" (AwarenessAssessmentController::interpretationStatsForQuestions
     * Method()) to build its total/province/district breakdown without the
     * old per-row chunkById() pass, which was the slow part of switching
     * fiscal year on that page for a METHOD_QUESTIONS year with several
     * thousand rows. Returns an empty collection when this year's method
     * isn't configured yet (no single-question mapped to either criteria
     * role) - same "pending" treatment every other method here uses.
     */
    public static function passFailCountsByProvinceAndDistrict($fiscalYear): \Illuminate\Support\Collection
    {
        $conditions = self::questionsMethodSqlConditions($fiscalYear);
        if ($conditions === null) {
            return collect();
        }
        [$awareCond, $limitCond] = $conditions;

        return SodiumSurvey::where('fiscal_year', $fiscalYear)
            ->select(['province_name', 'district_name'])
            ->selectRaw("SUM(CASE WHEN {$awareCond} AND {$limitCond} THEN 1 ELSE 0 END) as pass_count")
            ->selectRaw('COUNT(*) as total_count')
            ->groupBy(['province_name', 'district_name'])
            ->get();
    }

    /**
     * [awareCond, limitCond] - the two "expr IN (...)" SQL fragments a
     * METHOD_QUESTIONS fiscal year's criteria mappings resolve to (see
     * passFailCountsByColumn()'s own docblock for why this can be a single
     * SQL condition where METHOD_SCORE cannot), or null when this year's
     * criteria aren't configured yet (either role has no question mapped).
     * Shared by every grouped-count query above so they can never
     * disagree about what "pass" means for a given fiscal year.
     */
    private static function questionsMethodSqlConditions($fiscalYear): ?array
    {
        $awareMappings = SurveyYearMapping::criteriaMappingsFor($fiscalYear, 'is_aware_health');
        $limitMappings = SurveyYearMapping::criteriaMappingsFor($fiscalYear, 'is_know_limit');
        if ($awareMappings->isEmpty() || $limitMappings->isEmpty()) {
            return null;
        }

        $cond = function ($mappings): string {
            $parts = [];
            foreach ($mappings as $m) {
                $values = !empty($m->criteria_pass_values) ? $m->criteria_pass_values : SurveyYearMapping::DEFAULT_CRITERIA_PASS_VALUES;
                if (empty($values)) {
                    continue;
                }
                $expr = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$m->question_key}'))";
                $parts[] = "{$expr} IN (" . SurveyYearMapping::sqlInList($values) . ")";
            }
            return $parts ? '(' . implode(' OR ', $parts) . ')' : '1=0';
        };

        return [$cond($awareMappings), $cond($limitMappings)];
    }
}
