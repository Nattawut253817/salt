<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Which of that question's actual answer values count as "ผ่าน" for the
     * criteria role it's assigned to (semantic_key) - e.g. ['ใช่'] or
     * ['เคย', 'ใช่'] - since different years' forms don't always word their
     * yes/no answers the same way. Null means "not chosen yet"; every
     * place that checks pass/fail falls back to ['ใช่', 'เคย'] in that case
     * (see SurveyYearMapping::passValuesFor()) so existing configured
     * years keep behaving exactly as before this column existed.
     *
     * Set from "การประเมินความตระหนักรู้ > ตั้งค่าเกณฑ์ความตระหนักรู้" - see
     * App\Http\Controllers\SurveyQuestionSettingsController::updateCriteria().
     */
    public function up(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->json('criteria_pass_values')->nullable()->after('semantic_key');
        });
    }

    public function down(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->dropColumn('criteria_pass_values');
        });
    }
};
