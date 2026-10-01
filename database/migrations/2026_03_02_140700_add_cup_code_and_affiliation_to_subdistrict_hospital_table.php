<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subdistrict_hospital', function (Blueprint $table) {
            $table->string('cup_code')->nullable()->after('subdistrict_code')->comment('รหัสแม่ข่าย(CUP)');
            $table->string('affiliation')->nullable()->after('cup_code')->comment('สังกัด_ปัจจุบัน');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subdistrict_hospital', function (Blueprint $table) {
            $table->dropColumn(['cup_code', 'affiliation']);
        });
    }
};
