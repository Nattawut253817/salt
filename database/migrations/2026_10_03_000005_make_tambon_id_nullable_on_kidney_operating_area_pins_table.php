<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Relaxes kidney_operating_area_pins.tambon_id from required to nullable.
 * Per the user's own follow-up request: an operating area is not always
 * pinned all the way down to ตำบล/หมู่บ้าน - sometimes the area of work is
 * only as granular as อำเภอ. province_id and district_id stay required
 * (the admin cascade always picks at least that much); tambon_id and
 * village_id (already nullable since the table was first created) are
 * optional, more-precise refinements.
 *
 * Uses a raw ALTER TABLE rather than Schema::table(...)->change() so this
 * doesn't need doctrine/dbal installed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `kidney_operating_area_pins` MODIFY `tambon_id` INT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Best-effort revert. If any row was saved without a tambon_id in
        // the meantime, this will fail until those rows are given one -
        // that's expected, since NOT NULL can't be restored over NULLs.
        DB::statement('ALTER TABLE `kidney_operating_area_pins` MODIFY `tambon_id` INT UNSIGNED NOT NULL');
    }
};
