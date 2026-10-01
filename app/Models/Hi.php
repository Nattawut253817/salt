<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hi extends Model
{
    use HasFactory;

    protected $table = 'his';

    protected $fillable = [
        'year',
        'Province_id',
        'District_name',
        'target_b',
        'total_a',
        'rate_per_100k',
        'm10_oct',
        'm11_nov',
        'm12_dec',
        'm01_jan',
        'm02_feb',
        'm03_mar',
        'm04_apr',
        'm05_may',
        'm06_jun',
        'm07_jul',
        'm08_aug',
        'm09_sep',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class, 'Province_id', 'province_id');
    }
}
