<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaltAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fiscal_year',
        'quarter',
        'ans_1_detail',
        'ans_1_file',
        'ans_2_detail',
        'ans_2_file',
        'ans_3_detail',
        'ans_3_file',
        'ans_4_detail',
        'ans_4_file',
        'ans_5_1_detail',
        'ans_5_1_file',
        'ans_5_2_detail',
        'ans_5_2_file',
        'ans_5_3_detail',
        'ans_5_3_file',
        'ans_5_4_detail',
        'ans_5_4_file',
        'ans_5_5_detail',
        'ans_5_5_file',
        'problems',
        'suggestions',
        'reporter_metadata',
        'is_read',
        'confirmed_at',
        'confirmed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'reporter_metadata' => 'array',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the assessment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the (Level 1) user who confirmed this assessment, if any.
     */
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
