<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม column no_cluster ใน kidney_operating_area_pins
 * เมื่อ no_cluster = true: พิน นี้จะไม่ถูกจับกลุ่มกับพิน อื่นอัตโนมัติ
 * (แต่ละพื้นที่มีแบบประเมินแยกกัน แม้ปักพร้อมกัน)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kidney_operating_area_pins', function (Blueprint $table) {
            $table->boolean('no_cluster')->default(false)->after('display_only');
        });
    }

    public function down(): void
    {
        Schema::table('kidney_operating_area_pins', function (Blueprint $table) {
            $table->dropColumn('no_cluster');
        });
    }
};
