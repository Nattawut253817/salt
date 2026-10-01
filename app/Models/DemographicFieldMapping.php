<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Admin override of which raw Excel column index holds gender / age_range /
 * education / congenital_disease for a given fiscal year - see the
 * migration for why this exists (these fields are read by position, and
 * that position can shift - or a column can be inserted before them - from
 * one year's file to the next). One row per (fiscal_year, field_key); no
 * row means "use auto-detection" (see columnIndexFor()/detectColumnIndex()).
 */
class DemographicFieldMapping extends Model
{
    // The 4 fixed sodium_surveys columns whose Excel position an admin can
    // override. 'match_labels' are the exact (trimmed) header texts this
    // field has been seen under, in priority order - checked against the
    // CURRENT file's header before falling back to 'default_column_index'
    // (the position SodiumSurveyImport assumed back when every year's file
    // had these columns in the exact same 10-column layout). Kept here as
    // the single source of truth for the importer's auto-detection/
    // fallback and the admin picker's defaults.
    const FIELDS = [
        'gender' => [
            'title' => 'เพศ',
            'default_column_index' => 6,
            'match_labels' => ['เพศ'],
            'icon' => 'fa-venus-mars',
            'color' => '#0ea5e9',
            'description' => 'คอลัมน์ที่เก็บเพศของผู้ตอบแบบสอบถาม - ปกติเป็นคอลัมน์ที่ 7 ของไฟล์ Excel',
            'preview_image' => 'sex.png',
        ],
        'age_range' => [
            'title' => 'ช่วงอายุ',
            'default_column_index' => 7,
            // 'อายุ(ปี)' first: FY69's file separates out 'อายุ(เดือน)' as
            // its own column, so this must not match that one.
            'match_labels' => ['อายุ(ปี)', 'อายุ'],
            'icon' => 'fa-cake-candles',
            'color' => '#14b8a6',
            'description' => 'คอลัมน์ที่เก็บอายุ/ช่วงอายุของผู้ตอบแบบสอบถาม - ปกติเป็นคอลัมน์ที่ 8 ของไฟล์ Excel',
            'preview_image' => 'age.png',
        ],
        'education' => [
            'title' => 'ระดับการศึกษา',
            'default_column_index' => 8,
            'match_labels' => ['ระดับการศึกษาสูงสุด'],
            'icon' => 'fa-graduation-cap',
            'color' => '#f59e0b',
            'description' => 'คอลัมน์ที่เก็บระดับการศึกษาของผู้ตอบแบบสอบถาม - ปกติเป็นคอลัมน์ที่ 9 ของไฟล์ Excel',
            'preview_image' => 'education.png',
        ],
        'congenital_disease' => [
            'title' => 'โรคประจำตัว',
            'default_column_index' => 9,
            'match_labels' => ['โรคประจำตัว'],
            'icon' => 'fa-notes-medical',
            'color' => '#ef4444',
            'description' => 'คอลัมน์ที่เก็บโรคประจำตัวของผู้ตอบแบบสอบถาม - ปกติเป็นคอลัมน์ที่ 10 ของไฟล์ Excel',
            'preview_image' => null,
        ],
    ];

    protected $fillable = [
        'fiscal_year',
        'field_key',
        'column_index',
    ];

    /**
     * The column index to read for this field/year, in priority order:
     *  1. the admin's explicit override, if one is set;
     *  2. auto-detected from $header (this file's own header row) by
     *     matching FIELDS[$fieldKey]['match_labels'] - so a year whose
     *     file simply has this column at a different position (nothing
     *     else changed) never needs an admin visit at all;
     *  3. FIELDS[$fieldKey]['default_column_index'] - the pre-FY69 fixed
     *     position, used only when $header wasn't given or nothing in it
     *     matched.
     */
    public static function columnIndexFor($fiscalYear, string $fieldKey, ?Collection $header = null): int
    {
        $override = static::where('fiscal_year', $fiscalYear)
            ->where('field_key', $fieldKey)
            ->value('column_index');

        if ($override !== null) {
            return (int) $override;
        }

        if ($header) {
            $detected = static::detectColumnIndex($header, $fieldKey);
            if ($detected !== null) {
                return $detected;
            }
        }

        return self::FIELDS[$fieldKey]['default_column_index'];
    }

    /**
     * Scans $header for the first column whose (trimmed) text exactly
     * matches one of FIELDS[$fieldKey]['match_labels'], trying each label
     * in order before moving to the next. Returns null if none matched -
     * an unrecognized wording, so the caller should fall back to the
     * fixed default position.
     */
    public static function detectColumnIndex(Collection $header, string $fieldKey): ?int
    {
        $labels = self::FIELDS[$fieldKey]['match_labels'] ?? [];

        foreach ($labels as $label) {
            foreach ($header as $index => $cell) {
                if (trim((string) $cell) === $label) {
                    return (int) $index;
                }
            }
        }

        return null;
    }
}
