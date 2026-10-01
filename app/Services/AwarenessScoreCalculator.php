<?php

namespace App\Services;

use App\Http\Controllers\SurveyQuestionSettingsController;
use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;

/**
 * Computes the FY2569+ awareness-survey scoring rubric: each respondent's
 * raw answers -> a 0-32 "Sum all" total -> a ผ่าน/ไม่ผ่าน result.
 *
 * The exact arithmetic below was reverse-engineered (and verified exact
 * against all 10,578 rows of the reference export, awareness_interpretation_
 * round2_2569.xlsx) from a real worked example the user supplied - it is NOT
 * a guess. The one number in that reference file that did NOT hold up under
 * verification was the "14.2 คะแนน" prose threshold in a separate
 * interpretation image; the actual rule used consistently by every row in
 * the reference file is "Sum all >= 19.2" (= exactly ร้อยละ 60 of the 32-
 * point total), confirmed with the user - see self::PASS_THRESHOLD.
 *
 * WHAT STAYS FIXED (this class): the shape of the rubric - which roles feed
 * which sub-total, which sub-totals average vs. sum vs. max, and the final
 * threshold. This mirrors the fixed structure of the reference file's own
 * "Total=" column headers (8 / 10 / 10 / 8 / 22 / 32) and isn't expected to
 * change year over year the way question wording does.
 *
 * WHAT'S CONFIGURABLE PER FISCAL YEAR (admin "ตั้งค่าคะแนนความตระหนักรู้"
 * screen, SurveyQuestionSettingsController::scoring()): which of that year's
 * actual questions (SurveyYearMapping::question_key) fills each ROLES slot,
 * and - since sodium_surveys stores literal Thai answer TEXT, not the 1-5
 * numeric codes the reference file's raw columns use - what numeric score
 * each of that question's own real recorded answers is worth
 * (SurveyYearMapping::answer_scores). This is the same "pick the real
 * answers, don't guess the wording" approach the existing "ตั้งค่าเกณฑ์ความ
 * ตระหนักรู้" screen already uses for criteria_pass_values, generalized from
 * a boolean (pass/fail) to a numeric score per answer.
 */
class AwarenessScoreCalculator
{
    // ------------------------------------------------------------------
    // Fixed rubric shape
    // ------------------------------------------------------------------

    // Which top-level sub-total a role (or, for a grouped role, its group)
    // feeds. See self::compute() for exactly how these four combine.
    const BUCKET_SECTION2 = 'sum_2_1_to_2_5';
    const BUCKET_INDIVIDUAL_BELIEF = 'sum_individual_belief';
    const BUCKET_ENVIRONMENT_FACTOR = 'sum_environment_factor';
    const BUCKET_ADDON = 'addon_to_all_factor';

