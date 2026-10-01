<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReducedSodiumProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fiscal_year',
        'product_image',
        'product_name',
        'product_type',
        'sodium_amount_before',
        'sodium_amount',
        'standard_certification',
        'manufacturer_name',
        'province_name',
        'update_date',
    ];

    protected $casts = [
        'update_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
