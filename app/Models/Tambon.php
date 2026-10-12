<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tambon extends Model
{
    use HasFactory;

    protected $table = 'tambon';
    protected $primaryKey = 'tambon_id';
    public $incrementing = false;

    protected $fillable = [
        'tambon_id',
        'district_id',
        'province_id',
        'tambon_name',
    ];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }
}