    /**
     * Every fixed role in the rubric, in display order, grouped into the 4
     * sections shown on the settings screen. Each entry:
     *  - 'section'   => 'behavior' | 'label' | 'belief' | 'environment' -
     *                   which card group this is shown under.
     *  - 'title'     => short role name for the settings card.
     *  - 'hint'      => the FY2569 form's own wording for this question
     *                   (help text only - the admin still picks the real
     *                   question from a dropdown, never matched by text).
     *  - 'ui_type'   => 'per_answer' (pick a question, then assign a score
     *                   to each of its real recorded answers) or
     *                   'knowledge' (pick a question, then type the
     *                   accepted correct numeric value(s) - see below).
     *  - 'max_score' => 2 for every ordinary role, 1 for the 6 label-
     *                   awareness roles (each is a single ใช่/ไม่ใช่-shaped
     *                   answer, not a 5-point scale).
     *  - 'group'     => null for a standalone role (its own score feeds
     *                   'contributes_to' directly), or one of GROUPS's keys
     *                   for a role that first combines with its groupmates.
     *  - 'contributes_to' => set only on a standalone role, or once per
     *                   group (see GROUPS) - which bucket the (possibly
     *                   grouped) score feeds.
     */
    const ROLES = [
        // Section 2: พฤติกรรมการบริโภคโซเดียม (Sum 2.1-2.4, Total=8)
        'sec2_1' => ['section' => 'behavior', 'title' => '2.1 การเติมน้ำปลา/เครื่องปรุงเพิ่ม', 'hint' => '2.1 ท่านเติมน้ำปลา...', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_SECTION2],
        'sec2_2' => ['section' => 'behavior', 'title' => '2.2 การบริโภคผลิตภัณฑ์อาหารสำเร็จรูป', 'hint' => '2.2 บริโภคอาหารแปรรูป...', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_SECTION2],
        'sec2_3' => ['section' => 'behavior', 'title' => '2.3 การบริโภคอาหารแปรรูปหรือหมักดอง', 'hint' => '2.3 บริโภคอาหารหมักดอง...', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_SECTION2],
        'sec2_4' => ['section' => 'behavior', 'title' => '2.4 การพยายามจำกัดการบริโภคโซเดียม', 'hint' => '2.4 จำกัดการบริโภคโซเดียม...', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_SECTION2],

        // Food-label awareness (part of Sum 2.1-2.5, Total=10) - 3 "เคยเห็น"
        // + 3 "เคยใช้" roles, combined per group (see GROUPS) as "answered
        // yes to at least one of the three" -> 1, else 0.
        'label_seen_1' => ['section' => 'label', 'title' => 'เคยเห็นฉลากโภชนาการ', 'hint' => 'ฉลากโภชนาการ (เห็น)', 'ui_type' => 'per_answer', 'max_score' => 1, 'group' => 'label_seen', 'contributes_to' => null],
        'label_seen_2' => ['section' => 'label', 'title' => 'เคยเห็นฉลาก GDA', 'hint' => 'ฉลาก GDA (เห็น)', 'ui_type' => 'per_answer', 'max_score' => 1, 'group' => 'label_seen', 'contributes_to' => null],
        'label_seen_3' => ['section' => 'label', 'title' => 'เคยเห็นสัญลักษณ์ทางเลือกสุขภาพ', 'hint' => 'สัญลักษณ์ทางเลือกสุขภาพ (เห็น)', 'ui_type' => 'per_answer', 'max_score' => 1, 'group' => 'label_seen', 'contributes_to' => null],
        'label_used_1' => ['section' => 'label', 'title' => 'เคยใช้ฉลากโภชนาการ', 'hint' => 'ฉลากโภชนาการ (ใช้)', 'ui_type' => 'per_answer', 'max_score' => 1, 'group' => 'label_used', 'contributes_to' => null],
        'label_used_2' => ['section' => 'label', 'title' => 'เคยใช้ฉลาก GDA', 'hint' => 'ฉลาก GDA (ใช้)', 'ui_type' => 'per_answer', 'max_score' => 1, 'group' => 'label_used', 'contributes_to' => null],
        'label_used_3' => ['section' => 'label', 'title' => 'เคยใช้สัญลักษณ์ทางเลือกสุขภาพ', 'hint' => 'สัญลักษณ์ทางเลือกสุขภาพ (ใช้)', 'ui_type' => 'per_answer', 'max_score' => 1, 'group' => 'label_used', 'contributes_to' => null],

        // Section 3, Individual belief (Sum individual belief, Total=10)
        'belief_knowledge' => ['section' => 'belief', 'title' => '3.1 ความรู้: ปริมาณโซเดียมที่แนะนำต่อวัน (Knowledge)', 'hint' => '3.1 ปริมาณโซเดียมในอาหาร...', 'ui_type' => 'knowledge', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_ADDON],
        'belief_susceptibility' => ['section' => 'belief', 'title' => '3.2 การรับรู้ความเสี่ยง (Perceived susceptibility)', 'hint' => '3.2 การรับประทานเกลือ...เสี่ยงโรค', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_INDIVIDUAL_BELIEF],
        'belief_severity_1' => ['section' => 'belief', 'title' => '3.3 การรับรู้ความรุนแรง ข้อ 1 (Perceived severity)', 'hint' => '3.3 โรคจากเกลือ...ค่าใช้จ่ายสูง', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'severity', 'contributes_to' => null],
        'belief_severity_2' => ['section' => 'belief', 'title' => '3.4 การรับรู้ความรุนแรง ข้อ 2 (Perceived severity)', 'hint' => '3.4 ความดันสูง...เสี่ยงโรค', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'severity', 'contributes_to' => null],
        'belief_benefits' => ['section' => 'belief', 'title' => '3.5 การรับรู้ประโยชน์ (Perceived benefits)', 'hint' => '3.5 ลดเกลือ...ลดเสี่ยง NCDs', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_INDIVIDUAL_BELIEF],
        'belief_barriers_1' => ['section' => 'belief', 'title' => '3.6 การรับรู้อุปสรรค ข้อ 1 (Perceived barriers)', 'hint' => '3.6 ผลิตภัณฑ์ลดเกลือ...หาซื้อยาก', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'barriers', 'contributes_to' => null],
        'belief_barriers_2' => ['section' => 'belief', 'title' => '3.7 การรับรู้อุปสรรค ข้อ 2 (Perceived barriers)', 'hint' => '3.7 อาหารเค็มน้อย...ไม่อร่อย', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'barriers', 'contributes_to' => null],
        'belief_barriers_3' => ['section' => 'belief', 'title' => '3.8 การรับรู้อุปสรรค ข้อ 3 (Perceived barriers)', 'hint' => '3.8 เครื่องปรุงรส...ทำให้อร่อย', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'barriers', 'contributes_to' => null],
        'belief_selfefficacy' => ['section' => 'belief', 'title' => '3.9 ความมั่นใจในการปรับพฤติกรรม (Self-Efficacy)', 'hint' => '3.9 มั่นใจว่าปรับเปลี่ยนพฤติกรรมได้', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_INDIVIDUAL_BELIEF],

        // Section 3, Environmental factor (Sum environment factor, Total=8)
        'env_policy_1' => ['section' => 'environment', 'title' => '3.10 นโยบาย/กฎหมาย ข้อ 1 (Policy)', 'hint' => '3.10 ควรมีกฎหมายคุมโซเดียม', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'policy', 'contributes_to' => null],
        'env_policy_2' => ['section' => 'environment', 'title' => '3.11 นโยบาย/กฎหมาย ข้อ 2 (Policy)', 'hint' => '3.11 ภาษีโซเดียมช่วยคุมได้', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'policy', 'contributes_to' => null],
        'env_cue' => ['section' => 'environment', 'title' => '3.12 แรงกระตุ้นให้ปรับพฤติกรรม (Cue to action)', 'hint' => '3.12 คนใกล้ชิดป่วย...จะปรับพฤติกรรม', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_ADDON],
        'env_social_1' => ['section' => 'environment', 'title' => '3.13 คนรอบข้าง ข้อ 1 (Social)', 'hint' => '3.13 คนใกล้ชิดชอบทานเค็ม', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'social', 'contributes_to' => null],
        'env_social_2' => ['section' => 'environment', 'title' => '3.14 คนรอบข้าง ข้อ 2 (Social)', 'hint' => '3.14 คนใกล้ชิดแนะนำลดโซเดียม', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => 'social', 'contributes_to' => null],
        'env_media' => ['section' => 'environment', 'title' => '3.15 การรับสื่อรณรงค์ลดโซเดียม (Media)', 'hint' => '3.15 เคยได้ยินสื่อลดโซเดียม', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_ENVIRONMENT_FACTOR],
        'env_physical' => ['section' => 'environment', 'title' => '3.16 สภาพแวดล้อมทางกายภาพ (Physical environment)', 'hint' => '3.16 ร้านอาหารใกล้บ้านมีเมนูลดโซเดียม', 'ui_type' => 'per_answer', 'max_score' => 2, 'group' => null, 'contributes_to' => self::BUCKET_ENVIRONMENT_FACTOR],
    ];

