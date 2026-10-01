<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            // Free-text name of the specific area/unit that actually
            // carried out the work for this report (e.g. a ตำบล, รพ.สต.,
            // or team name) - one value per report (fiscal_year + quarter),
            // in addition to the reporting agency (สสอ./หน่วยงานที่รายงาน)
            // that was previously the only "who did this" information.
            $table->string('operating_area')->nullable()->after('quarter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropColumn('operating_area');
        });
    }
};
