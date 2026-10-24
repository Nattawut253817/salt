<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatingAreaPin extends Model
{
    use HasFactory;

    protected $table = 'kidney_operating_area_pins';

    protected $fillable = [
        'fiscal_year',
        'province_id',
        'district_id',
        'tambon_id',
        'village_id',
        'village_name_snapshot',
        'latitude',
        'longitude',
        'label',
        'display_only',
        'no_cluster',
        'agency_label',
        'created_by',
    ];

    protected $casts = [
        'display_only' => 'boolean',
        'no_cluster' => 'boolean',
    ];

    /** true เมื่อรัน migration เพิ่มคอลัมน์ display_only แล้ว (กันหน้าเว็บพังถ้ายังไม่ได้ migrate) */
    public static function hasDisplayColumns(): bool
    {
        static $has = null;
        if ($has === null) {
            $has = \App\Support\SchemaProbe::hasColumn('kidney_operating_area_pins', 'display_only');
        }
        return $has;
    }

    /** true เมื่อรัน migration เพิ่มคอลัมน์ no_cluster แล้ว */
    public static function hasNoClusterColumn(): bool
    {
        static $has = null;
        if ($has === null) {
            $has = \App\Support\SchemaProbe::hasColumn('kidney_operating_area_pins', 'no_cluster');
        }
        return $has;
    }

    /**
     * หมุดที่ใช้รายงานได้จริง - ตัด "หมุดแสดงผล" (SuperAdmin เพิ่มไว้แสดงอย่างเดียว) ออก
     * ใช้กับรายการเลือกพื้นที่/แบบรายงาน/หน้าแรก
     */
    public function scopeReportable($query)
    {
        if (static::hasDisplayColumns()) {
            $query->where(function ($q) {
                $q->where('display_only', false)->orWhereNull('display_only');
            });
        }
        return $query;
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function tambon()
    {
        return $this->belongsTo(Tambon::class, 'tambon_id', 'tambon_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id', 'village_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Every KidneyAssessment report ever filed against this specific
     * registered pin (see KidneyAssessment::operatingAreaPin(), the
     * reverse side of this same operating_area_pin_id column) - used to
     * compute this pin's own "ความก้าวหน้า X/8 ข้อ" on the public
     * kidney-dhb-report pin map.
     */
    public function assessments()
    {
        return $this->hasMany(KidneyAssessment::class, 'operating_area_pin_id');
    }
}
