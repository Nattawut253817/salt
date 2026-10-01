<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Lets survey_year_mappings also register the FIXED demographic columns
     * (hospital_name .. congenital_disease, see
     * SodiumSurveyImport::FIXED_COLUMN_COUNT) that every year's Excel file
     * starts with, not just the variable question columns after them.
     *
     * Why: those fixed columns are read by RAW POSITION (row[6] = gender,
     * row[7] = age, row[8] = education, ...) - if a given year's file has
     * them in a different order, the import silently grabs the wrong
     * column. Recording each fixed column's actual header text + position
     * here (is_fixed_column = true) lets the "ตั้งค่าแดชบอร์ด" admin screen
     * show them and let an admin pick, per fiscal year, which one really
     * holds gender/age/education - see demographic_field_mappings and
     * DemographicFieldMapping.
     *
     * column_index is the raw 0-based Excel column position for BOTH kinds
     * of row (fixed and variable) - convenient for the admin picker to
     * display/order by without recomputing it from sort_order.
     */
    public function up(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->boolean('is_fixed_column')->default(false)->after('dashboard_panel');
            $table->unsignedInteger('column_index')->nullable()->after('is_fixed_column');
        });
    }

    public function down(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->dropColumn(['is_fixed_column', 'column_index']);
        });
    }
};
