<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The /awareness page filters both awareness tables by fiscal_year,
     * province_name and district_name (and orders by created_at) on every
     * request, but neither table had any indexes beyond the primary key,
     * forcing a full table scan for every filter and every dropdown list.
     * As the tables grow this makes the page (and its filter clicks)
     * noticeably slower.
     */
    public function up(): void
    {
        Schema::table('awareness_assessments', function (Blueprint $table) {
            $table->index('fiscal_year', 'awareness_assessments_fiscal_year_index');
            $table->index('province_name', 'awareness_assessments_province_name_index');
            $table->index('district_name', 'awareness_assessments_district_name_index');
            $table->index('created_at', 'awareness_assessments_created_at_index');
        });

        Schema::table('awareness_assessments_fy69', function (Blueprint $table) {
            $table->index('fiscal_year', 'awareness_assessments_fy69_fiscal_year_index');
            $table->index('province_name', 'awareness_assessments_fy69_province_name_index');
            $table->index('district_name', 'awareness_assessments_fy69_district_name_index');
            $table->index('created_at', 'awareness_assessments_fy69_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('awareness_assessments', function (Blueprint $table) {
            $table->dropIndex('awareness_assessments_fiscal_year_index');
            $table->dropIndex('awareness_assessments_province_name_index');
            $table->dropIndex('awareness_assessments_district_name_index');
            $table->dropIndex('awareness_assessments_created_at_index');
        });

        Schema::table('awareness_assessments_fy69', function (Blueprint $table) {
            $table->dropIndex('awareness_assessments_fy69_fiscal_year_index');
            $table->dropIndex('awareness_assessments_fy69_province_name_index');
            $table->dropIndex('awareness_assessments_fy69_district_name_index');
            $table->dropIndex('awareness_assessments_fy69_created_at_index');
        });
    }
};
