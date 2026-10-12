<?php

namespace App\Imports;

/**
 * "คีย์ตรวจแถวซ้ำ" ของผู้ตอบแบบสอบถาม 1 คน: ปีงบ(อยู่นอกคีย์) + hcode + วันที่ทำแบบสอบถาม + เพศ + อายุ + การศึกษา
 * เป็นชุดคอลัมน์เดียวกับที่ตัวนำเข้าเคยใช้ WHERE ค้นทีละแถว - ตอนนี้ใช้เทียบในหน่วยความจำแทน
 *
 * การเทียบทำเหมือน MySQL: null เท่ากับ null, ข้อความไม่สนตัวพิมพ์เล็ก/ใหญ่และช่องว่างท้าย
 */
final class SodiumSurveyDuplicateKey
{
    /**
     * @param \DateTimeInterface|string|null $surveyDate
     */
    public static function make(?string $hcode, $surveyDate, ?string $gender, ?string $ageRange, ?string $education): string
    {
        if ($surveyDate instanceof \DateTimeInterface) {
            $surveyDate = $surveyDate->format('Y-m-d H:i:s');
        }

        return implode("\x1f", array_map(
            fn ($part) => $part === null ? "\x00" : mb_strtolower(rtrim((string) $part)),
            [$hcode, $surveyDate, $gender, $ageRange, $education]
        ));
    }

    /** จากข้อมูลแถวที่เตรียมจะบันทึก (ผลของ SodiumSurveyImport::buildRowData) */
    public static function fromRow(array $row): string
    {
        return self::make($row['hcode'], $row['survey_date'], $row['gender'], $row['age_range'], $row['education']);
    }
}
