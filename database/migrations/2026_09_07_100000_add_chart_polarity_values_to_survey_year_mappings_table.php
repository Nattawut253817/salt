<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Which of this question's OWN literal answer values (e.g. "ทุกวัน",
     * "ทุกครั้ง") the admin has explicitly picked as "the most extreme /
     * highest-frequency" answer, straight from a checkbox list of that
     * question's real recorded answers - see "การประเมินความตระหนักรู้ >
     * ตั้งค่าแดชบอร์ด" next to each question (updatePanelMembership()).
     *
     * Null/empty means "not set" - the chart falls back to guessing the
     * rank of each answer from its own wording (getSemanticRank(), a small
     * hardcoded keyword lookup in resources/views/pages/awareness.blade.php),
     * same as every chart before this column existed. When set, any answer
     * in this list is always treated as rank 0 (the reddest/most extreme
     * end BEFORE chart_polarity's negative/positive flip is applied) -
     * this only overrides which answers count as "most extreme", not the
     * separate negative/positive direction choice (chart_polarity), so an
     * admin can fix a question whose real answer wording the built-in
     * keyword guess doesn't recognize, without touching direction at all.
     */
    public function up(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->json('chart_polarity_values')->nullable()->after('chart_polarity');
        });
    }

    public function down(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->dropColumn('chart_polarity_values');
        });
    }
};