    /**
     * Roles that combine before feeding a bucket. 'aggregate' is 'average'
     * (severity/barriers/policy/social - matches "Average 3.x-3.y" in the
     * reference file) or 'max' (label_seen/label_used - "answered yes to at
     * least one of these three" -> 1, else 0; every member role is itself
     * already a 0/1 score, same shape as an "any() " check).
     */
    const GROUPS = [
        'severity'    => ['aggregate' => 'average', 'contributes_to' => self::BUCKET_INDIVIDUAL_BELIEF],
        'barriers'    => ['aggregate' => 'average', 'contributes_to' => self::BUCKET_INDIVIDUAL_BELIEF],
        'policy'      => ['aggregate' => 'average', 'contributes_to' => self::BUCKET_ENVIRONMENT_FACTOR],
        'social'      => ['aggregate' => 'average', 'contributes_to' => self::BUCKET_ENVIRONMENT_FACTOR],
        'label_seen'  => ['aggregate' => 'max', 'contributes_to' => self::BUCKET_SECTION2],
        'label_used'  => ['aggregate' => 'max', 'contributes_to' => self::BUCKET_SECTION2],
    ];

    // Sum all (Total=32) >= this -> ผ่าน, else ไม่ผ่าน. Exactly ร้อยละ 60 of
    // 32 - verified against all 10,578 rows of the reference export (zero
    // exceptions); the "14.2 คะแนน" figure in a separate interpretation
    // image did not hold up under that same check and was confirmed with
    // the user NOT to be the real threshold.
    const PASS_THRESHOLD = 19.2;

