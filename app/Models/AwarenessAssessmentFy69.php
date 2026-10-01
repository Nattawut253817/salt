<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AwarenessAssessmentFy69 extends Model
{
    use HasFactory;

    protected $table = 'awareness_assessments_fy69';

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
        'add_seasoning_cook',
        'add_sauce_table',
        'freq_instant_food',
        'freq_processed_food',
        'is_aware_health',
        'is_know_limit',
        'is_appropriate_intake',
        'importance_level',
        'effort_level',
        'knowledge_level',
        'behavioral_reduce_salty',
        'behavioral_reduce_processed',
        'behavioral_reduce_soup',
        'behavioral_reduce_dipping',
        'behavioral_increase_veg',
        'behavioral_exercise',
        'behavioral_drink_water',
        'behavioral_confidence_change',
        'support_law',
        'support_tax',
        'social_adjust_if_relative_sick',
        'social_relative_likes_salty',
        'social_relative_recommends',
        'heard_media',
        'nearby_restaurants_have_menu',
        'update_date',
    ];
}
