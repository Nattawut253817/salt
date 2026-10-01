<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'kidney_assessments_user_id_fiscal_year_quarter_operating_area_unique'; // หรือชื่อคงที่ที่ตั้งไว้

    public function up(): void
    {
        // 1. ลบ Unique Index เดิมแบบปลอดภัย
        if ($this->indexExists('kidney_assessments', 'kidney_assessments_user_id_fiscal_year_quarter_unique')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE kidney_assessments DROP CONSTRAINT IF EXISTS kidney_assessments_user_id_fiscal_year_quarter_unique');
            } else {
                Schema::table('kidney_assessments', function (Blueprint $table) {
                    $table->dropUnique('kidney_assessments_user_id_fiscal_year_quarter_unique');
                });
            }
        }

        // 2. สร้าง Unique Index ใหม่ (ถ้ายังไม่มี)
        if (!$this->indexExists('kidney_assessments', self::INDEX_NAME)) {
            Schema::table('kidney_assessments', function (Blueprint $table) {
                $table->unique(['user_id', 'fiscal_year', 'quarter', 'operating_area'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('kidney_assessments', self::INDEX_NAME)) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE kidney_assessments DROP CONSTRAINT IF EXISTS ' . self::INDEX_NAME);
            } else {
                Schema::table('kidney_assessments', function (Blueprint $table) {
                    $table->dropUnique(self::INDEX_NAME);
                });
            }
        }

        if (!$this->indexExists('kidney_assessments', 'kidney_assessments_user_id_fiscal_year_quarter_unique')) {
            Schema::table('kidney_assessments', function (Blueprint $table) {
                $table->unique(['user_id', 'fiscal_year', 'quarter'], 'kidney_assessments_user_id_fiscal_year_quarter_unique');
            });
        }
    }

    /**
     * ฟังก์ชันเช็กว่ามี Index อยู่หรือไม่ (รองรับทั้ง MySQL และ PostgreSQL)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::select(
                "SELECT COUNT(1) AS cnt
                 FROM pg_indexes
                 WHERE schemaname = 'public'
                   AND tablename = ?
                   AND indexname = ?",
                [$table, $indexName]
            );
            return !empty($result) && $result[0]->cnt > 0;
        }

        // ถ้าเป็น MySQL ให้เช็กผ่าน Schema Manager ของ Doctrine/Laravel
        return count(Schema::getIndexes($table)) > 0 &&
               collect(Schema::getIndexes($table))->contains('name', $indexName);
    }
};
