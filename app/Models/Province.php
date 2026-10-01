<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $table = 'province';
    protected $primaryKey = 'province_id';
    public $incrementing = false; // User specified int(11) but not auto-increment in UI snippet, but usually PK is AI. I'll stick to what they showed.

    protected $fillable = [
        'province_id',
        'province_name',
        'province_code',
    ];

    public function districts()
    {
        return $this->hasMany(District::class, 'province_id', 'province_id');
    }
}
