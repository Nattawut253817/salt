<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single respondent's survey row for any fiscal year. The demographic
 * columns (hospital, hcode, province, ..., education) are identical every
 * year; every question answer - however many questions, worded however
 * that year's form words them - lives in survey_data as JSON, keyed
 * q1, q2, ... See SurveyYearMapping for turning those keys back into
 * human-readable labels.
 */
class SodiumSurvey extends Model
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
        'birth_date',
        'birth_date_unknown',
        'age_months',
        'income',
        'education',
        'education_other',
        'congenital_disease',
        'no_congenital_disease',
        'congenital_disease_other',
        'survey_data',
        'update_date',
    ];

    protected $casts = [
        'survey_data' => 'array',
        'survey_date' => 'datetime',
        'birth_date' => 'datetime',
        'update_date' => 'date',
    ];

    /**
     * This row's answer to one question, looked up by a stable cross-year
     * semantic key (e.g. 'is_aware_health') instead of a raw survey_data
     * key. Returns null when this row's fiscal year has no question
     * tagged with that key.
     */
    public function answerFor(string $semanticKey): ?string
    {
        $key = SurveyYearMapping::questionKeyFor($this->fiscal_year, $semanticKey);
        if (!$key) {
            return null;
        }

        return $this->survey_data[$key] ?? null;
    }

    /**
     * Every dynamic answer on this row paired with its label and sort
     * order, ready to render (e.g. in the admin detail view) without the
     * caller needing to know this fiscal year's form shape.
     */
    public function labeledAnswers()
    {
        $labels = SurveyYearMapping::labelsFor($this->fiscal_year);
        $data = $this->survey_data ?? [];

        return collect($data)
            ->map(function ($value, $key) use ($labels) {
                return [
                    'key'        => $key,
                    'label'      => $labels[$key]['label'] ?? $key,
                    'sort_order' => $labels[$key]['sort_order'] ?? 0,
                    'value'      => $value,
                ];
            })
            ->values()
            ->sortBy('sort_order')
            ->values();
    }
}
