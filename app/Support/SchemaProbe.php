<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * เช็กว่า "มีตาราง/คอลัมน์หรือยัง" โดยจำผลที่เป็น "มี" ไว้
 *
 * Schema::hasTable() / hasColumn() ยิงไปที่ information_schema ของ MySQL ซึ่งช้า (30-60 ms ต่อครั้ง)
 * แต่เดิมโค้ดเรียกซ้ำทุกคำขอ - ตารางที่สร้างแล้วไม่หายไปเอง จึงจำผล "มี" ได้ปลอดภัย
 * ส่วนผล "ยังไม่มี" ไม่จำ เพื่อให้หน้าเว็บเห็นตาราง/คอลัมน์ใหม่ทันทีหลังรัน migrate
 */
final class SchemaProbe
{
    /** @var array<string, bool> */
    private static array $memo = [];

    public static function hasTable(string $table): bool
    {
        return self::remember('table:' . $table, fn () => Schema::hasTable($table));
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return self::remember('column:' . $table . '.' . $column, fn () => Schema::hasColumn($table, $column));
    }

    private static function remember(string $key, \Closure $check): bool
    {
        if (isset(self::$memo[$key])) {
            return true;
        }

        $cacheKey = 'schema_probe:' . $key;
        if (Cache::get($cacheKey) === true || $check()) {
            Cache::forever($cacheKey, true);

            return self::$memo[$key] = true;
        }

        return false;
    }
}
