<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adds dashboard_panel: which of the 4 fixed breakdown panels on the
     * /awareness report a question's answers should be charted in (1-4),
     * or null to not show it at all. Set from the new admin screen
     * ("การประเมินความตระหนักรู้" > "ตั้งค่าคำถามและเกณฑ์การประเมิน") -
     * see App\Http\Controllers\SurveyQuestionSettingsController.
     */
    public function up(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->unsignedTinyInteger('dashboard_panel')->nullable()->after('semantic_key');
        });
    }

    public function down(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->dropColumn('dashboard_panel');
        });
    }
};
