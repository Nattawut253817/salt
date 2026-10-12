<?php

namespace App\Imports;

/**
 * Pure row-mapping logic for the OLD "เมนูลดโซเดียม" Excel format (fiscal
 * years 2567/2568), used by ReducedSodiumMenuImportLegacy.
 *
 * Unlike ReducedSodiumMenuImportV2 - where the import dialog's chosen
 * fiscal year + province are stamped onto every row, because a 2569+ file
 * is always a single-province batch - these legacy files bundle several
 * provinces' historical data together in one workbook. So year and
 * province must be read PER ROW from the file itself; the dialog's chosen
 * year is kept only as a fallback for a cell that isn't a usable number,
 * and there is no sensible fallback for a missing province at all (an
 * old row with a blank province cell is simply skipped).
 *
 * Kept free of any Laravel/Eloquent/collection dependency so it can be
 * exercised in isolation - ReducedSodiumMenuImportLegacy is the thin
 * wrapper that calls this per row and does the actual DB read/write.
 */
class ReducedSodiumMenuLegacyRowMapper
{
    /**
     * The legacy file's header, column-for-column. Note the differences
     * from ReducedSodiumMenuImportV2::EXPECTED_HEADERS: no trailing "*" on
     * the menu-name column, no "(มก.)" suffix on the sodium columns, a
     * trailing "." on the kitchen-type column, and no 11th "รูปภาพเมนู"
     * column at all (these old files never carried embedded images).
     */
    protected const EXPECTED_HEADERS = [
        'ปีงบประมาณ',
        'จังหวัด',
        'อำเภอ',
        'ประเภทหน่วยงาน',
        'หน่วยงาน',
        'โรงครัวรพ./ร้านอาหารในรพ.',
        'ชื่อเมนูอาหาร',
        'ปริมาณโซเดียมก่อนปรับสูตร',
        'ปริมาณโซเดียมหลังปรับสูตร',
        'หมายเหตุ',
    ];

    /**
     * Throws (with a Thai message naming the exact column + expected vs.
     * actual text) on the first header cell that doesn't match - same
     * strict, exact-text gate style as ReducedSodiumMenuImportV2's own
     * assertHeaderMatches(), just against the legacy header set.
     */
    public static function assertHeaderMatches(array $headerRow): void
    {
        foreach (self::EXPECTED_HEADERS as $index => $expected) {
            $actual = trim((string) ($headerRow[$index] ?? ''));
            if ($actual !== $expected) {
                throw new \Exception(
                    "รูปแบบไฟล์ไม่ถูกต้อง: คอลัมน์ที่ " . ($index + 1) .
                    " ต้องเป็น \"{$expected}\" แต่พบ \"{$actual}\""
                );
            }
        }
    }

    /**
     * Maps one data row to the fields ReducedSodiumMenu needs, or returns
     * null when the row should be skipped entirely (fully blank, no menu
     * name, or no province - in that last case there's no per-row
     * fallback to substitute, unlike year).
     *
     * $fallbackYear is the fiscal year chosen in the import dialog; it's
     * used only when the row's own year cell isn't numeric.
     */
    public static function mapRow(array $row, $fallbackYear): ?array
    {
        if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
            return null;
        }

        $menuName = trim((string) ($row[6] ?? ''));
        if ($menuName === '') {
            return null;
        }

        $province = trim((string) ($row[1] ?? ''));
        if ($province === '') {
            return null;
        }

        $year = is_numeric($row[0] ?? null) ? (int) $row[0] : $fallbackYear;

        $district = trim((string) ($row[2] ?? '')) ?: '-';
        $orgType = trim((string) ($row[3] ?? '')) ?: '-';
        $orgName = trim((string) ($row[4] ?? '')) ?: '-';
        $kitchenType = trim((string) ($row[5] ?? '')) ?: 'โรงครัว';

        $sodiumBefore = is_numeric($row[7] ?? null) ? (float) $row[7] : null;
        $sodiumAfter = is_numeric($row[8] ?? null) ? (float) $row[8] : null;

        return [
            'year' => $year,
            'province' => $province,
            'district' => $district,
            'org_type' => $orgType,
            'org_name' => $orgName,
            'kitchen_type' => $kitchenType,
            'menu_name' => $menuName,
            'sodium_before' => $sodiumBefore,
            'sodium_after' => $sodiumAfter,
        ];
    }
}
