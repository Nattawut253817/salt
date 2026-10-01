<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KidneyAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fiscal_year',
        'quarter',
        'operating_area',
        'category_1',
        'category_2',
        'category_3',
        'category_4',
        'category_5',
        'category_6',
        'category_7',
        'category_8_1',
        'category_8_2',
        'category_8_3',
        'category_1_file',
        'category_2_file',
        'category_3_file',
        'category_4_file',
        'category_5_file',
        'category_6_file',
        'category_7_file',
        'category_8_1_file',
        'category_8_2_file',
        'category_8_3_file',
        'problems_obstacles',
        'recommendations_opportunities',
        'reporter_metadata',
        'is_read',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'reporter_metadata' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
