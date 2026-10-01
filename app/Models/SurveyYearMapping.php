<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per question that a given fiscal year's survey form asks,
 * keyed by the question_key used inside SodiumSurvey::survey_data.
 *
 * Populated automatically from each uploaded file's header row (see
 * SodiumSurveyImport::syncMappings()) so a brand new year's form never
 * needs a code change - only semantic_key (used to find "the awareness
 * question" etc. across years for reporting) needs a human to confirm it,
 * and only for the handful of questions reports actually rely on.
 */
class SurveyYearMapping extends Model
{
    // The only two semantic_key values the "aware/pass" criteria (home
    // dashboard map, /awareness report, admin list badge) ever look for -
    // set from the admin settings screen. A question can also carry some
    // other semantic_key (e.g. 'add_seasoning_cook', used by the home
    // dashboard's metric cards) - that settings screen never touches those.
    const CRITERIA_ROLES = ['is_aware_health', 'is_know_limit'];

    // Fallback accepted answers for a criteria question that hasn't had
    // criteria_pass_values chosen yet - matches what every year used
    // before that column existed, so nothing changes for an already-
    // configured year until an admin explicitly picks different answers
    // from "ตั้งค่าเกณฑ์ความตระหนักรู้".
    const DEFAULT_CRITERIA_PASS_VALUES = ['ใช่', 'เคย'];

    // The fixed breakdown panels on the /awareness report. Keyed by the
    // panel number actually stored in dashboard_panel (stable - never
    // renumbered, so existing data never needs a migration if a panel is
    // ever added again). DASHBOARD_PANEL_DISPLAY_ORDER controls the order
    // these are shown/offered in the UI, independent of that stored number.
    //
    // A 5th panel ("พฤติกรรมการบริโภคโซเดียม") briefly existed here but was
    // removed - that same name/content already lives on the home dashboard
    // (MainController::index()'s "Awareness Metrics Card Data" section), so
    // it was a duplicate on /awareness rather than a new thing.
    const DASHBOARD_PANEL_COUNT = 4;

    const DASHBOARD_PANEL_TITLES = [
        1 => 'พฤติกรรมการบริโภคที่เสี่ยง (ความถี่การทาน)',
        2 => 'พฤติกรรมการปรุงและการทานนอกบ้าน',
        3 => 'พฤติกรรมสุขภาพและการสั่งอาหาร',
        4 => 'ความตระหนักรู้ ทัศนคติ และความพยายาม',
    ];

    // Display/offer order for the panels above.
    const DASHBOARD_PANEL_DISPLAY_ORDER = [1, 2, 3, 4];

    // Presentation metadata for the "ตั้งค่าแดชบอร์ด" card picker - keyed the
    // same as DASHBOARD_PANEL_TITLES.
    const DASHBOARD_PANEL_META = [
        1 => ['icon' => 'fa-triangle-exclamation', 'color' => '#e74c3c', 'description' => 'ความถี่ในการบริโภคอาหารเสี่ยงโซเดียมสูง เช่น อาหารแช่แข็ง อาหารหมักดอง'],
        2 => ['icon' => 'fa-utensils', 'color' => '#e67e22', 'description' => 'พฤติกรรมการเติมเครื่องปรุงระหว่างปรุง/บนโต๊ะ และการทานอาหารนอกบ้าน'],
        3 => ['icon' => 'fa-heart-pulse', 'color' => '#3498db', 'description' => 'การทำอาหารทานเองและพฤติกรรมการสั่งอาหารในเชิงสุขภาพ'],
        4 => ['icon' => 'fa-brain', 'color' => '#2ecc71', 'description' => 'ระดับความสำคัญ ความพยายาม และความรู้ในการลดโซเดียม'],
    ];

    protected $fillable = [
        'fiscal_year',
        'question_key',
        'question_label',
        'semantic_key',
        'criteria_pass_values',
        'behavior_roles',
        'dashboard_panel',
        'score_role',
        'answer_scores',
        'chart_polarity',
        'chart_polarity_values',
        'sort_order',
        'is_fixed_column',
        'column_index',
    ];

    protected $casts = [
        'criteria_pass_values' => 'array',
        'behavior_roles' => 'array',
        'chart_polarity_values' => 'array',
        'answer_scores' => 'array',
    ];

    /**
     * The question_key registered for a given fiscal year + semantic key,
     * or null if that year's form has no question tagged with it yet.
     */
    public static function questionKeyFor($fiscalYear, string $semanticKey): ?string
    {
        return static::where('fiscal_year', $fiscalYear)
            ->where('semantic_key', $semanticKey)
            ->orderBy('id')
            ->value('question_key');
    }

    /**
     * Which of that question's actual answer values count as "ผ่าน" for
     * this fiscal year + semantic role - the admin's choice (from "ตั้งค่า
     * เกณฑ์ความตระหนักรู้" for is_aware_health/is_know_limit, or from its
     * "คอลัมน์สำหรับการ์ด 'พฤติกรรมการบริโภคโซเดียม'" section for the home
     * dashboard's 10 behavior roles) if one was made, otherwise $default -
     * what that role assumed before this was configurable for it, so
     * nothing changes for an already-configured year/role until an admin
     * explicitly picks different answers. $default falls back to
     * DEFAULT_CRITERIA_PASS_VALUES (['ใช่', 'เคย']) when not given, which is
     * only right for is_aware_health/is_know_limit - every other role
     * should pass its own literal default explicitly.
     */
    public static function passValuesFor($fiscalYear, string $semanticKey, array $default = self::DEFAULT_CRITERIA_PASS_VALUES): array
    {
        $values = static::where('fiscal_year', $fiscalYear)
            ->where('semantic_key', $semanticKey)
            ->orderBy('id')
            ->value('criteria_pass_values');

        if (is_string($values)) {
            $values = json_decode($values, true);
        }

        return !empty($values) ? $values : $default;
    }

    /**
     * Every question this fiscal year currently tagged with a given
     * criteria role (semantic_key) - a role can now be backed by more than
     * one question at once (same idea as the independent behavior_roles
     * mechanism used elsewhere), each keeping its own criteria_pass_values;
     * ANY one of them matching a row's answer counts as that role being
     * satisfied for that row. Ordered by id so "the first one tagged"
     * (still useful for a UI/legacy caller that only wants one) is stable.
     */
    public static function criteriaMappingsFor($fiscalYear, string $role)
    {
        return static::where('fiscal_year', $fiscalYear)
            ->where('semantic_key', $role)
            ->orderBy('id')
            ->get(['id', 'question_key', 'question_label', 'criteria_pass_values']);
    }

    /**
     * Every question this fiscal year that's tagged for a given home-
     * dashboard behavior role (e.g. 'add_seasoning_cook'), via the
     * independent behavior_roles JSON column - NOT semantic_key /
     * criteria_pass_values. A role can be backed by more than one question
     * at once (an admin can pick several from "คอลัมน์สำหรับการ์ด 'พฤติกรรม
     * การบริโภคโซเดียม'"), and the same question can carry a behavior role
     * alongside an unrelated semantic_key (e.g. is_aware_health) at the
     * same time, since behavior_roles doesn't interact with semantic_key
     * or criteria_pass_values at all.
     *
     * behavior_roles is stored as a JSON OBJECT keyed by role name (see the
     * add_behavior_roles migration), so this fetches every row tagged for
     * *any* role and filters in PHP rather than a JSON-path WHERE - simpler
     * and safe at this table's size. Each returned row's criteria_pass_
     * values attribute is overwritten (in memory only, never saved) with
     * THIS role's own pass-values from behavior_roles[$role] - which may
     * be null (not chosen yet) - so callers can read $mapping->
     * criteria_pass_values exactly like the single-role code used to, and
     * fall back to a literal default when it's empty.
     */
    public static function behaviorMappingsFor($fiscalYear, string $role)
    {
        return static::where('fiscal_year', $fiscalYear)
            ->whereNotNull('behavior_roles')
            ->orderBy('id')
            ->get(['id', 'question_key', 'question_label', 'behavior_roles'])
            ->filter(fn ($m) => array_key_exists($role, (array) $m->behavior_roles))
            ->map(function ($m) use ($role) {
                $m->setAttribute('criteria_pass_values', ((array) $m->behavior_roles)[$role] ?? null);
                return $m;
            })
            ->values();
    }

    /**
     * A safely-escaped SQL "'a','b','c'" fragment (NOT including the
     * surrounding parens) for embedding a pass-values list into a raw
     * "column IN (...)" expression.
     */
    public static function sqlInList(array $values): string
    {
        $escaped = array_map(fn ($v) => "'" . addslashes((string) $v) . "'", $values);

        return $escaped ? implode(',', $escaped) : "''";
    }

    /**
     * [question_key => ['label' => ..., 'sort_order' => ...]] for one fiscal
     * year, used to label a SodiumSurvey row's survey_data answers without
     * knowing that year's form shape ahead of time. Excludes the fixed
     * demographic columns (is_fixed_column) - those never appear inside
     * survey_data, they're plain columns on SodiumSurvey itself.
     */
    public static function labelsFor($fiscalYear): array
    {
        return static::where('fiscal_year', $fiscalYear)
            ->where('is_fixed_column', false)
            ->orderBy('sort_order')
            ->get(['question_key', 'question_label', 'sort_order'])
            ->mapWithKeys(fn ($row) => [
                $row->question_key => [
                    'label'      => $row->question_label,
                    'sort_order' => $row->sort_order,
                ],
            ])
            ->toArray();
    }

    /**
     * This fiscal year's fixed demographic columns (hospital_name ..
     * congenital_disease), ordered by their raw Excel position - the list
     * "ตั้งค่าแดชบอร์ด"'s gender/age/education column pickers choose from.
     * Empty until at least one file has been uploaded for this year (see
     * SodiumSurveyImport::syncMappings()).
     */
    public static function fixedColumnsFor($fiscalYear)
    {
        // Some fiscal years (imported/edited across many earlier iterations
        // of this feature, some predating firstOrNew-based syncMappings())
        // ended up with more than one is_fixed_column row at the same
        // column_index - harmless leftover data, but it made "ตั้งค่า
        // แดชบอร์ด"'s demographic-field picker list every fixed column
        // twice. Only the first row (lowest id) at each column_index is
        // ever actually referenced elsewhere (DemographicFieldMapping
        // overrides store a column_index, not a mapping id), so keeping
        // just that one here is safe - it doesn't touch or delete the
        // stale duplicate row, just stops showing it a second time.
        return static::where('fiscal_year', $fiscalYear)
            ->where('is_fixed_column', true)
            ->orderBy('column_index')
            ->orderBy('id')
            ->get()
            ->unique('column_index')
            ->values();
    }

    /**
     * [role_key => SurveyYearMapping] for every AwarenessScoreCalculator::
     * ROLES role that has a question assigned to it this fiscal year (see
     * "ตั้งค่าคะแนนความตระหนักรู้"). A role with no question assigned yet is
     * simply missing from the returned array - callers treat that as "this
     * year's score isn't configured (yet)" rather than a hard error, same
     * spirit as criteriaMappingsFor()'s empty collection.
     */
    public static function scoreRoleMappingsFor($fiscalYear): array
    {
        return static::where('fiscal_year', $fiscalYear)
            ->whereNotNull('score_role')
            ->get(['id', 'question_key', 'question_label', 'score_role', 'answer_scores'])
            ->keyBy('score_role')
            ->all();
    }
}
