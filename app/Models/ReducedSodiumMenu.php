<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReducedSodiumMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'province',
        'district',
        'org_type',
        'org_name',
        'kitchen_type',
        'menu_name',
        'sodium_before',
        'sodium_after',
        'agency',
        'user_id',
        'product_image',
        'update_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
