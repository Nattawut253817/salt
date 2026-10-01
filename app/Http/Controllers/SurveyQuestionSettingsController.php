<?php

namespace App\Http\Controllers;

use App\Models\AwarenessPassSetting;
use App\Models\DemographicFieldMapping;
use App\Models\FiscalYear;
use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use App\Services\AwarenessPassResolver;
use App\Services\AwarenessScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Two admin screens (both linked from "การประเมินความตระหนักรู้") for
 * configuring, per fiscal year:
 *
 *  - "ร้อยละความตระหนักรู้": which question counts as criteria 1
 *    ("ตระหนักปัญหาสุขภาพ") and which counts as criteria 2 ("รู้ขีดจำกัด")
 *    for the "aware/pass" calculation used by the home dashboard map, the
 *    /awareness report, and the badge on the admin upload list. This just
 *    sets SurveyYearMapping::semantic_key to 'is_aware_health' /
 *    'is_know_limit' - every place that reads it already resolves per
 *    fiscal year, so nothing else needs to change when this is edited.
 *
 *  - "ตั้งค่าแดชบอร์ด": which of the fixed breakdown panels on /awareness
 *    each question's answers should be charted in
 *    (SurveyYearMapping::dashboard_panel), or left blank to not show it at
 *    all.
 *
 * A brand new year's form is auto-registered on first upload (see
 * SodiumSurveyImport::syncMappings()) with both of these unset, so nothing
 * shows for it on /awareness (and its "aware/pass" status reads as
 * "pending", same as FY69 always has) until someone visits these screens.
 */
class SurveyQuestionSettingsController extends Controller
{
    /**
     * Same fiscal-year list as the main "การประเมินความตระหนักรู้" page
     * (AwarenessAssessmentController) and its "นำเข้า Excel" modal - real
     * distinct years already in the data, plus any admin-enabled years and
     * minus any admin-hidden years from "จัดการปีงบประมาณ"
     * (FiscalYearController) - so these two settings screens never offer a
     * year the main page itself wouldn't. Picking a year with no real data
     * yet just shows an empty question list below (questionsFor() already
     * handles that), rather than being excluded from the dropdown.
     */
    private function years()
    {
        return FiscalYear::selectableYearsFor('awareness', SodiumSurvey::query()->distinct()->pluck('fiscal_year'));
    }

    private function questionsFor($selectedYear)
    {
        return $selectedYear
            ? SurveyYearMapping::where('fiscal_year', $selectedYear)
                ->where('is_fixed_column', false)
                ->orderBy('sort_order')
                ->get()
            : collect();
    }

    // Every distinct answer actually recorded this fiscal year for each
    // given question, keyed by question id - lets an admin see the real
    // wording used in the uploaded data (e.g. "ทุกวัน"/"บ่อยครั้ง"/"ไม่เคย")
    // instead of guessing blind. Shared by the "ร้อยละความตระหนักรู้" screen
    // (criteria()) and the "ตั้งค่าแดชบอร์ด" screen (dashboard()).
    private function distinctAnswersFor($questions, $selectedYear)
    {
        // Cached per fiscal year for 5 minutes - this used to run one
        // JSON_EXTRACT DISTINCT query PER survey question on every single
        // page load, which is why clicking between the "ตั้งค่าคะแนน
        // ความตระหนักรู้" category cards (each one a fresh GET to
        // scoring()) felt slow: every click re-scanned sodium_surveys once
        // per question again from scratch. $questions is always built the
        // same way (questionsFor($selectedYear)) by every caller of this
        // method, so the result is identical for a given year no matter
        // which of the three screens asks first - safe to key on the year
        // alone. Invalidated explicitly wherever the underlying data can
        // change (see Cache::forget('scoring_distinct_answers:...') in
        // AwarenessAssessmentController::import()/deleteFiltered() and
        // SurveyQuestionSettingsController::resetQuestionMappings()) and
        // also self-expires after 5 minutes as a backstop.
        return Cache::remember("scoring_distinct_answers:{$selectedYear}", now()->addMinutes(5), function () use ($questions, $selectedYear) {
            $distinctAnswers = [];
            foreach ($questions as $q) {
                $expr = "JSON_UNQUOTE(JSON_EXTRACT(survey_data, '$.{$q->question_key}'))";
                $values = SodiumSurvey::where('fiscal_year', $selectedYear)
                    ->whereRaw("{$expr} IS NOT NULL AND {$expr} != ''")
                    ->selectRaw("DISTINCT {$expr} as val")
                    ->orderBy('val')
                    ->limit(30)
                    ->pluck('val')
                    ->toArray();

                // A handful of older imports stored the literal JSON string
                // "null" (or a blank/whitespace-only string) for an unanswered
                // question, instead of a real SQL NULL - the WHERE clause above
                // only catches a true SQL NULL or an exactly-empty string, so
                // it lets that literal "null" leftover straight through as if
                // it were a genuine recorded answer. Filter it out here too, so
                // it never shows up as a bogus selectable option on the
                // "ตั้งค่าคะแนนความตระหนักรู้"/criteria/dashboard screens.
                $distinctAnswers[$q->id] = array_values(array_filter($values, function ($v) {
                    $trimmed = trim((string) $v);
                    return $trimmed !== '' && mb_strtolower($trimmed) !== 'null';
                }));
            }
            return $distinctAnswers;
        });
    }

