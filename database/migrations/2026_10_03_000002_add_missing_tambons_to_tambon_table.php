<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The DOPA village-level dataset used by the new village table (see the
 * create_village_table migration right after this one) references 3
 * tambon codes that are missing from the tambon table imported earlier
 * this project (from the official ข้อมูลตำบล.xlsx the user uploaded):
 *
 *   330102 เมืองใต้    (อำเภอเมืองศรีสะเกษ, จังหวัดศรีสะเกษ)
 *   330115 โพนเขวา     (อำเภอเมืองศรีสะเกษ, จังหวัดศรีสะเกษ)
 *   490503 ซ่ง         (อำเภอคำชะอี, จังหวัดมุกดาหาร)
 *
 * These 3 tambon names are inferred from the DOPA village records that
 * carry each code (e.g. 330102's villages are all "ชุมชน..." names
 * consistent with the เทศบาลเมืองศรีสะเกษ "เมืองใต้" zone, and 490503's
 * villages - นาสีนวน, ซ่ง, โนนสังข์ศรี, โพธิ์ศรี - match the existing
 * ตำบลบ้านซ่ง pattern under a renumbered code) rather than cross-checked
 * against an official gazette, so they are a best-effort fill-in to keep
 * the new village-level cascading select from silently dropping these
 * villages - worth a quick visual confirmation by the user later.
 *
 * insertOrIgnore() so this is safe to run even if one of these 3 codes
 * already exists (e.g. after a future, more complete tambon re-import) -
 * it will never duplicate or overwrite an existing row.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('tambon')->insertOrIgnore([
            [
                'tambon_id' => 330102,
                'district_id' => 3301,
                'province_id' => 33,
                'tambon_name' => 'เมืองใต้',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tambon_id' => 330115,
                'district_id' => 3301,
                'province_id' => 33,
                'tambon_name' => 'โพนเขวา',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tambon_id' => 490503,
                'district_id' => 4905,
                'province_id' => 49,
                'tambon_name' => 'ซ่ง',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('tambon')->whereIn('tambon_id', [330102, 330115, 490503])->delete();
    }
};
