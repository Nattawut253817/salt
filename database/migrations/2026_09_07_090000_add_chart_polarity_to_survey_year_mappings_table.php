<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Whether the "most frequent / most extreme" answer to this question
     * counts as good ('positive', e.g. "ทำอาหารทานเอง ทุกครั้ง" is GOOD) or
     * risky ('negative', e.g. "ทานอาหารนอกบ้าน ทุกวัน" is BAD) - used by the
     * /awareness page's 4 breakdown-panel charts to decide which side of
     * the diverging bar (เสี่ยง / ปลอดภัย) each of that question's answers
     * falls on. Null means "not set" - the chart falls back to guessing
     * from the question's own wording (a small hardcoded keyword lookup in
     * resources/views/pages/awareness.blade.php), same as every chart
     * before this column existed. Set from "การประเมินความตระหนักรู้ >
     * ตั้งค่าแดชบอร์ด" next to each question - see
     * App\Http\Controllers\SurveyQuestionSettingsController::updatePanelMembership().
     */
    public function up(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->string('chart_polarity')->nullable()->after('dashboard_panel');
        });
    }

    public function down(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->dropColumn('chart_polarity');
        });
    }
};