    // Exact per-role answer-to-score table, taken verbatim (one row per
    // real answer option, per role) from the admin's own criteria
    // spreadsheet ("เกณฑ์ประเมิน.xlsx" - "คำตอบ" / "คะแนนเต็ม" / "คะแนนแปลง"
    // columns). This REPLACES the earlier shared-scale-per-question-family
    // guess (one "agreement"/"frequency" table reused across many roles) -
    // that guess had already turned out wrong for several roles once
    // checked against real data, whereas this file gives the literal
    // recorded text AND its score for every one of the 26 roles directly,
    // so there's nothing left to infer. Both the literal Thai answer text
    // and the file's own "คะแนนเต็ม" digit are kept as keys for each option
    // (a couple of roles' real recorded answers may turn out to be the bare
    // digit rather than the Thai phrase) - matched by exact text after
    // trimming/whitespace-collapsing, so an unexpected wording variant
    // simply falls back to no suggestion rather than guessing wrong. Never
    // applied once an admin has actually saved a score for that specific
    // answer - see computeScoringSuggestions() below.
    //
    // sec2_1-2_3 (เติมน้ำปลา/อาหารสำเร็จรูป/อาหารแปรรูป-หมักดอง): doing it
    // MORE often scores higher here (ทุกวัน = 2, ไม่ทานเลย = 0) - the
    // opposite direction of sec2_4 right below, exactly as the criteria
    // file lists them; kept as-is rather than "corrected" to match, since
    // this file is the authority here, not an assumption about which
    // direction a frequency question "should" go.
    // NOTE: 'นานครั้งๆ' (the criteria file's own spelling) and 'นานๆครั้ง' (the
    // ๆ-mark-order variant actually recorded by real survey data - seen in
    // the admin's own screenshot of a real question's answers) are the same
    // option, just spelled with the ๆ repeater in a different position -
    // both kept as keys, same score, on every sec2_* role below so neither
    // spelling ever falls through to "no suggestion" / "-".
    const ROLE_ANSWER_SCORES = [
        'sec2_1' => ['ทุกวัน' => 2, '1' => 2, 'นานครั้งๆ' => 1.5, 'นานๆครั้ง' => 1.5, '2' => 1.5, 'บางครั้ง' => 1, '3' => 1, 'บ่อยครั้ง' => 0.5, '4' => 0.5, 'ไม่ทานเลย' => 0, '5' => 0],
        'sec2_2' => ['ทุกวัน' => 2, '1' => 2, 'นานครั้งๆ' => 1.5, 'นานๆครั้ง' => 1.5, '2' => 1.5, 'บางครั้ง' => 1, '3' => 1, 'บ่อยครั้ง' => 0.5, '4' => 0.5, 'ไม่ทานเลย' => 0, '5' => 0],
        'sec2_3' => ['ทุกวัน' => 2, '1' => 2, 'นานครั้งๆ' => 1.5, 'นานๆครั้ง' => 1.5, '2' => 1.5, 'บางครั้ง' => 1, '3' => 1, 'บ่อยครั้ง' => 0.5, '4' => 0.5, 'ไม่ทานเลย' => 0, '5' => 0],
        'sec2_4' => ['ทุกวัน' => 0, '1' => 0, 'นานครั้งๆ' => 0.5, 'นานๆครั้ง' => 0.5, '2' => 0.5, 'บางครั้ง' => 1, '3' => 1, 'บ่อยครั้ง' => 1.5, '4' => 1.5, 'ไม่ทานเลย' => 2, '5' => 2],

        'label_seen_1' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_seen_2' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_seen_3' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_used_1' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_used_2' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_used_3' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],

        // เฉยๆ/เห็นด้วย/เห็นด้วยอย่างยิ่ง/ไม่เห็นด้วย/ไม่เห็นด้วยอย่างยิ่ง - the
        // criteria file's own 5 options for every belief item (note this is
        // NOT a monotonic "strongly disagree...strongly agree" ordering -
        // "เฉยๆ" (neutral) is listed before "เห็นด้วย" in the file, and the
        // score column follows the file's row order exactly, not a Likert
        // assumption). Roles below score เฉยๆ=0...ไม่เห็นด้วยอย่างยิ่ง=2.
        'belief_susceptibility' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],
        'belief_severity_1' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],
        'belief_severity_2' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],
        'belief_benefits' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],
        'belief_selfefficacy' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],
        'env_policy_1' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],
        'env_policy_2' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],
        'env_cue' => ['เฉยๆ' => 0, '1' => 0, 'เห็นด้วย' => 0.5, '2' => 0.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 1.5, '4' => 1.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 2, '5' => 2],

        // Same 5 options, opposite score direction (เฉยๆ=2...ไม่เห็นด้วยอย่าง
        // ยิ่ง=0) - the "barriers" items plus one "social" item, exactly as
        // the criteria file lists them.
        'belief_barriers_1' => ['เฉยๆ' => 2, '1' => 2, 'เห็นด้วย' => 1.5, '2' => 1.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 0.5, '4' => 0.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 0, '5' => 0],
        'belief_barriers_2' => ['เฉยๆ' => 2, '1' => 2, 'เห็นด้วย' => 1.5, '2' => 1.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 0.5, '4' => 0.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 0, '5' => 0],
        'belief_barriers_3' => ['เฉยๆ' => 2, '1' => 2, 'เห็นด้วย' => 1.5, '2' => 1.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 0.5, '4' => 0.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 0, '5' => 0],
        'env_social_1' => ['เฉยๆ' => 2, '1' => 2, 'เห็นด้วย' => 1.5, '2' => 1.5, 'เห็นด้วยอย่างยิ่ง' => 1, '3' => 1, 'ไม่เห็นด้วย' => 0.5, '4' => 0.5, 'ไม่เห็นด้วยอย่างยิ่ง' => 0, '5' => 0],

        // Genuine yes/no facts (worth 1 point, not 2 - the criteria file's
        // own "คะแนนเต็ม" for these three is 1/0, not the 5-point scale
        // above), so a role card here may now read "ยังไม่ถึงคะแนนเต็มที่ตั้ง
        // ไว้ในสูตร (2)" until the rubric's own max_score is revisited to
        // match - flagged separately, not changed here.
        'env_social_2' => ['ใช่' => 1, '1' => 1, 'ไม่ใช่' => 0, '0' => 0],
        'env_media' => ['ใช่' => 1, '1' => 1, 'ไม่ใช่' => 0, '0' => 0],
        'env_physical' => ['ใช่' => 1, '1' => 1, 'ไม่ใช่' => 0, '0' => 0],
    ];

    // The criteria file ("เกณฑ์ประเมิน.xlsx") lists 3 columns per answer:
    // "คำตอบ" (answer text), "คะแนนเต็ม" (the raw 1-5 / 0-1 code the form
    // itself recorded for that option, before conversion), and "คะแนนแปลง"
    // (the converted score already captured in ROLE_ANSWER_SCORES above).
    // This constant is that same file's "คะแนนเต็ม" column, verbatim, so the
    // settings-scoring screen can show it purely for reference next to the
    // "คะแนนแปลง" score an admin actually picks - it is NEVER written to
    // answer_scores and never affects compute(). Same per-role key set and
    // same text/digit-key duplication as ROLE_ANSWER_SCORES above (kept
    // separate rather than merged into one nested structure so an edit to
    // one never risks silently mis-indexing the other).
    const ROLE_ANSWER_RAW_SCORES = [
        'sec2_1' => ['ทุกวัน' => 1, '1' => 1, 'นานครั้งๆ' => 2, 'นานๆครั้ง' => 2, '2' => 2, 'บางครั้ง' => 3, '3' => 3, 'บ่อยครั้ง' => 4, '4' => 4, 'ไม่ทานเลย' => 5, '5' => 5],
        'sec2_2' => ['ทุกวัน' => 1, '1' => 1, 'นานครั้งๆ' => 2, 'นานๆครั้ง' => 2, '2' => 2, 'บางครั้ง' => 3, '3' => 3, 'บ่อยครั้ง' => 4, '4' => 4, 'ไม่ทานเลย' => 5, '5' => 5],
        'sec2_3' => ['ทุกวัน' => 1, '1' => 1, 'นานครั้งๆ' => 2, 'นานๆครั้ง' => 2, '2' => 2, 'บางครั้ง' => 3, '3' => 3, 'บ่อยครั้ง' => 4, '4' => 4, 'ไม่ทานเลย' => 5, '5' => 5],
        'sec2_4' => ['ทุกวัน' => 1, '1' => 1, 'นานครั้งๆ' => 2, 'นานๆครั้ง' => 2, '2' => 2, 'บางครั้ง' => 3, '3' => 3, 'บ่อยครั้ง' => 4, '4' => 4, 'ไม่ทานเลย' => 5, '5' => 5],

        'label_seen_1' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_seen_2' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_seen_3' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_used_1' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_used_2' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],
        'label_used_3' => ['เคย' => 1, '1' => 1, 'ไม่เคย' => 0, '0' => 0],

        'belief_susceptibility' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'belief_severity_1' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'belief_severity_2' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'belief_benefits' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'belief_selfefficacy' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'env_policy_1' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'env_policy_2' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'env_cue' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],

        'belief_barriers_1' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'belief_barriers_2' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'belief_barriers_3' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],
        'env_social_1' => ['เฉยๆ' => 1, '1' => 1, 'เห็นด้วย' => 2, '2' => 2, 'เห็นด้วยอย่างยิ่ง' => 3, '3' => 3, 'ไม่เห็นด้วย' => 4, '4' => 4, 'ไม่เห็นด้วยอย่างยิ่ง' => 5, '5' => 5],

        'env_social_2' => ['ใช่' => 1, '1' => 1, 'ไม่ใช่' => 0, '0' => 0],
        'env_media' => ['ใช่' => 1, '1' => 1, 'ไม่ใช่' => 0, '0' => 0],
        'env_physical' => ['ใช่' => 1, '1' => 1, 'ไม่ใช่' => 0, '0' => 0],
    ];

    // Reserved answer_scores key an admin's own manually-picked/-overridden
    // "คะแนนเต็ม" is stored under - same trick as
    // AwarenessScoreCalculator::KNOWLEDGE_CORRECT_KEY (a real recorded
    // answer text can never equal this literal string, so it can live
    // alongside the real per-answer "คะแนนแปลง" keys in the same JSON
    // column without colliding). AwarenessScoreCalculator::rawScoreForRole()
    // reads answer_scores by the real answer text only, so this key is
    // simply never looked at during compute() - purely a UI convenience so
    // an admin's manual "คะแนนเต็ม" pick survives a reload instead of
    // reverting to the auto-suggested value every time.
    const RAW_SCORE_STORE_KEY = '__raw_scores__';

    // The 6 label_seen_*/label_used_* roles have no numbered form item (their
    // hint is prose only, e.g. "ฉลากโภชนาการ (เห็น)"), so they can't use the
    // item-number match computeScoringSuggestions() uses for every other
    // role. Matched instead by keyword: BOTH the specific label/symbol name
    // and the correct verb (เห็น vs ใช้) must appear in a real question's
    // label, so a question naming the same label under the other verb never
    // matches the wrong role.
    const LABEL_ROLE_KEYWORDS = [
        'label_seen_1' => ['ฉลากโภชนาการ', 'เห็น'],
        'label_seen_2' => ['GDA', 'เห็น'],
        'label_seen_3' => ['สัญลักษณ์ทางเลือกสุขภาพ', 'เห็น'],
        'label_used_1' => ['ฉลากโภชนาการ', 'ใช้'],
        'label_used_2' => ['GDA', 'ใช้'],
        'label_used_3' => ['สัญลักษณ์ทางเลือกสุขภาพ', 'ใช้'],
    ];

    // Presentation metadata for the "ร้อยละความตระหนักรู้" card picker - keyed
    // by the semantic_key each card assigns. Order here is display order.
    const CRITERIA_META = [
        'is_aware_health' => [
            'order' => 1,
            'title' => 'ตระหนักปัญหาสุขภาพ',
            'description' => 'คำถามที่ใช้วัดว่าตระหนักหรือไม่ว่าการบริโภคโซเดียมสูงส่งผลเสียต่อสุขภาพ',
            'icon' => 'fa-heart-pulse',
            'color' => '#db2777',
        ],
        'is_know_limit' => [
            'order' => 2,
            'title' => 'รู้ขีดจำกัดการบริโภค',
            'description' => 'คำถามที่ใช้วัดว่ารู้หรือไม่ว่าควรจำกัดปริมาณโซเดียมที่บริโภคต่อวัน',
            'icon' => 'fa-gauge-high',
            'color' => '#7c3aed',
        ],
    ];

    // Presentation metadata for the "คอลัมน์สำหรับการ์ด 'พฤติกรรมการบริโภค
    // โซเดียม' (หน้าแรก)" section at the bottom of "ร้อยละความตระหนักรู้" - one
    // entry per behavior role that MainController::index()'s awareness-metrics
    // block (the home page's 4-row widget) reads. 'row' groups entries under
    // the same widget row. hint_pre / hint_fy69 are the DEFAULT answer value(s) MainController
    // treats as "the good behavior" for that role in that era (empty array
    // = role not used in that era). Same role/default pairing as
    // MainController::index()'s $behaviorWhere() defaults - keep both in
    // sync. Unlike CRITERIA_META's DEFAULT_CRITERIA_PASS_VALUES (shared by
    // both criteria roles), these vary per role, so each one is listed here
    // explicitly. A role can now be assigned to MULTIPLE questions at once,
    // and the same question can also carry an unrelated semantic_key (e.g.
    // is_aware_health) at the same time - see updateBehavior() - so each
    // question's chosen pass-values for a given role live in that
    // question's own behavior_roles[$role], NOT in criteria_pass_values
    // (which stays reserved for semantic_key/CRITERIA_ROLES).
    const BEHAVIOR_META = [
        'add_seasoning_cook' => [
            'row' => 1, 'row_title' => 'ไม่เติมน้ำปลา/เครื่องปรุงรสเพิ่มระหว่างปรุงอาหาร',
            'title' => 'เติมน้ำปลา/เครื่องปรุงรสเพิ่มระหว่างปรุงอาหาร',
            'description' => 'คำถามที่ถามความถี่ในการเติมเครื่องปรุงรส (น้ำปลา/ซีอิ๊ว/เกลือ ฯลฯ) เพิ่มระหว่างปรุงอาหาร',
            'icon' => 'fa-utensils', 'color' => '#16a34a',
            'hint_pre' => ['ไม่เคยเลย'], 'hint_fy69' => ['ไม่ทานเลย'],
        ],
        'freq_instant_food' => [
            'row' => 2, 'row_title' => 'ไม่ทานอาหารสำเร็จรูป/กึ่งสำเร็จรูป',
            'title' => 'ความถี่ในการทานอาหารสำเร็จรูป/กึ่งสำเร็จรูป',
            'description' => 'คำถามที่ถามความถี่ในการทานอาหารสำเร็จรูปหรือกึ่งสำเร็จรูป (เช่น บะหมี่กึ่งสำเร็จรูป อาหารกระป๋อง)',
            'icon' => 'fa-bowl-food', 'color' => '#f59e0b',
            'hint_pre' => ['ไม่เคย'], 'hint_fy69' => ['ไม่ทานเลย'],
        ],
        'freq_pickled_food' => [
            'row' => 3, 'row_title' => 'ไม่ทานอาหารหมักดอง/โซเดียมสูง',
            'title' => 'ความถี่ในการทานอาหารหมักดอง (ก่อนปีงบประมาณ 2569)',
            'description' => 'คำถามที่ถามความถี่ในการทานอาหารหมักดอง (เช่น ปลาร้า ผักดอง ไข่เค็ม)',
            'icon' => 'fa-jar', 'color' => '#ef4444',
            'hint_pre' => ['ไม่เคย'], 'hint_fy69' => [],
        ],
        'freq_processed_food' => [
            'row' => 3, 'row_title' => 'ไม่ทานอาหารหมักดอง/โซเดียมสูง',
            'title' => 'ความถี่ในการทานอาหารแปรรูป/หมักดอง (ปีงบประมาณ 2569 เป็นต้นไป)',
            'description' => 'คำถามที่ถามความถี่ในการทานอาหารแปรรูปหรือหมักดองของแบบฟอร์มปีงบประมาณ 2569',
            'icon' => 'fa-jar', 'color' => '#ef4444',
            'hint_pre' => [], 'hint_fy69' => ['ไม่ทานเลย'],
        ],
        'importance_level' => [
            'row' => 4, 'row_title' => 'ความพยายาม/ทัศนคติในการลดโซเดียม',
            'title' => 'ระดับความสำคัญที่ให้กับการลดโซเดียม (ก่อนปีงบประมาณ 2569)',
            'description' => 'คำถามที่ถามว่าให้ความสำคัญกับการลดโซเดียมมากน้อยเพียงใด',
            'icon' => 'fa-star', 'color' => '#6366f1',
            'hint_pre' => ['ทุกครั้ง'], 'hint_fy69' => [],
        ],
        'behavioral_reduce_dipping' => [
            'row' => 4, 'row_title' => 'ความพยายาม/ทัศนคติในการลดโซเดียม',
            'title' => 'ความพยายามลดการจิ้ม/จุ่มน้ำจิ้ม (ปีงบประมาณ 2569 เป็นต้นไป)',
            'description' => 'คำถามที่ถามว่าเห็นด้วยหรือไม่กับการลดการจิ้ม/จุ่มน้ำจิ้ม',
            'icon' => 'fa-droplet', 'color' => '#6366f1',
            'hint_pre' => [], 'hint_fy69' => ['เห็นด้วยอย่างยิ่ง', 'เห็นด้วย'],
        ],
    ];

    // Which BEHAVIOR_META roles are actually read by MainController::index()
    // for the selected fiscal year - only these are offered, so an admin is
    // never shown a role that would have zero effect on that year's widget.
    const BEHAVIOR_ROLES_PRE_FY69 = ['add_seasoning_cook', 'freq_instant_food', 'freq_pickled_food', 'importance_level'];
    const BEHAVIOR_ROLES_FY69 = ['add_seasoning_cook', 'freq_instant_food', 'freq_processed_food', 'behavioral_reduce_dipping'];

    public function criteria(Request $request)
    {
        $years = $this->years();
        $selectedYear = $request->get('fiscal_year') ?: $years->first();
        $questions = $this->questionsFor($selectedYear);

        // Whether this fiscal year has any REAL uploaded survey rows yet -
        // SurveyYearMapping rows (and so $questions) can exist for a year
        // that was imported once but ended up with zero saved rows (every
        // row skipped/invalid, or later bulk-deleted), which used to still
        // show the full "ตั้งค่าเกณฑ์" configuration UI as if the year were
        // properly set up. The view gates on this instead, so a year with
        // no real data shows a clear "ยังไม่มีข้อมูล" notice and skips the
        // configuration cards entirely.
        $hasData = SodiumSurvey::where('fiscal_year', $selectedYear)->exists();

        // "วิธีตั้งเกณฑ์ผ่าน/ไม่ผ่าน" - which method this fiscal year currently
        // uses to decide "ตระหนักรู้/ผ่านเกณฑ์" everywhere that concept is shown
        // (this page's own criteria cards below, the home dashboard map, the
        // /awareness report, and the admin upload list badge) - see
        // AwarenessPassResolver/AwarenessPassSetting and updatePassMethod().
        $passMethods = AwarenessPassSetting::METHODS;
        $currentPassMethod = $selectedYear ? AwarenessPassSetting::methodFor($selectedYear) : AwarenessPassSetting::METHOD_QUESTIONS;
        $scoreConfigured = $selectedYear ? (new AwarenessScoreCalculator($selectedYear))->isFullyConfigured() : false;

        $criteriaMeta = self::CRITERIA_META;
        $currentByRole = [];
        $currentPassValues = [];
        foreach (SurveyYearMapping::CRITERIA_ROLES as $role) {
            // A role can now be backed by more than one question at once
            // (checkbox list instead of a single radio), same as the
            // independent behavior_roles mechanism below - ANY one of them
            // matching counts as that role being satisfied.
            $current = $questions->where('semantic_key', $role)->values();
            $currentByRole[$role] = $current;

            $currentPassValues[$role] = [];
            foreach ($current as $q) {
                $currentPassValues[$role][$q->id] = !empty($q->criteria_pass_values)
                    ? $q->criteria_pass_values
                    : SurveyYearMapping::DEFAULT_CRITERIA_PASS_VALUES;
            }
        }

        // Every distinct answer actually recorded for each candidate
        // question this year, so admin can pick which of THOSE literal
        // values counts as "ผ่าน" instead of assuming every year phrases
        // its yes/no answers the same way ("ใช่"/"เคย").
        $distinctAnswers = $this->distinctAnswersFor($questions, $selectedYear);

        // "คอลัมน์สำหรับการ์ด 'พฤติกรรมการบริโภคโซเดียม' (หน้าแรก)" - only the
        // roles relevant to this fiscal year's form (FY69 asks these
        // differently than earlier years) are offered. Reads the
        // independent behavior_roles column (not semantic_key), so a
        // question already used for is_aware_health / is_know_limit (or
        // for another behavior role) shows up here too, and a role can
        // have more than one question tagged for it at once.
        $isFy69 = ((string) $selectedYear) === '2569';
        $behaviorRoleKeys = $isFy69 ? self::BEHAVIOR_ROLES_FY69 : self::BEHAVIOR_ROLES_PRE_FY69;
        $behaviorMeta = array_intersect_key(self::BEHAVIOR_META, array_flip($behaviorRoleKeys));
        $currentByBehaviorRole = [];
        $currentBehaviorPassValues = [];
        foreach ($behaviorRoleKeys as $role) {
            $default = $isFy69
                ? (self::BEHAVIOR_META[$role]['hint_fy69'] ?? [])
                : (self::BEHAVIOR_META[$role]['hint_pre'] ?? []);

            $tagged = $questions->filter(
                fn ($q) => array_key_exists($role, (array) $q->behavior_roles)
            )->values();
            $currentByBehaviorRole[$role] = $tagged;

            $currentBehaviorPassValues[$role] = [];
            foreach ($tagged as $q) {
                $rolePassValues = ((array) $q->behavior_roles)[$role] ?? null;
                $currentBehaviorPassValues[$role][$q->id] = !empty($rolePassValues) ? $rolePassValues : $default;
            }
        }

        return view('admin.awareness.settings-criteria', compact(
            'years', 'selectedYear', 'questions', 'criteriaMeta', 'currentByRole', 'currentPassValues', 'distinctAnswers',
            'isFy69', 'behaviorRoleKeys', 'behaviorMeta', 'currentByBehaviorRole', 'currentBehaviorPassValues', 'hasData',
            'passMethods', 'currentPassMethod', 'scoreConfigured'
        ));
    }

    // Saves which method this fiscal year uses to decide "ตระหนักรู้/ผ่าน
    // เกณฑ์" - AwarenessPassSetting::METHOD_QUESTIONS (the "เกณฑ์ข้อ 1/ข้อ 2"
    // cards below) or METHOD_SCORE (the scoring rubric from "ตั้งค่าคะแนน
    // ความตระหนักรู้"). Every consumer of this concept (AwarenessPassResolver)
    // picks this up immediately - no separate "apply"/rebuild step.
    public function updatePassMethod(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required',
            'method' => 'required|in:' . implode(',', array_keys(AwarenessPassSetting::METHODS)),
        ]);

        $fiscalYear = $request->input('fiscal_year');
        $method = $request->input('method');

        AwarenessPassSetting::updateOrCreate(['fiscal_year' => $fiscalYear], ['method' => $method]);

        // The home dashboard caches its computed cards for 10 minutes
        // under a key that does NOT include any of this settings data
        // (see MainController::index()'s $homeDashboardCacheKey) - so
        // without an explicit flush here, a just-saved change like this
        // one would silently keep showing the OLD numbers on the home
        // page for up to 10 more minutes. Flushing the whole (file)
        // cache store is the simplest correct fix since this driver
        // doesn't support scoped/tagged eviction.
        \Cache::flush();

        $title = AwarenessPassSetting::METHODS[$method]['title'] ?? '';
        return redirect()->route('admin.awareness.settings.criteria', ['fiscal_year' => $fiscalYear])
            ->with('success', 'เปลี่ยนวิธีตั้งเกณฑ์เป็น "' . $title . '" เรียบร้อยแล้ว');
    }

    // Saves one home-widget row's question(s) AND which of each question's
    // own answer values count as "ทำพฤติกรรมที่ดี" (BEHAVIOR_META role) at a
    // time - one modal = one role = one submit, same as updateCriteria(),
    // but a role can now be checked onto MULTIPLE questions at once
    // (question_ids[] instead of a single question_id), and this never
    // touches semantic_key at all - a question already used for
    // is_aware_health / is_know_limit (or for another behavior role) can
    // be checked here too without being "moved away" from that other use,
    // since each role's membership + pass-values live in that question's
    // own behavior_roles[$role], independent of every other role and of
    // semantic_key/criteria_pass_values. Leaving every answer unchecked
    // for a checked question falls back to that role's own built-in
    // default (BEHAVIOR_META's hint_pre/hint_fy69) rather than rejecting
    // the submit - unlike updateCriteria(), a behavior role always has a
    // sensible default, so it's fine for an admin to just check the
    // question and leave the passing answer as-is.
    public function updateBehavior(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required',
            'role' => 'required|in:' . implode(',', array_keys(self::BEHAVIOR_META)),
            'question_ids' => 'array',
            'question_ids.*' => 'integer',
            'pass_values' => 'nullable|array',
        ]);

        $fiscalYear = $request->input('fiscal_year');
        $role = $request->input('role');
        $checkedIds = array_map('intval', $request->input('question_ids', []));

        $mappings = SurveyYearMapping::where('fiscal_year', $fiscalYear)->get();

        foreach ($mappings as $mapping) {
            $roles = (array) $mapping->behavior_roles;
            $isChecked = in_array($mapping->id, $checkedIds, true);
            $hasRole = array_key_exists($role, $roles);

            if (!$isChecked && !$hasRole) {
                continue;
            }

            if ($isChecked) {
                $passValues = array_values(array_filter(
                    (array) $request->input("pass_values.{$mapping->id}", []),
                    fn ($v) => $v !== null && $v !== ''
                ));
                // Empty selection = keep using this role's built-in default
                // rather than an "all answers fail" state, which is what an
                // empty non-null array would mean to $behaviorWhere().
                $roles[$role] = $passValues ?: null;
            } else {
                unset($roles[$role]);
            }

            $mapping->behavior_roles = $roles ?: null;
            $mapping->save();
        }

        // See the matching comment in updatePassMethod() above - this
        // card's numbers are also baked into the 10-minute home
        // dashboard cache under a key that never changes when only
        // behavior_roles changes, so the fix has to flush explicitly.
        \Cache::flush();

        $title = self::BEHAVIOR_META[$role]['title'] ?? '';
        return redirect()->route('admin.awareness.settings.criteria', ['fiscal_year' => $fiscalYear])
            ->with('success', 'บันทึกคอลัมน์ของ "' . $title . '" เรียบร้อยแล้ว');
    }

    // Saves one criteria role's question(s) (and which of each question's
    // own answer values count as "ผ่าน") at a time - one modal = one role =
    // one submit, the same pattern as updateBehavior()/updatePanelMembership().
    // A role can now be checked onto MULTIPLE questions at once
    // (question_ids[] instead of a single question_id) - ANY one of them
    // matching an answer counts as that role being satisfied for that row
    // (see SurveyYearMapping::criteriaMappingsFor()). Unlike behavior roles,
    // criteria has no sensible built-in default answer, so every checked
    // question still requires at least one pass value chosen; this is
    // validated for every checked question BEFORE anything is saved, so a
    // rejected submit never leaves the role partially cleared. A question
    // can only carry ONE semantic_key at a time, so checking it here always
    // "moves" it away from whatever OTHER criteria role (if any) it
    // previously belonged to; any unrelated semantic_key (e.g.
    // 'add_seasoning_cook', used by the home dashboard's metric cards) is
    // left untouched - this screen doesn't manage those.
    public function updateCriteria(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required',
            'role' => 'required|in:is_aware_health,is_know_limit',
            'question_ids' => 'array',
            'question_ids.*' => 'integer',
            'pass_values' => 'nullable|array',
        ]);

        $fiscalYear = $request->input('fiscal_year');
        $role = $request->input('role');
        $checkedIds = array_map('intval', $request->input('question_ids', []));

        $passValuesByQuestion = [];
        foreach ($checkedIds as $questionId) {
            $passValues = array_values(array_filter(
                (array) $request->input("pass_values.{$questionId}", []),
                fn ($v) => $v !== null && $v !== ''
            ));

            if (empty($passValues)) {
                return redirect()->back()->withErrors([
                    'pass_values' => 'กรุณาเลือกอย่างน้อย 1 คำตอบที่นับเป็น "ผ่าน" สำหรับทุกคำถามที่เลือกไว้',
                ])->withInput();
            }

            $passValuesByQuestion[$questionId] = $passValues;
        }

        SurveyYearMapping::where('fiscal_year', $fiscalYear)
            ->where('semantic_key', $role)
            ->whereNotIn('id', $checkedIds)
            ->update(['semantic_key' => null, 'criteria_pass_values' => null]);

        foreach ($checkedIds as $questionId) {
            $mapping = SurveyYearMapping::where('fiscal_year', $fiscalYear)->find($questionId);
            if ($mapping) {
                $mapping->semantic_key = $role;
                $mapping->criteria_pass_values = $passValuesByQuestion[$questionId];
                $mapping->save();
            }
        }

        // Same reason as updatePassMethod()/updateBehavior() above.
        \Cache::flush();

        $title = self::CRITERIA_META[$role]['title'] ?? '';
        return redirect()->route('admin.awareness.settings.criteria', ['fiscal_year' => $fiscalYear])
            ->with('success', 'บันทึกเกณฑ์ "' . $title . '" เรียบร้อยแล้ว');
    }

    // Card picker: one card per fixed panel (icon, color, description, and
    // how many of this year's questions are currently in it). Clicking a
    // card opens that panel's own checklist modal - see updatePanelMembership().
    //
    // Also shows a second row of cards for the 3 fixed demographic columns
    // whose Excel position can vary by year (gender/age/education) - see
    // DemographicFieldMapping - letting an admin pick, per fiscal year,
    // which of that year's actual header columns really holds each one.
    public function dashboard(Request $request)
    {
        $years = $this->years();
        $selectedYear = $request->get('fiscal_year') ?: $years->first();
        $questions = $this->questionsFor($selectedYear);

        // See criteria()'s $hasData for why this is checked against real
        // SodiumSurvey rows rather than just $questions/$fixedColumns
        // (mapping rows can exist for a year with zero real rows left).
        $hasData = SodiumSurvey::where('fiscal_year', $selectedYear)->exists();

        $panelOrder = SurveyYearMapping::DASHBOARD_PANEL_DISPLAY_ORDER;
        $panelTitles = SurveyYearMapping::DASHBOARD_PANEL_TITLES;
        $panelMeta = SurveyYearMapping::DASHBOARD_PANEL_META;
        $countsByPanel = $questions->countBy(fn ($q) => (int) $q->dashboard_panel);

        // The actual question labels selected into each panel (not just the
        // count), so the settings card can show the admin which questions
        // are already in there instead of only a bare number.
        $questionsByPanel = $questions->groupBy(fn ($q) => (int) $q->dashboard_panel);

        // Real answers actually recorded this year for each question, so
        // the "คำตอบ ... ของข้อนี้คือ" dropdown can show the admin real
        // example wording next to each choice instead of making them guess
        // blind (same data the "ร้อยละความตระหนักรู้" screen already uses).
        $distinctAnswers = $this->distinctAnswersFor($questions, $selectedYear);

        $fixedColumns = SurveyYearMapping::fixedColumnsFor($selectedYear);
        $demographicFields = DemographicFieldMapping::FIELDS;
        $demographicColumnIndex = [];
        foreach ($demographicFields as $fieldKey => $meta) {
            $demographicColumnIndex[$fieldKey] = DemographicFieldMapping::columnIndexFor($selectedYear, $fieldKey);
        }

        return view('admin.awareness.settings-dashboard', compact(
            'years', 'selectedYear', 'questions', 'panelOrder', 'panelTitles', 'panelMeta', 'countsByPanel',
            'questionsByPanel', 'distinctAnswers', 'fixedColumns', 'demographicFields', 'demographicColumnIndex', 'hasData'
        ));
    }

    // Saves which raw column holds one demographic field (gender / age_range
    // / education) for one fiscal year - one modal = one field = one submit,
    // same pattern as updatePanelMembership(). Only affects FUTURE imports
    // (or a re-upload of an already-imported file) - it doesn't retroactively
    // fix rows already in the database from a prior, wrongly-positioned
    // upload; re-upload that year's file (duplicate mode "แทนที่ข้อมูลเดิม")
    // afterwards to correct them.
    public function updateDemographicMapping(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required',
            'field_key' => 'required|in:' . implode(',', array_keys(DemographicFieldMapping::FIELDS)),
            'column_index' => 'required|integer|min:0',
        ]);

        $fiscalYear = $request->input('fiscal_year');
        $fieldKey = $request->input('field_key');
        $columnIndex = (int) $request->input('column_index');

        DemographicFieldMapping::updateOrCreate(
            ['fiscal_year' => $fiscalYear, 'field_key' => $fieldKey],
            ['column_index' => $columnIndex]
        );

        $title = DemographicFieldMapping::FIELDS[$fieldKey]['title'] ?? '';
        return redirect()->route('admin.awareness.settings.dashboard', ['fiscal_year' => $fiscalYear])
            ->with('success', 'บันทึกคอลัมน์ของ "' . $title . '" เรียบร้อยแล้ว - มีผลกับการอัปโหลดไฟล์ครั้งถัดไปของปีงบประมาณนี้');
    }

    // "รีเซ็ตรูปแบบคำถาม" - the escape hatch SodiumSurveyImport::
    // assertHeaderMatches() points admins to when a fiscal year's form has
    // genuinely been restructured (so the new file's header no longer
    // resembles the old one closely enough to pass the mismatch guard, even
    // though it IS the right year - just a redesigned form). Deletes every
    // survey_year_mappings row recorded for this fiscal year, which:
    //  - clears its question_label history, so assertHeaderMatches() sees
    //    an empty $existingLabels next time and treats the next upload as a
    //    brand-new year (any header accepted) - exactly like a fiscal year
    //    that has never been uploaded before;
    //  - along with it, clears every OTHER per-question setting that lives
    //    on this same table for this year (criteria_pass_values,
    //    behavior_roles, dashboard_panel, chart_polarity(_values),
    //    semantic_key, answer_scores) - which is unavoidable and correct:
    //    those settings are tied to the OLD question wording/order and
    //    genuinely don't carry over to a restructured form. The admin will
    //    need to revisit "ร้อยละความตระหนักรู้" / "ตั้งค่าแดชบอร์ด" / "ตั้งค่า
    //    คะแนนความตระหนักรู้" afterwards to reconfigure them against the new
    //    questions - the confirmation dialog on the button says so.
    // Deliberately does NOT touch sodium_surveys (the actual respondent
    // rows already imported) - this only resets the QUESTION FORMAT
    // metadata, never real survey data. A separate, explicit action
    // ("ลบข้อมูลที่กรอง" on the main import page) is what removes rows.
    public function resetQuestionMappings(Request $request)
    {
        $request->validate(['fiscal_year' => 'required']);
        $fiscalYear = $request->input('fiscal_year');

        $deleted = SurveyYearMapping::where('fiscal_year', $fiscalYear)->delete();

        Cache::forget("scoring_distinct_answers:{$fiscalYear}");

        return redirect()->route('admin.awareness.settings.dashboard', ['fiscal_year' => $fiscalYear])
            ->with('success', 'รีเซ็ตรูปแบบคำถามของปีงบประมาณ ' . $fiscalYear . ' เรียบร้อยแล้ว (ล้างการตั้งค่า ' . $deleted . ' รายการ) '
                . 'ตอนนี้สามารถอัปโหลดไฟล์รูปแบบใหม่ของปีนี้ได้แล้ว - แต่ต้องตั้งค่า "ร้อยละความตระหนักรู้", "ตั้งค่าแดชบอร์ด" '
                . 'และ "ตั้งค่าคะแนนความตระหนักรู้" ของปีนี้ใหม่ทั้งหมด เนื่องจากค่าที่ตั้งไว้เดิมผูกกับคำถามชุดเก่า');
    }


    // Saves one panel's question membership at a time (one modal = one
    // panel = one submit), rather than one big table for all panels at
    // once - checking a question here moves it into this panel even if it
    // was already in another one; unchecking a question that's currently in
    // this panel clears it. Any question not touched here (not checked, and
    // not currently in this panel) is left exactly as it was.
    //
    // Also saves each question's chart_polarity (the "ทิศทาง" dropdown next
    // to it) - which side of the /awareness diverging bar its most extreme
    // answer falls on - AND chart_polarity_values (the checkbox list of
    // that question's own real answers the admin has picked as "the most
    // extreme/highest-frequency" one(s), overriding the chart's built-in
    // keyword guess for just those literal values). Both are a property of
    // the QUESTION, not the panel, so they're saved regardless of whether
    // that question is checked into THIS panel; every panel's modal lists
    // the same controls for the same question and they all write to the
    // same columns, so setting them from any one of them is enough. Left
    // blank ("อัตโนมัติ" / no checkbox ticked) clears back to null, which
    // falls back to the chart's own keyword guess.
    public function updatePanelMembership(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required',
            'panel' => 'required|integer|in:' . implode(',', array_keys(SurveyYearMapping::DASHBOARD_PANEL_TITLES)),
            'question_ids' => 'array',
            'question_ids.*' => 'integer',
            'polarity' => 'nullable|array',
            'polarity.*' => 'nullable|in:positive,negative',
            'polarity_values' => 'nullable|array',
        ]);

        $fiscalYear = $request->input('fiscal_year');
        $panel = (int) $request->input('panel');
        $checkedIds = array_map('intval', $request->input('question_ids', []));
        $polarityInput = $request->input('polarity', []);
        $polarityValuesInput = $request->input('polarity_values', []);

        $mappings = SurveyYearMapping::where('fiscal_year', $fiscalYear)->get();

        foreach ($mappings as $mapping) {
            $isChecked = in_array($mapping->id, $checkedIds, true);
            if ($isChecked && (int) $mapping->dashboard_panel !== $panel) {
                $mapping->dashboard_panel = $panel;
            } elseif (!$isChecked && (int) $mapping->dashboard_panel === $panel) {
                $mapping->dashboard_panel = null;
            }

            if (array_key_exists($mapping->id, $polarityValuesInput)) {
                $selectedValues = array_values(array_filter(
                    (array) $polarityValuesInput[$mapping->id],
                    fn ($v) => $v !== null && $v !== ''
                ));
                $newValues = $selectedValues ?: null;
                if ($mapping->chart_polarity_values !== $newValues) {
                    $mapping->chart_polarity_values = $newValues;
                }
            }

            if (array_key_exists($mapping->id, $polarityInput)) {
                $polarity = $polarityInput[$mapping->id] ?: null;
                if ($mapping->chart_polarity !== $polarity) {
                    $mapping->chart_polarity = $polarity;
                }
            }

            if ($mapping->isDirty()) {
                $mapping->save();
            }
        }

        $title = SurveyYearMapping::DASHBOARD_PANEL_TITLES[$panel] ?? '';
        return redirect()->route('admin.awareness.settings.dashboard', ['fiscal_year' => $fiscalYear])
            ->with('success', 'บันทึกคำถามในกรอบ "' . $title . '" เรียบร้อยแล้ว');
    }

    // "ตั้งค่าคะแนนความตระหนักรู้" - card picker: one card per fixed rubric
    // role (AwarenessScoreCalculator::ROLES), grouped into the 4 sections the
    // reference scoring file itself uses. Clicking a card opens a modal to
    // (a) pick which of this year's real questions fills that role, and (b)
    // - except for the one free-text 'knowledge' role - assign a numeric
    // score to each of that question's own real recorded answers. See
    // updateScoreRole() for the save side and AwarenessScoreCalculator for
    // how these are combined into a final Sum all / ผ่าน-ไม่ผ่าน result.
    public function scoring(Request $request)
    {
        $years = $this->years();
        $selectedYear = $request->get('fiscal_year') ?: $years->first();
        $questions = $this->questionsFor($selectedYear);

        // See criteria()'s $hasData for why this is checked against real
        // SodiumSurvey rows rather than just $questions.
        $hasData = SodiumSurvey::where('fiscal_year', $selectedYear)->exists();

        // This whole screen only matters when the fiscal year's "วิธีตั้ง
        // เกณฑ์ผ่าน/ไม่ผ่าน" (set on "ตั้งค่าเกณฑ์ความตระหนักรู้") is METHOD_SCORE
        // ("วิธีที่ 2: คิดจากคะแนนที่ตั้งค่าไว้") - under METHOD_QUESTIONS
        // ("วิธีที่ 1: เลือกคำถามเฉพาะ") ผ่าน/ไม่ผ่าน is decided by the two
        // "เกณฑ์ข้อ 1/ข้อ 2" answers directly (AwarenessPassResolver), and
        // this page's whole rubric (role → question → per-answer score →
        // ≥19.2/32) is simply never consulted, so configuring it here would
        // only mislead an admin into thinking it's in effect. The view
        // shows a notice instead of the configuration UI whenever this is
        // METHOD_QUESTIONS, same pattern as the !$hasData notice below.
        $currentPassMethod = $selectedYear ? AwarenessPassSetting::methodFor($selectedYear) : AwarenessPassSetting::METHOD_QUESTIONS;

        $roles = AwarenessScoreCalculator::ROLES;
        $roleSections = [
            'behavior' => 'ส่วนที่ 2: พฤติกรรมการบริโภคโซเดียม (คะแนนเต็ม 8)',
            'label' => 'การรับรู้และใช้ฉลากโภชนาการ (คะแนนเต็ม 2 - รวมกับด้านบนเป็นคะแนนเต็ม 10)',
            'belief' => 'ส่วนที่ 3: การรับรู้ส่วนบุคคล (Individual belief, คะแนนเต็ม 10)',
            'environment' => 'ส่วนที่ 3: ปัจจัยสิ่งแวดล้อม (Environmental factor, คะแนนเต็ม 8)',
        ];

        // Category filter (in addition to the fiscal-year one above) - when
        // set, the view swaps the "click each role card to open its own
        // modal" overview for one inline form covering every role in just
        // this section, so an admin can review + save a whole category
        // (e.g. all 8 "Environmental factor" roles) in a single submit
        // instead of one modal at a time. Falls back to no filter (the
        // original all-sections overview) for an unknown/blank value rather
        // than erroring, since this is just a display filter.
        $selectedSection = $request->get('section') ?: null;
        if ($selectedSection !== null && !array_key_exists($selectedSection, $roleSections)) {
            $selectedSection = null;
        }

        $mappings = SurveyYearMapping::scoreRoleMappingsFor($selectedYear);

        $currentMapping = [];
        $currentAnswerScores = [];
        $currentCorrectValues = [];
        $currentRawScores = [];
        foreach (array_keys($roles) as $role) {
            $mapping = $mappings[$role] ?? null;
            $currentMapping[$role] = $mapping;
            $currentAnswerScores[$role] = $mapping && $mapping->answer_scores
                ? array_diff_key((array) $mapping->answer_scores, [
                    AwarenessScoreCalculator::KNOWLEDGE_CORRECT_KEY => true,
                    self::RAW_SCORE_STORE_KEY => true,
                ])
                : [];
            $currentCorrectValues[$role] = $mapping && $mapping->answer_scores
                ? (array) ($mapping->answer_scores[AwarenessScoreCalculator::KNOWLEDGE_CORRECT_KEY] ?? [])
                : [];
            // The admin's own saved "คะแนนเต็ม" picks, if any were ever made -
            // see RAW_SCORE_STORE_KEY above. Falls back to the criteria
            // file's own suggestion (computeRawAnswerScores() below) for any
            // answer not explicitly picked yet.
            $currentRawScores[$role] = $mapping && $mapping->answer_scores
                ? (array) ($mapping->answer_scores[self::RAW_SCORE_STORE_KEY] ?? [])
                : [];
        }

        $distinctAnswers = $this->distinctAnswersFor($questions, $selectedYear);

        $configuredCount = count(array_filter($currentMapping));
        $totalRoles = count($roles);

        [$suggestedQuestion, $suggestedAnswerScores] = $this->computeScoringSuggestions(
            $roles, $questions, $currentMapping, $currentAnswerScores, $distinctAnswers
        );

        $rawAnswerScores = $this->computeRawAnswerScores($roles, $questions, $distinctAnswers);

        // Only the "filter by หมวดหมู่" inline editor needs a narrowed
        // per-role candidate list (see candidateQuestionsForRole()) - the
        // overview/modal screen keeps browsing the full $questions list.
        $categoryQuestions = [];
        if ($selectedSection) {
            foreach ($roles as $role => $meta) {
                if ($meta['section'] !== $selectedSection) {
                    continue;
                }
                $currentQuestionId = $currentMapping[$role]?->id ?? ($suggestedQuestion[$role] ?? null);
                $categoryQuestions[$role] = $this->candidateQuestionsForRole($role, $meta, $questions, $currentQuestionId);
            }
        }

        return view('admin.awareness.settings-scoring', compact(
            'years', 'selectedYear', 'questions', 'roles', 'roleSections', 'selectedSection', 'currentMapping',
            'currentAnswerScores', 'currentCorrectValues', 'currentRawScores', 'distinctAnswers', 'hasData',
            'configuredCount', 'totalRoles', 'suggestedQuestion', 'suggestedAnswerScores', 'rawAnswerScores',
            'categoryQuestions', 'currentPassMethod'
        ));
    }

    // Purely-informational "คะแนนเต็ม" (raw code) lookup shown next to each
    // answer's "คะแนนแปลง" picker on the settings-scoring screen, straight
    // from ROLE_ANSWER_RAW_SCORES (the criteria file's own column) - unlike
    // computeScoringSuggestions() this never writes a score and isn't
    // limited to the role's currently-mapped/suggested question, since every
    // question in $questions gets its own (hidden-until-selected) box in the
    // view and each should show the raw code for its own real answers.
    private function computeRawAnswerScores($roles, $questions, $distinctAnswers)
    {
        $rawAnswerScores = [];
        foreach ($roles as $role => $meta) {
            $scale = self::ROLE_ANSWER_RAW_SCORES[$role] ?? null;
            if (!$scale) {
                continue;
            }
            foreach ($questions as $q) {
                foreach ($distinctAnswers[$q->id] ?? [] as $val) {
                    if (array_key_exists($val, $rawAnswerScores[$role] ?? [])) {
                        continue;
                    }
                    $normalized = trim(preg_replace('/\s+/u', ' ', (string) $val));
                    if (array_key_exists($normalized, $scale)) {
                        $rawAnswerScores[$role][$val] = $scale[$normalized];
                    }
                }
            }
        }
        return $rawAnswerScores;
    }

    // Narrows the "เลือกคำถามที่จะใช้สำหรับข้อนี้" candidate list for ONE role
    // down to just the real questions that could plausibly BE that item -
    // used only by the "filter by หมวดหมู่" inline editor (scoring()'s
    // $categoryQuestions), so admin sees just this item's own candidate(s)
    // instead of every real column in the whole fiscal year (demographics,
    // every other section's items, etc.) while reviewing one category.
    // The overview/modal screen still uses the full, unfiltered $questions
    // list - a deliberate difference, since that view is for a single role
    // in isolation and an admin there may genuinely need to browse the full
    // form to find an unusually-worded column.
    //
    // Same two matching passes as computeScoringSuggestions() above (item-
    // number prefix from the role's own "hint", or LABEL_ROLE_KEYWORDS for
    // the 6 label_seen/label_used roles that have no item number) - but
    // returns every match, not just the first, and ALWAYS keeps the role's
    // currently-saved question in the list even if it doesn't match (so a
    // real column whose wording changed since it was mapped never
    // disappears out from under an already-configured role). Falls back to
    // the full list only if a role has neither a saved mapping nor any
    // pattern/keyword match at all, so a role is never left with nothing
    // selectable.
    private function candidateQuestionsForRole($role, $meta, $questions, $currentQuestionId)
    {
        $candidates = [];
        $seenIds = [];

        if (preg_match('/^\s*(\d+\.\d+)\b/u', $meta['hint'], $m)) {
            $code = $m[1];
            foreach ($questions as $q) {
                if (preg_match('/^\s*' . preg_quote($code, '/') . '\b/u', $q->question_label) && !isset($seenIds[$q->id])) {
                    $candidates[] = $q;
                    $seenIds[$q->id] = true;
                }
            }
        } elseif (isset(self::LABEL_ROLE_KEYWORDS[$role])) {
            foreach ($questions as $q) {
                if (isset($seenIds[$q->id])) {
                    continue;
                }
                $matchesAll = true;
                foreach (self::LABEL_ROLE_KEYWORDS[$role] as $keyword) {
                    if (mb_stripos($q->question_label, $keyword) === false) {
                        $matchesAll = false;
                        break;
                    }
                }
                if ($matchesAll) {
                    $candidates[] = $q;
                    $seenIds[$q->id] = true;
                }
            }
        }

        if ($currentQuestionId && !isset($seenIds[$currentQuestionId])) {
            foreach ($questions as $q) {
                if ($q->id == $currentQuestionId) {
                    $candidates[] = $q;
                    $seenIds[$q->id] = true;
                    break;
                }
            }
        }

        if (empty($candidates)) {
            return $questions;
        }

        return $candidates;
    }

    // Used by scoring() to render each unconfigured role's best guess as an
    // on-screen "แนะนำ" suggestion (pre-checked radio / pre-selected score) -
    // never saved on its own; the admin still has to open that role's modal
    // and click "บันทึก" to actually persist it.
    //
    // Part 1: best-guess QUESTION for a role with no saved mapping yet -
    // match the question whose label starts with the same form item number
    // as the role's own "hint" (e.g. hint "2.1 ท่านเติมน้ำปลา..." -> any real
    // question labeled "2.1 ..."). Matches only the official form's stable
    // item numbering, never the prose wording itself (which can vary). Then
    // a second pass covers the 6 label_seen/label_used roles, whose hint has
    // no item number, by keyword match instead (see LABEL_ROLE_KEYWORDS) -
    // both passes skip any question a role's saved mapping OR an earlier
    // suggestion in this same call has already claimed, so two roles never
    // end up suggested onto the same question.
    //
    // Part 2: best-guess SCORE for each of that question's own real
    // recorded answers, using the official answer-scale tables above - an
    // answer that already has a saved score is left untouched, and one that
    // doesn't exactly match a known option text (after trimming/whitespace-
    // collapsing) is simply left with no suggestion.
    private function computeScoringSuggestions($roles, $questions, $currentMapping, $currentAnswerScores, $distinctAnswers)
    {
        $suggestedQuestion = [];
        $claimedIds = [];
        foreach ($currentMapping as $mapping) {
            if ($mapping) {
                $claimedIds[$mapping->id] = true;
            }
        }

        foreach ($roles as $role => $meta) {
            if ($currentMapping[$role]) {
                continue;
            }
            if (!preg_match('/^\s*(\d+\.\d+)\b/u', $meta['hint'], $m)) {
                continue;
            }
            $code = $m[1];
            foreach ($questions as $q) {
                if (isset($claimedIds[$q->id])) {
                    continue;
                }
                if (preg_match('/^\s*' . preg_quote($code, '/') . '\b/u', $q->question_label)) {
                    $suggestedQuestion[$role] = $q->id;
                    $claimedIds[$q->id] = true;
                    break;
                }
            }
        }

        foreach (self::LABEL_ROLE_KEYWORDS as $role => $keywords) {
            if ($currentMapping[$role] || isset($suggestedQuestion[$role])) {
                continue;
            }
            foreach ($questions as $q) {
                if (isset($claimedIds[$q->id])) {
                    continue;
                }
                $label = $q->question_label;
                $matchesAll = true;
                foreach ($keywords as $keyword) {
                    if (mb_stripos($label, $keyword) === false) {
                        $matchesAll = false;
                        break;
                    }
                }
                if ($matchesAll) {
                    $suggestedQuestion[$role] = $q->id;
                    $claimedIds[$q->id] = true;
                    break;
                }
            }
        }

        $suggestedAnswerScores = [];
        foreach ($roles as $role => $meta) {
            $scale = self::ROLE_ANSWER_SCORES[$role] ?? null;
            if (!$scale) {
                continue;
            }
            $questionId = $currentMapping[$role]?->id ?? ($suggestedQuestion[$role] ?? null);
            if (!$questionId) {
                continue;
            }
            $alreadyScored = $currentAnswerScores[$role] ?? [];
            foreach ($distinctAnswers[$questionId] ?? [] as $val) {
                if (array_key_exists($val, $alreadyScored)) {
                    continue;
                }
                $normalized = trim(preg_replace('/\s+/u', ' ', (string) $val));
                if (array_key_exists($normalized, $scale)) {
                    $suggestedAnswerScores[$role][$val] = $scale[$normalized];
                }
            }
        }

        return [$suggestedQuestion, $suggestedAnswerScores];
    }

    // Saves one rubric role's question + scoring at a time (one modal = one
    // role = one submit, same pattern as updatePanelMembership()). Picking
    // "ยังไม่กำหนด" (question_id blank) clears this role for this fiscal year
    // instead of validating scores, since there is then nothing to score.
    public function updateScoreRole(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required',
            'role' => 'required|in:' . implode(',', array_keys(AwarenessScoreCalculator::ROLES)),
            'question_id' => 'nullable|integer',
            'scores' => 'nullable|array',
            'raw_scores' => 'nullable|array',
            'correct_values' => 'nullable|array',
        ]);

        $fiscalYear = $request->input('fiscal_year');
        $role = $request->input('role');
        $questionId = $request->input('question_id');
        $roleMeta = AwarenessScoreCalculator::ROLES[$role];

        // "ยังไม่กำหนดคำถามสำหรับข้อนี้" - clear this role off EVERY question
        // for this fiscal year (there's no "the chosen one" to exclude here,
        // unlike the branch below) and stop; nothing left to score.
        if (!$questionId) {
            SurveyYearMapping::where('fiscal_year', $fiscalYear)
                ->where('score_role', $role)
                ->update(['score_role' => null, 'answer_scores' => null]);

            $title = $roleMeta['title'] ?? '';
            return redirect()->route('admin.awareness.settings.scoring', ['fiscal_year' => $fiscalYear])
                ->with('success', 'ยกเลิกการกำหนดคำถามสำหรับ "' . $title . '" เรียบร้อยแล้ว');
        }

        // A role lives on exactly one question at a time - clear it off
        // whichever OTHER question (if any) previously held it before
        // assigning it to the newly-chosen one, same as dashboard_panel.
        SurveyYearMapping::where('fiscal_year', $fiscalYear)
            ->where('score_role', $role)
            ->where('id', '!=', $questionId)
            ->update(['score_role' => null, 'answer_scores' => null]);

        $mapping = SurveyYearMapping::where('fiscal_year', $fiscalYear)->find($questionId);
        if (!$mapping) {
            return redirect()->back()->withErrors(['question_id' => 'ไม่พบคำถามที่เลือก']);
        }

        if ($roleMeta['ui_type'] === 'knowledge') {
            // Both "correct_values" and "scores" below are submitted
            // nested under every question's own id (see the settings-
            // scoring.blade.php field-name comments) - the modal renders
            // one of these inputs per real question in the whole list,
            // hidden unless it's the one actually picked, and a hidden
            // input still submits along with the form regardless of CSS
            // display. Without the question-id nesting, two different
            // questions could collide on the same field name (identical
            // answer wording, or simply both being "correct_values") and
            // whichever sat later in the page would silently win over the
            // one the admin actually saw and edited. Reading only this
            // question's own bucket sidesteps that entirely.
            $correctValues = array_values(array_filter(array_map(
                'trim',
                explode(',', (string) ($request->input('correct_values')[$questionId] ?? ''))
            ), fn ($v) => $v !== ''));

            if (empty($correctValues)) {
                return redirect()->back()->withErrors([
                    'correct_values' => 'กรุณาระบุค่าคำตอบที่ถือว่าถูกต้องอย่างน้อย 1 ค่า',
                ])->withInput();
            }

            $answerScores = [AwarenessScoreCalculator::KNOWLEDGE_CORRECT_KEY => $correctValues];
        } else {
            // Keys arrive base64-encoded (see settings-scoring.blade.php's
            // <select name="scores[...]"> comment) - PHP's own form-body
            // parser rewrites a space or dot inside a raw array key to an
            // underscore, which would silently corrupt any answer text
            // containing one (e.g. every Section 2 frequency option, all of
            // which have a space) into a key that never matches again on
            // the next read, reverting that answer's score back to unset.
            // "scores" is ALSO nested under the question's own id, same
            // reason and same fix as correct_values above.
            $rawScores = (array) ($request->input('scores')[$questionId] ?? []);
            $answerScores = [];
            foreach ($rawScores as $encodedAnswerValue => $score) {
                if ($score === null || $score === '') {
                    continue;
                }
                $answerValue = base64_decode($encodedAnswerValue);
                $answerScores[$answerValue] = (float) $score;
            }

            // "คะแนนเต็ม" (the criteria file's raw 1-5 / 0-1 code, purely a
            // reference display next to the "คะแนนแปลง" picker above - see
            // RAW_SCORE_STORE_KEY) - same base64 + question-id nesting fix as
            // "scores", stored under one reserved sub-key so it never
            // collides with a real answer's own "คะแนนแปลง" entry above.
            // Never read by AwarenessScoreCalculator; only round-trips an
            // admin's manual pick back onto this same screen.
            $rawScoreInputs = (array) ($request->input('raw_scores')[$questionId] ?? []);
            $rawScoreEntries = [];
            foreach ($rawScoreInputs as $encodedAnswerValue => $rawScore) {
                if ($rawScore === null || $rawScore === '') {
                    continue;
                }
                $answerValue = base64_decode($encodedAnswerValue);
                $rawScoreEntries[$answerValue] = (float) $rawScore;
            }
            if (!empty($rawScoreEntries)) {
                $answerScores[self::RAW_SCORE_STORE_KEY] = $rawScoreEntries;
            }
        }

        $mapping->score_role = $role;
        $mapping->answer_scores = $answerScores ?: null;
        $mapping->save();

        $title = $roleMeta['title'] ?? '';
        return redirect()->route('admin.awareness.settings.scoring', ['fiscal_year' => $fiscalYear])
            ->with('success', 'บันทึกคะแนนของ "' . $title . '" เรียบร้อยแล้ว');
    }

    // Saves every role in ONE category at once - the "filter by หมวดหมู่"
    // workflow on settings-scoring: instead of opening each role's modal one
    // at a time (updateScoreRole() above), the whole section's questions +
    // score pickers render inline in a single <form>, and this submits all
    // of them together. Same per-role save logic as updateScoreRole(),
    // just looped over every role sharing the submitted "section" and
    // wrapped in one DB transaction so a half-filled-in role never leaves
    // the others partially saved. A role that can't be saved (no matching
    // question, or a knowledge-type role with no correct value entered) is
    // simply skipped - left exactly as it was before this submit - and
    // named back to the admin in the flash message, rather than aborting
    // the whole category's save over one incomplete role.
    public function updateScoreCategory(Request $request)
    {
        $validSections = array_values(array_unique(array_column(AwarenessScoreCalculator::ROLES, 'section')));

        $request->validate([
            'fiscal_year' => 'required',
            'section' => 'required|in:' . implode(',', $validSections),
            'question_id' => 'nullable|array',
            'scores' => 'nullable|array',
            'raw_scores' => 'nullable|array',
            'correct_values' => 'nullable|array',
        ]);

        $fiscalYear = $request->input('fiscal_year');
        $section = $request->input('section');
        $sectionRoles = collect(AwarenessScoreCalculator::ROLES)->filter(fn ($m) => $m['section'] === $section);

        $savedTitles = [];
        $skipped = [];

        DB::transaction(function () use ($request, $fiscalYear, $sectionRoles, &$savedTitles, &$skipped) {
            foreach ($sectionRoles as $role => $roleMeta) {
                $title = $roleMeta['title'] ?? $role;
                $questionId = $request->input('question_id')[$role] ?? null;

                // "ยังไม่กำหนดคำถามสำหรับข้อนี้" - same as updateScoreRole():
                // clear this role off every question for this fiscal year
                // and move on to the next role in the category.
                if (!$questionId) {
                    SurveyYearMapping::where('fiscal_year', $fiscalYear)
                        ->where('score_role', $role)
                        ->update(['score_role' => null, 'answer_scores' => null]);
                    continue;
                }

                SurveyYearMapping::where('fiscal_year', $fiscalYear)
                    ->where('score_role', $role)
                    ->where('id', '!=', $questionId)
                    ->update(['score_role' => null, 'answer_scores' => null]);

                $mapping = SurveyYearMapping::where('fiscal_year', $fiscalYear)->find($questionId);
                if (!$mapping) {
                    $skipped[] = "{$title} (ไม่พบคำถามที่เลือก)";
                    continue;
                }

                if (($roleMeta['ui_type'] ?? null) === 'knowledge') {
                    $correctValues = array_values(array_filter(array_map(
                        'trim',
                        explode(',', (string) ($request->input('correct_values')[$role][$questionId] ?? ''))
                    ), fn ($v) => $v !== ''));

                    if (empty($correctValues)) {
                        $skipped[] = "{$title} (ยังไม่ได้ระบุค่าคำตอบที่ถือว่าถูกต้อง)";
                        continue;
                    }

                    $answerScores = [AwarenessScoreCalculator::KNOWLEDGE_CORRECT_KEY => $correctValues];
                } else {
                    // Same base64 + (question id, and now also role) nesting
                    // fix as updateScoreRole() - see that method's own
                    // comment for why the encoding is needed; the extra
                    // role level here keeps every role's answers in their
                    // own bucket now that one form covers many roles at
                    // once.
                    $rawScores = (array) ($request->input('scores')[$role][$questionId] ?? []);
                    $answerScores = [];
                    foreach ($rawScores as $encodedAnswerValue => $score) {
                        if ($score === null || $score === '') {
                            continue;
                        }
                        $answerScores[base64_decode($encodedAnswerValue)] = (float) $score;
                    }

                    $rawScoreInputs = (array) ($request->input('raw_scores')[$role][$questionId] ?? []);
                    $rawScoreEntries = [];
                    foreach ($rawScoreInputs as $encodedAnswerValue => $rawScore) {
                        if ($rawScore === null || $rawScore === '') {
                            continue;
                        }
                        $rawScoreEntries[base64_decode($encodedAnswerValue)] = (float) $rawScore;
                    }
                    if (!empty($rawScoreEntries)) {
                        $answerScores[self::RAW_SCORE_STORE_KEY] = $rawScoreEntries;
                    }
                }

                $mapping->score_role = $role;
                $mapping->answer_scores = $answerScores ?: null;
                $mapping->save();

                $savedTitles[] = $title;
            }
        });

        $message = 'บันทึกคะแนนของหมวดนี้เรียบร้อยแล้ว (' . count($savedTitles) . ' ข้อ)';
        if (!empty($skipped)) {
            $message .= ' - ข้ามไป ' . count($skipped) . ' ข้อที่ยังไม่พร้อมบันทึก: ' . implode(', ', $skipped);
        }

        return redirect()->route('admin.awareness.settings.scoring', ['fiscal_year' => $fiscalYear, 'section' => $section])
            ->with(empty($skipped) ? 'success' : 'error', $message);
    }
}
