<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * ดัชนีผสม (composite) สำหรับ query บนหน้า Dashboard หลัก
     *
     * his (Province_id, year)        — WHERE year = ? AND Province_id IN (...)
     * reduced_sodium_menus (province, year) — WHERE province IN (...) AND year = ?
     *
     * สร้างเฉพาะเมื่อ index ยังไม่มี (กัน error กรณีรัน migration ซ้ำหลัง rollback บางส่วน)
     */
    public function up(): void
    {
        // his: Province_id + year (ลำดับนี้ตรงกับ WHERE Province_id IN (...) + year filter)
        if (!$this->indexExists('his', 'hi_province_year_index')) {
            Schema::table('his', function (Blueprint $table) {
                $table->index(['Province_id', 'year'], 'hi_province_year_index');
            });
        }

        // reduced_sodium_menus: province + year (ลำดับนี้ตรงกับ WHERE province IN (...) AND year = ?)
        if (!$this->indexExists('reduced_sodium_menus', 'rsm_province_year_index')) {
            Schema::table('reduced_sodium_menus', function (Blueprint $table) {
                $table->index(['province', 'year'], 'rsm_province_year_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('his', function (Blueprint $table) {
            $table->dropIndex('hi_province_year_index');
        });
        Schema::table('reduced_sodium_menus', function (Blueprint $table) {
            $table->dropIndex('rsm_province_year_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