    // Reserved answer_scores key used only by the 'knowledge' ui_type role
    // (belief_knowledge) - holds the list of accepted correct numeric
    // values instead of a per-answer score map. A respondent's free-text
    // answer scores 2 when its LEADING digit run exactly equals one of
    // these (e.g. answer "2000-2500" and accepted value "2000" both match
    // on the leading run "2000"), else 0.
    const KNOWLEDGE_CORRECT_KEY = '__correct_prefix__';

    protected $fiscalYear;

    /** @var array<string, SurveyYearMapping> role_key => mapping row */
    protected $mappings;

    /**
     * Which role keys belong to each GROUPS entry, precomputed once here
     * instead of inside compute(). ROLES/GROUPS are fixed class constants,
     * so this mapping is identical on every call - compute() previously
     * re-derived it by looping over all 20 ROLES for EACH of the 6 GROUPS
     * (120 array checks) on every single respondent row it scored. This
     * page runs compute() once per respondent for a score-method fiscal
     * year (thousands of rows for FY2569+), so that was thousands of
     * redundant re-derivations of the exact same 6 short lists - one of
     * the two things (along with the query-side work already optimized
     * elsewhere) that made switching to a score-configured year slow on
     * both /awareness and the home dashboard. Built once per class load
     * and shared by every instance/row.
     *
     * @var array<string, string[]>|null group_key => [role_key, ...]
     */
    protected static $groupMembersCache;

    public function __construct($fiscalYear)
    {
        $this->fiscalYear = $fiscalYear;
        $this->mappings = SurveyYearMapping::scoreRoleMappingsFor($fiscalYear);
    }

    protected static function groupMembers(): array
    {
        if (self::$groupMembersCache === null) {
            $map = array_fill_keys(array_keys(self::GROUPS), []);
            foreach (self::ROLES as $roleKey => $meta) {
                if (($meta['group'] ?? null) !== null) {
                    $map[$meta['group']][] = $roleKey;
                }
            }
            self::$groupMembersCache = $map;
        }
        return self::$groupMembersCache;
    }

