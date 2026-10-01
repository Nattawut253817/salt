<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Same problem the awareness_assessments / sodium_surveys tables
     * already had fixed for them (see add_awareness_performance_indexes
     * and add_composite_and_created_at_indexes_to_sodium_surveys_table):
     * these four tables back a public report page whose filters (ปีงบ,
     * จังหวัด, อำเภอ, ประเภท, ...) are applied on every single page view and
     * every filter change, but the columns those filters query on had no
     * index beyond the primary key - forcing a full table scan each time.
     *
     * - his (อัตราป่วยรายใหม่ HT / new-ht-cases): filtered by year,
     *   Province_id, District_name.
     * - kidney_assessments (พชอ.ไต): filtered by fiscal_year (and quarter)
     *   independently of the existing (user_id, fiscal_year, quarter)
     *   unique index, which can't be used for a fiscal_year-only lookup
     *   since user_id is its leftmost column.
     * - reduced_sodium_menus / reduced_sodium_products (เมนู/ผลิตภัณฑ์ลด
     *   โซเดียม): filtered by year/fiscal_year, province/province_name,
     *   district, and org_name/product_type.
     */
    public function up(): void
    {
        Schema::table('his', function (Blueprint $table) {
            $table->index('year', 'his_year_index');
            $table->index('Province_id', 'his_province_id_index');
            $table->index('District_name', 'his_district_name_index');
        });

        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->index('fiscal_year', 'kidney_assessments_fiscal_year_index');
            $table->index('quarter', 'kidney_assessments_quarter_index');
        });

        Schema::table('reduced_sodium_menus', function (Blueprint $table) {
            $table->index('year', 'reduced_sodium_menus_year_index');
            $table->index('province', 'reduced_sodium_menus_province_index');
            $table->index('district', 'reduced_sodium_menus_district_index');
            $table->index('org_name', 'reduced_sodium_menus_org_name_index');
        });

        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->index('province_name', 'reduced_sodium_products_province_name_index');
            $table->index('fiscal_year', 'reduced_sodium_products_fiscal_year_index');
            $table->index('product_type', 'reduced_sodium_products_product_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('his', function (Blueprint $table) {
            $table->dropIndex('his_year_index');
            $table->dropIndex('his_province_id_index');
            $table->dropIndex('his_district_name_index');
        });

        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropIndex('kidney_assessments_fiscal_year_index');
            $table->dropIndex('kidney_assessments_quarter_index');
        });

        Schema::table('reduced_sodium_menus', function (Blueprint $table) {
            $table->dropIndex('reduced_sodium_menus_year_index');
            $table->dropIndex('reduced_sodium_menus_province_index');
            $table->dropIndex('reduced_sodium_menus_district_index');
            $table->dropIndex('reduced_sodium_menus_org_name_index');
        });

        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->dropIndex('reduced_sodium_products_province_name_index');
            $table->dropIndex('reduced_sodium_products_fiscal_year_index');
            $table->dropIndex('reduced_sodium_products_product_type_index');
        });
    }
};
