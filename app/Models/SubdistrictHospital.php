<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubdistrictHospital extends Model
{
    use HasFactory;

    protected $table = 'subdistrict_hospital';
    protected $primaryKey = 'sh_id';
    public $timestamps = false;

    protected $fillable = [
        'hospital_name',
        'hospital_code_5_digit',
        'service_zone',
        'province',
        'district',
        'subdistrict',
        'province_id',
        'district_id',
        'subdistrict_code',
        'cup_code',
        'affiliation',
    ];
}
