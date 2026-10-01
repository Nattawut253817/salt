<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Starting FY2569, the awareness survey has its own scoring rubric (see
     * App\Services\AwarenessScoreCalculator) that converts each respondent's
     * answers into a 0-32 total score and a ผ่าน/ไม่ผ่าน result. Each fixed
     * "role" in that rubric (e.g. "2.1 การเติมน้ำปลา", "3.9 Self-Efficacy")
     * needs to be tied to one of THIS fiscal year's actual questions - same
     * idea as dashboard_panel/semantic_key, just a new independent slot:
     *
     * - score_role: which fixed rubric role (AwarenessScoreCalculator::
     *   ROLES key) this question is currently assigned to, or null if it
     *   isn't part of the scoring rubric at all. One question can only hold
     *   one score_role at a time, same as dashboard_panel.
     * - answer_scores: admin's own mapping of this question's real recorded
     *   answer text to the numeric score it's worth (e.g. {"ไม่ทานเลย": 2,
     *   "นานๆครั้ง": 1.5, ...}), keyed exactly like the DISTINCT answers shown
     *   on the settings screen - same "pick the real answers, not a
     *   guess" approach as criteria_pass_values. For the one free-text role
     *   (belief_knowledge / "3.1" ปริมาณโซเดียมที่แนะนำ) this instead holds a
     *   single reserved key, {"__correct_prefix__": ["2000"]} - see
     *   AwarenessScoreCalculator::scoreForRole().
     */
    public function up(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->string('score_role')->nullable()->after('dashboard_panel');
            $table->json('answer_scores')->nullable()->after('score_role');
        });
    }

    public function down(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->dropColumn(['score_role', 'answer_scores']);
        });
    }
};
