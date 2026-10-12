<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "หมุดแสดงผล" ที่ SuperAdmin เพิ่มย้อนหลัง/ปักใหม่ในหน้าจัดการพื้นที่ดำเนินงาน:
 * บอกเพียงว่าพื้นที่นี้มีการดำเนินงานด้าน พชอ.ไต และหน่วยงานใดดำเนินการ
 *   display_only - 1 = หมุดแสดงผลอย่างเดียว (ไม่ผูกบัญชีหน่วยงาน ไม่ล็อกพื้นที่ ไม่เข้าแบบรายงาน/หน้าแรก)
 *   agency_label - ชื่อหน่วยงานที่ดำเนินงาน (พิมพ์เอง)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kidney_operating_area_pins', function (Blueprint $table) {
            if (!Schema::hasColumn('kidney_operating_area_pins', 'display_only')) {
                $table->boolean('display_only')->default(false)->after('label');
            }
            if (!Schema::hasColumn('kidney_operating_area_pins', 'agency_label')) {
                $table->string('agency_label', 255)->nullable()->after('display_only');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kidney_operating_area_pins', function (Blueprint $table) {
            if (Schema::hasColumn('kidney_operating_area_pins', 'agency_label')) {
                $table->dropColumn('agency_label');
            }
            if (Schema::hasColumn('kidney_operating_area_pins', 'display_only')) {
                $table->dropColumn('display_only');
            }
        });
    }
};
