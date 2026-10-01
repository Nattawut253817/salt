<?php

namespace App\Imports;

use App\Models\AwarenessAssessment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;

class AwarenessAssessmentImport implements ToModel, WithCustomCsvSettings, WithBatchInserts, WithChunkReading, SkipsEmptyRows, WithStartRow
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
        \Log::info('==== AWARENESS ASSESSMENT ATOMIC IMPORT START ====');
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
            'hospital_name'      => $row[0]  ?? null,
            'hcode'              => $row[1]  ?? null,
            'province_name'      => $row[2]  ?? null,
            'district_name'      => $row[3]  ?? null,
            'sub_district'       => $row[4]  ?? null,
            'survey_date'        => $surveyDate,
            'gender'             => $gender,
            'age_range'          => $age,
            'education'          => $edu,
            'congenital_disease' => $row[9]  ?? null,
            'is_aware_health'    => $row[10] ?? null,
            'is_know_limit'      => $row[11] ?? null,
            'freq_instant_food'  => $row[12] ?? null,
            'freq_frozen_food'   => $row[13] ?? null,
            'freq_pickled_food'  => $row[14] ?? null,
            'freq_home_cooked'   => $row[15] ?? null,
            'freq_outside_food'  => $row[16] ?? null,
            'add_seasoning_cook' => $row[17] ?? null,
            'add_sauce_table'    => $row[18] ?? null,
            'freq_high_sodium'   => $row[19] ?? null,
            'order_no_msg'       => $row[20] ?? null,
            'importance_level'   => $row[21] ?? null,
            'effort_level'       => $row[22] ?? null,
            'knowledge_level'    => $row[23] ?? null,
            'update_date'        => now(),
        ];

        // If force append mode, skip duplicate check
        if ($this->duplicate_action === 'replace') {
            $this->importedCount++;
            return new AwarenessAssessment(array_merge(['fiscal_year' => $this->fiscal_year], $newData));
        }

        $existing = AwarenessAssessment::where('fiscal_year', $this->fiscal_year)
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

            // Skip mode: compare non-key fields to detect changes
            $compareFields = [
                'hospital_name', 'province_name', 'district_name', 'sub_district',
                'congenital_disease', 'is_aware_health', 'is_know_limit',
                'freq_instant_food', 'freq_frozen_food', 'freq_pickled_food',
                'freq_home_cooked', 'freq_outside_food', 'add_seasoning_cook',
                'add_sauce_table', 'freq_high_sodium', 'order_no_msg',
                'importance_level', 'effort_level', 'knowledge_level',
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
        return new AwarenessAssessment(array_merge(['fiscal_year' => $this->fiscal_year], $newData));
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
