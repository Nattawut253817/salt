<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;

    protected $table = 'hospital';
    protected $primaryKey = 'hos_id';
    public $timestamps = false;

    protected $fillable = [
        'hos_no',
        'hos_id',
        'hos_name',
        'hos_level',
        'hos_level_text',
        'district_id',
        'province_id',
        'ah_id',
        'hos_year',
        'hos_status'
    ];
}
