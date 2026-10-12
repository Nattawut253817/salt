<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a kidney-dhb assessment's "พื้นที่การดำเนินงาน" be backed by an
 * actual map location, same pin-picker UI/data shape as
 * kidney_operating_area_pins (จัดการพื้นที่ดำเนินงาน), instead of being
 * only a free-typed name:
 *
 *  - operating_area_pin_id: set when the reporter picked one of the
 *    pins Level 1 already registered via /admin/operating-areas
 *    ("เลือกจากพื้นที่ที่มีอยู่"). Intentionally no FK constraint - every
 *    other location column in this app (village_id, district_id, etc.)
 *    is already FK-less, and a pin getting deleted later shouldn't be
 *    able to block or cascade into an assessment row.
 *  - operating_area_province_id / _district_id / _tambon_id / _village_id
 *    / _village_name_snapshot / _latitude / _longitude: the resolved
 *    hierarchy + coordinates either copied from that pin, or picked
 *    fresh via the form's own cascade+map ("ปักหน่วยงานพื้นที่ใหม่") when no
 *    registered pin fits yet. All nullable: legacy rows (and anyone who
 *    only ever types a free-text area name) keep working unchanged -
 *    the existing `operating_area` text column stays the actual lookup
 *    key for everything (uniqueness, year/quarter record matching,
 *    exports, the kidney-dhb-list filter), this migration only adds
 *    *where that text points to* on a map.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->unsignedBigInteger('operating_area_pin_id')->nullable()->after('operating_area');
            $table->unsignedInteger('operating_area_province_id')->nullable()->after('operating_area_pin_id');
            $table->unsignedInteger('operating_area_district_id')->nullable()->after('operating_area_province_id');
            $table->unsignedInteger('operating_area_tambon_id')->nullable()->after('operating_area_district_id');
            $table->unsignedInteger('operating_area_village_id')->nullable()->after('operating_area_tambon_id');
            $table->string('operating_area_village_name_snapshot')->nullable()->after('operating_area_village_id');
            $table->decimal('operating_area_latitude', 10, 6)->nullable()->after('operating_area_village_name_snapshot');
            $table->decimal('operating_area_longitude', 10, 6)->nullable()->after('operating_area_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'operating_area_pin_id',
                'operating_area_province_id',
                'operating_area_district_id',
                'operating_area_tambon_id',
                'operating_area_village_id',
                'operating_area_village_name_snapshot',
                'operating_area_latitude',
                'operating_area_longitude',
            ]);
        });
    }
};
