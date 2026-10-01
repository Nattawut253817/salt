<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AwarenessAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year',
        'hospital_name',
        'hcode',
        'province_name',
        'district_name',
        'sub_district',
        'survey_date',
        'gender',
        'age_range',
        'education',
        'congenital_disease',
        'is_aware_health',
        'is_know_limit',
        'freq_instant_food',
        'freq_frozen_food',
        'freq_pickled_food',
        'freq_home_cooked',
        'freq_outside_food',
        'add_seasoning_cook',
        'add_sauce_table',
        'freq_high_sodium',
        'order_no_msg',
        'importance_level',
        'effort_level',
        'knowledge_level',
        'update_date',
    ];
}
