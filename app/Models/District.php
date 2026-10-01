<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $table = 'district';
    protected $primaryKey = 'district_id';
    public $incrementing = false;

    protected $fillable = [
        'district_id',
        'province_id',
        'district_name',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'District_id', 'district_id');
    }
}
