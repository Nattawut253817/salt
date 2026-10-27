<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * แคชข้อมูลตัวเลือกของหน้าแอดมิน (รายการจังหวัด, ปีที่มีข้อมูลใน dropdown ฯลฯ)
 * - รายการจังหวัดเป็นข้อมูลคงที่ แคช 1 ชั่วโมง
 * - ปีที่มีข้อมูล (distinct) แคช 5 นาที และถูกล้างทันทีเมื่อผู้ดูแลบันทึก/ลบข้อมูลใดๆ (version เดียวกับ PublicPageCache)
 */
class AdminCache
{
    public static function version()
    {
        return Cache::get('public_page_cache_version', 0);
    }

    public static function provinces()
    {
        return Cache::remember('adm.provinces', 3600, fn () => \App\Models\Province::orderBy('province_name', 'asc')->get());
    }

    /** ค่า distinct ของคอลัมน์ในโมเดลนั้น (ไม่รวม null) */
    public static function distinct(string $modelClass, string $column)
    {
        return Cache::remember('adm.distinct.' . md5($modelClass . $column) . '.' . self::version(), 300,
            fn () => $modelClass::query()->distinct()->pluck($column));
    }
}
