<?php

namespace App\Imports;

use App\Models\AwarenessAssessmentFy69;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;

class AwarenessAssessmentFy69Import implements ToModel, WithCustomCsvSettings, WithBatchInserts, WithChunkReading, SkipsEmptyRows, WithStartRow
{
    protected $fiscal_year;
    protected $duplicate_action;
    protected $importedCount = 0;
    protected $skippedCount  = 0;
    protected $replacedCount = 0;
    protected $updatedCount  = 0;
    protected $failedCount   = 0;
    protected $errors        = [];

    public function __construct($fiscal_year, $duplicate_action = 'skip')
    {
        $this->fiscal_year      = $fiscal_year;
        $this->duplicate_action = $duplicate_action;
        \Log::info('==== AWARENESS ASSESSMENT FY69 ATOMIC IMPORT START ====');
    }

    public function startRow(): int { return 2; }

    public function getCsvSettings(): array { return ['input_encoding' => 'UTF-8']; }

    public function batchSize(): int { return 1000; }

    public function chunkSize(): int { return 1000; }

    public function model(array $row)
    {
        if (!isset($row[0]) || trim($row[0]) === '') return null;

        $hcode      = $row[1] ?? null;
        $surveyDate = $this->transformDate($row[5] ?? null);
        $gender     = $row[6] ?? null;
        $age        = $row[7] ?? null;
        $edu        = $row[8] ?? null;

        $newData = [
            'hospital_name'                  => $row[0]  ?? null,
            'hcode'                          => $row[1]  ?? null,
            'province_name'                  => $row[2]  ?? null,
            'district_name'                  => $row[3]  ?? null,
            'sub_district'                   => $row[4]  ?? null,
            'survey_date'                    => $surveyDate,
            'gender'                         => $gender,
            'age_range'                      => $age,
            'education'                      => $edu,
            'congenital_disease'             => $row[9]  ?? null,
            'add_seasoning_cook'             => $row[10] ?? null,
            'add_sauce_table'                => $row[11] ?? null,
            'freq_instant_food'              => $row[12] ?? null,
            'freq_processed_food'            => $row[13] ?? null,
            'is_aware_health'                => $row[14] ?? null,
            'is_know_limit'                  => $row[15] ?? null,
            'is_appropriate_intake'          => $row[16] ?? null,
            'importance_level'               => $row[17] ?? null,
            'effort_level'                   => $row[18] ?? null,
            'knowledge_level'                => $row[19] ?? null,
            'behavioral_reduce_salty'        => $row[20] ?? null,
            'behavioral_reduce_processed'    => $row[21] ?? null,
            'behavioral_reduce_soup'         => $row[22] ?? null,
            'behavioral_reduce_dipping'      => $row[23] ?? null,
            'behavioral_increase_veg'        => $row[24] ?? null,
            'behavioral_exercise'            => $row[25] ?? null,
            'behavioral_drink_water'         => $row[26] ?? null,
            'behavioral_confidence_change'   => $row[27] ?? null,
            'support_law'                    => $row[28] ?? null,
            'support_tax'                    => $row[29] ?? null,
            'social_adjust_if_relative_sick' => $row[30] ?? null,
            'social_relative_likes_salty'    => $row[31] ?? null,
            'social_relative_recommends'     => $row[32] ?? null,
            'heard_media'                    => $row[33] ?? null,
            'nearby_restaurants_have_menu'   => $row[34] ?? null,
            'update_date'                    => now(),
        ];

        // If force append mode, skip duplicate check
        if ($this->duplicate_action === 'replace') {
            $this->importedCount++;
            return new AwarenessAssessmentFy69(array_merge(['fiscal_year' => $this->fiscal_year], $newData));
        }

        $existing = AwarenessAssessmentFy69::where('fiscal_year', $this->fiscal_year)
            ->where('hcode', $hcode)
            ->where('survey_date', $surveyDate)
            ->where('gender', $gender)
            ->where('age_range', $age)
            ->where('education', $edu)
            ->first();

        if ($existing) {
            if ($this->duplicate_action === 'replace') {
                $existing->fill($newData)->save();
                $this->replacedCount++;
                return null;
            }

            // Skip mode: compare non-key fields
            $compareFields = [
                'hospital_name', 'province_name', 'district_name', 'sub_district',
                'congenital_disease', 'add_seasoning_cook', 'add_sauce_table',
                'freq_instant_food', 'freq_processed_food', 'is_aware_health',
                'is_know_limit', 'is_appropriate_intake', 'importance_level',
                'effort_level', 'knowledge_level', 'behavioral_reduce_salty',
                'behavioral_reduce_processed', 'behavioral_reduce_soup',
                'behavioral_reduce_dipping', 'behavioral_increase_veg',
                'behavioral_exercise', 'behavioral_drink_water',
                'behavioral_confidence_change', 'support_law', 'support_tax',
                'social_adjust_if_relative_sick', 'social_relative_likes_salty',
                'social_relative_recommends', 'heard_media', 'nearby_restaurants_have_menu',
            ];

            $hasChanges = false;
            foreach ($compareFields as $field) {
                if ((string)($existing->$field ?? '') !== (string)($newData[$field] ?? '')) {
                    $hasChanges = true;
                    break;
                }
            }

            if ($hasChanges) {
                $existing->fill($newData)->save();
                $this->updatedCount++;
            } else {
                $this->skippedCount++;
            }
            return null;
        }

        $this->importedCount++;
        return new AwarenessAssessmentFy69(array_merge(['fiscal_year' => $this->fiscal_year], $newData));
    }

    private function transformDate($value)
    {
        if (!$value) return null;
        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getImportedCount(): int { return $this->importedCount; }
    public function getSkippedCount(): int  { return $this->skippedCount; }
    public function getReplacedCount(): int { return $this->replacedCount; }
    public function getUpdatedCount(): int  { return $this->updatedCount; }
    public function getFailedCount(): int   { return $this->failedCount; }
    public function getErrors(): array      { return $this->errors; }
}
