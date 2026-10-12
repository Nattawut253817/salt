<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 of "จัดการพื้นที่ดำเนินงาน" (Manage Operating Area), under
 * แบบประเมินพชอ.ไต, level-1 admin only: an admin cascades
 * จังหวัด -> อำเภอ -> ตำบล -> ชุมชน/หมู่บ้าน, places a map pin, and the
 * pin + a data table of all pinned areas are shown. Further workflow on
 * top of these saved pins is intentionally left for a later phase, per
 * the user's own request.
 *
 * village_id is nullable (FK-less, like the rest of this app's location
 * columns) purely as a defensive measure - the admin UI always requires
 * picking a village before saving - in case a future tambon has no
 * village reference data yet. village_name_snapshot keeps the picked
 * village's name as typed at pin time so a row stays readable even if
 * the village reference table is edited or re-imported later.
 *
 * The composite index below is given an explicit short name because
 * Laravel's auto-generated name
 * ("kidney_operating_area_pins_province_id_district_id_tambon_id_index")
 * is 68 characters, past MySQL's 64-character identifier limit
 * (SQLSTATE[42000] error 1059).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kidney_operating_area_pins', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('province_id');
            $table->unsignedInteger('district_id');
            $table->unsignedInteger('tambon_id');
            $table->unsignedInteger('village_id')->nullable();
            $table->string('village_name_snapshot')->nullable();
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->string('label')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['province_id', 'district_id', 'tambon_id'], 'kidney_oa_pins_location_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kidney_operating_area_pins');
    }
};
