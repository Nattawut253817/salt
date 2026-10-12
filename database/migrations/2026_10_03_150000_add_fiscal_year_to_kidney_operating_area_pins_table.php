<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ปีงบประมาณ (fiscal year) for each saved operating-area pin, per the
 * user's own request. "จัดการพื้นที่ดำเนินงาน" shares its fiscal-year list
 * with admin.kidney-dhb-list ("รายการข้อมูล พชอ.ไต") - both use
 * FiscalYear::selectableYearsFor('kidney', ...) - since this feature lives
 * under the same พชอ.ไต module (see AdminController::operatingAreasIndex()).
 *
 * Nullable, like kidney_assessments' own fiscal_year-less predecessor
 * columns (e.g. reduced_sodium_products.fiscal_year), so this migration
 * never fails on rows that already exist; the backfill right below
 * immediately sets every pre-existing pin to 2569 - the fiscal year all of
 * today's existing data was entered under - and the admin form's own
 * validation (AdminController::storeOperatingAreaPin()) requires it for
 * every pin saved from here on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kidney_operating_area_pins', function (Blueprint $table) {
            $table->integer('fiscal_year')->nullable()->after('village_name_snapshot');
        });

        DB::table('kidney_operating_area_pins')
            ->whereNull('fiscal_year')
            ->update(['fiscal_year' => 2569]);
    }

    public function down(): void
    {
        Schema::table('kidney_operating_area_pins', function (Blueprint $table) {
            $table->dropColumn('fiscal_year');
        });
    }
};
