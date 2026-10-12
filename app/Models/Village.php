<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    use HasFactory;

    protected $table = 'village';
    protected $primaryKey = 'village_id';
    public $incrementing = false;

    protected $fillable = [
        'village_id',
        'tambon_id',
        'district_id',
        'province_id',
        'village_name',
        'latitude',
        'longitude',
    ];

    public function tambon()
    {
        return $this->belongsTo(Tambon::class, 'tambon_id', 'tambon_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }
}