    /**
     * Whether every one of the 26 fixed roles has a question assigned yet
     * this fiscal year. compute() below still degrades gracefully (missing
     * roles simply score 0) so a partially-configured year doesn't crash -
     * but callers showing a pass/fail badge should treat "not fully
     * configured" as "pending", the same way isAwarePass() already does for
     * the older is_aware_health/is_know_limit criteria.
     */
    public function isFullyConfigured(): bool
    {
        foreach (array_keys(self::ROLES) as $role) {
            if (!isset($this->mappings[$role])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Full score breakdown for one respondent row, or null if this fiscal
     * year has no scoring configuration at all yet (nothing to compute).
     * Returns every sub-total the reference file itself shows, so an admin
     * reviewing one respondent's detail can see exactly how the total was
     * built, plus 'sum_all' and 'result' ('ผ่าน'/'ไม่ผ่าน').
     */
    public function compute(SodiumSurvey $survey, bool $withRawCodes = false): ?array
    {
        if (empty($this->mappings)) {
            return null;
        }

        $data = $survey->survey_data ?? [];

        // Every leaf role's own score (before any group averaging/maxing) -
        // and, only when a caller actually asked for it ($withRawCodes),
        // that role's raw pre-conversion "คะแนนเต็ม" code in the SAME pass
        // over ROLES/$data rather than a second one. AwarenessInterpretation
        // Export is the only caller that needs raw_codes (for its
        // "คะแนนเต็ม" columns) - every other caller (the /awareness
        // dashboard, interpretation stats, etc.) calls compute() far more
        // often per request and never looks at raw codes at all, so this
        // stays opt-in rather than doing the extra resolution work
        // unconditionally on their behalf.
        $roleScores = [];
        $rawCodes = [];
        foreach (self::ROLES as $roleKey => $meta) {
            $roleScores[$roleKey] = $this->rawScoreForRole($roleKey, $data);
            if ($withRawCodes) {
                $code = $this->preConversionCodeForRole($roleKey, $data);
                if ($code !== null) {
                    $rawCodes[$roleKey] = $code;
                }
            }
        }

        // Resolve each group down to a single value, then hand every
        // (standalone-or-grouped) value to the bucket it feeds.
        $bucketTotals = [
            self::BUCKET_SECTION2 => 0.0,
            self::BUCKET_INDIVIDUAL_BELIEF => 0.0,
            self::BUCKET_ENVIRONMENT_FACTOR => 0.0,
            self::BUCKET_ADDON => 0.0,
        ];
        $groupValues = [];
        $groupMembersMap = self::groupMembers();

        foreach (self::GROUPS as $groupKey => $groupMeta) {
            $memberScores = [];
            foreach ($groupMembersMap[$groupKey] as $roleKey) {
                $memberScores[] = $roleScores[$roleKey] ?? 0;
            }
            $value = $groupMeta['aggregate'] === 'max'
                ? (empty($memberScores) ? 0.0 : max($memberScores))
                : (empty($memberScores) ? 0.0 : array_sum($memberScores) / count($memberScores));

            $groupValues[$groupKey] = round($value, 2);
            $bucketTotals[$groupMeta['contributes_to']] += $value;
        }

        foreach (self::ROLES as $roleKey => $meta) {
            if ($meta['group'] === null) {
                $bucketTotals[$meta['contributes_to']] += $roleScores[$roleKey] ?? 0;
            }
        }

        $sumSection2 = round($bucketTotals[self::BUCKET_SECTION2], 2);
        $sumIndividualBelief = round($bucketTotals[self::BUCKET_INDIVIDUAL_BELIEF], 2);
        $sumEnvironmentFactor = round($bucketTotals[self::BUCKET_ENVIRONMENT_FACTOR], 2);
        $sumAllFactor = round($sumIndividualBelief + $sumEnvironmentFactor + $bucketTotals[self::BUCKET_ADDON], 2);
        $sumAll = round($sumSection2 + $sumAllFactor, 2);

        return [
            'role_scores' => $roleScores,
            'raw_codes' => $rawCodes,
            'group_values' => $groupValues,
            'sum_2_1_to_2_5' => $sumSection2,
            'sum_individual_belief' => $sumIndividualBelief,
            'sum_environment_factor' => $sumEnvironmentFactor,
            'sum_all_factor' => $sumAllFactor,
            'sum_all' => $sumAll,
            'is_pass' => $sumAll >= self::PASS_THRESHOLD,
            'result' => $sumAll >= self::PASS_THRESHOLD ? 'ผ่าน' : 'ไม่ผ่าน',
        ];
    }

    /**
     * One leaf role's own score for this respondent - 0 when the role has
     * no question assigned yet, the respondent left it blank, or the
     * recorded answer has no score assigned to it yet (an admin adding a
     * brand new answer wording mid-year before revisiting the settings
     * screen). Never null, so compute()'s sums always stay numeric.
     */
    protected function rawScoreForRole(string $roleKey, array $data): float
    {
        $mapping = $this->mappings[$roleKey] ?? null;
        if (!$mapping) {
            return 0.0;
        }

        $answer = $data[$mapping->question_key] ?? null;
        if ($answer === null || $answer === '') {
            return 0.0;
        }

        if ((self::ROLES[$roleKey]['ui_type'] ?? null) === 'knowledge') {
            return $this->scoreKnowledgeAnswer($mapping->answer_scores, (string) $answer);
        }

        $scores = (array) $mapping->answer_scores;
        return isset($scores[$answer]) ? (float) $scores[$answer] : 0.0;
    }

    /**
     * The role's raw PRE-CONVERSION "คะแนนเต็ม" code for this respondent -
     * the 1-5 (or 0/1) code the criteria file's own reference column
     * assigns to the real answer text recorded, before SurveyYearMapping::
     * answer_scores converts it to the 0-2/0-1 rubric score rawScoreForRole()
     * above returns. Returns null when there's nothing genuine to show: no
     * question assigned to this role yet, the respondent left it blank, or
     * the role has no raw-code table at all (belief_knowledge is free-text
     * and was never assigned one - see
     * SurveyQuestionSettingsController::ROLE_ANSWER_RAW_SCORES). Resolution
     * order mirrors that controller's own computeRawAnswerScores()/
     * settings-scoring screen exactly, so this always agrees with the raw
     * code an admin already sees there: their own explicit
     * RAW_SCORE_STORE_KEY pick for this exact answer text first, then the
     * criteria file's own suggestion table (matched after the same
     * whitespace-normalization computeRawAnswerScores() applies).
     */
    protected function preConversionCodeForRole(string $roleKey, array $data): ?float
    {
        $mapping = $this->mappings[$roleKey] ?? null;
        if (!$mapping) {
            return null;
        }

        $answer = $data[$mapping->question_key] ?? null;
        if ($answer === null || $answer === '') {
            return null;
        }
        $answer = (string) $answer;

        $overrides = (array) ($mapping->answer_scores[SurveyQuestionSettingsController::RAW_SCORE_STORE_KEY] ?? []);
        if (array_key_exists($answer, $overrides)) {
            return (float) $overrides[$answer];
        }

        $scale = SurveyQuestionSettingsController::ROLE_ANSWER_RAW_SCORES[$roleKey] ?? null;
        if (!$scale) {
            return null;
        }
        $normalized = trim(preg_replace('/\s+/u', ' ', $answer));
        return array_key_exists($normalized, $scale) ? (float) $scale[$normalized] : null;
    }


    /**
     * belief_knowledge's free-text scoring: 2 when the answer's leading run
     * of digits exactly equals one of the accepted correct values, else 0.
     * Matches the reference file's real recorded answers exactly (e.g.
     * "2000", "2000-2100", "2000-2500" all score 2 against accepted value
     * "2000"; "1900-2000", "2500", "-" all score 0).
     */
    protected function scoreKnowledgeAnswer($answerScores, string $answer): float
    {
        $accepted = (array) ($answerScores[self::KNOWLEDGE_CORRECT_KEY] ?? []);
        if (empty($accepted)) {
            return 0.0;
        }

        if (!preg_match('/\d+/', $answer, $m)) {
            return 0.0;
        }
        $leadingDigits = $m[0];

        foreach ($accepted as $value) {
            if ($leadingDigits === (string) $value) {
                return 2.0;
            }
        }
        return 0.0;
    }
}
