<?php

namespace App\Imports;

use App\Models\Hi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/**
 * Importer behind the "นำเข้าข้อมูล (Excel)" modal on the อัตราป่วยรายใหม่ HT
 * (HI) admin page (App\Http\Controllers\HiController::import()).
 *
 * The template ("แบบฟอร์มนำเข้า HT.xlsx") uses a TWO-row header: row 1 has
 * อำเภอ / B / A / อัตราต่อแสน... in columns A-D (each vertically merged down
 * into row 2) and a single merged "จำนวนผู้ป่วยรายใหม่รายเดือน ตามปีงบประมาณ"
 * label spanning columns E-P; row 2 then carries the 12 individual month
 * abbreviations (ตค. .. กย.) under that merged label. Real data starts at
 * row 3. Both header rows are validated - column by column - BEFORE any
 * row is processed (see assertHeaderMatches()), so a file whose columns
 * don't match this layout is rejected outright with nothing saved.
 */
class HiImport implements ToCollection, SkipsEmptyRows, WithCalculatedFormulas
{
    /** Row 1 (index 0) - columns A-E, by 0-based column index. */
    protected const EXPECTED_ROW1 = [
        0 => 'อำเภอ',
        1 => 'B',
        2 => 'A',
        3 => 'อัตราต่อแสน (A/B)X100,000',
        4 => 'จำนวนผู้ป่วยรายใหม่รายเดือน ตามปีงบประมาณ',
    ];

    /** Row 2 (index 1) - columns E-P (0-based index 4-15), in file order. */
    protected const EXPECTED_MONTH_HEADERS = [
        'ตค.', 'พย.', 'ธค.', 'มค.', 'กพ.', 'มีค.', 'เมย.', 'พค.', 'มิย.', 'กค.', 'สค.', 'กย.',
    ];

    protected $year;
    protected $province_id;
    protected $duplicate_action;
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $replacedCount = 0;
    protected $updatedCount = 0;

    public function __construct($year, $province_id, $duplicate_action = 'skip')
    {
        $this->year = $year;
        $this->province_id = $province_id;
        $this->duplicate_action = $duplicate_action;
    }

    public function collection(Collection $rows)
    {
        // Strict header check FIRST - before any row is touched, so a
        // mismatched file is rejected with nothing saved to the database.
        $this->assertHeaderMatches($rows);

        // Real data starts at row 3 (index 2) - rows 0 and 1 are the
        // two-row header validated above.
        foreach ($rows as $index => $row) {
            if ($index < 2) {
                continue;
            }
            // The template ends with a "รวม" (grand total) row. It is a
            // sum of the district rows above it, not a district, so it is
            // never imported - otherwise it would be saved as a district
            // named "รวม" and double every total on the dashboards.
            if ($this->isTotalRow($row[0] ?? null)) {
                continue;
            }
            $this->importRow($row->toArray());
        }
    }

    /**
     * Compares both header rows against EXPECTED_ROW1 / EXPECTED_MONTH_HEADERS
     * (whitespace-normalized, since row 1's rate-column label has an
     * embedded line break in the template). Throws with a clear Thai
     * message on the first mismatch - the controller catches this as a
     * normal \Exception and shows the message to the admin, so a wrong/
     * garbled file never reaches importRow() at all.
     */
    protected function assertHeaderMatches(Collection $rows): void
    {
        if ($rows->count() < 2) {
            throw new \Exception('ไฟล์ไม่มีข้อมูลหรือรูปแบบไม่ถูกต้อง กรุณาตรวจสอบไฟล์ที่อัปโหลด');
        }

        $normalize = fn($v) => trim(preg_replace('/\s+/u', ' ', (string) ($v ?? '')));

        $row1 = $rows->get(0);
        foreach (self::EXPECTED_ROW1 as $i => $expected) {
            $actual = $normalize($row1[$i] ?? '');
            if ($actual !== $expected) {
                throw new \Exception(
                    'หัวคอลัมน์ไม่ตรงกับเทมเพลตที่กำหนด (แถวที่ 1 คอลัมน์ที่ ' . ($i + 1)
                    . ' ควรเป็น "' . $expected . '" แต่พบ "' . $actual . '") '
                    . 'กรุณาดาวน์โหลดเทมเพลตล่าสุดแล้วลองใหม่อีกครั้ง'
                );
            }
        }

        $row2 = $rows->get(1);
        foreach (self::EXPECTED_MONTH_HEADERS as $j => $expectedMonth) {
            $col = 4 + $j;
            $actual = $normalize($row2[$col] ?? '');
            if ($actual !== $expectedMonth) {
                throw new \Exception(
                    'หัวคอลัมน์ไม่ตรงกับเทมเพลตที่กำหนด (แถวที่ 2 คอลัมน์ที่ ' . ($col + 1)
                    . ' ควรเป็น "' . $expectedMonth . '" แต่พบ "' . $actual . '") '
                    . 'กรุณาดาวน์โหลดเทมเพลตล่าสุดแล้วลองใหม่อีกครั้ง'
                );
            }
        }
    }

    /**
     * True for the footer total row of the template ("รวม", also tolerated:
     * "รวมทั้งหมด", "รวมทั้งสิ้น", "รวมเขต", "รวมจังหวัด", "Total").
     */
    protected function isTotalRow($firstCell): bool
    {
        $label = trim(preg_replace('/\s+/u', '', (string) ($firstCell ?? '')));

        return $label !== ''
            && (bool) preg_match('/^(รวม(ทั้งหมด|ทั้งสิ้น|เขต|จังหวัด)?|total)$/iu', $label);
    }

    /** แปลงค่าในเซลล์เป็นตัวเลข - ค่าที่อ่านไม่ได้ (ข้อความ, สูตรที่ไม่ถูกคำนวณ, ว่าง) เป็น 0 */
    protected function num($value)
    {
        if ($value === null || is_bool($value)) {
            return 0;
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $clean = str_replace([',', ' ', "\u{00A0}"], '', trim((string) $value));
        if ($clean !== '' && is_numeric($clean)) {
            return $clean + 0;
        }

        return 0;
    }

    protected function importRow(array $row): void
    {
        // Column indices:
        // 0: District_name
        // 1: target_b
        // 2: total_a
        // 3: rate_per_100k
        // 4-15: m10_oct through m09_sep

        if (empty($row[0])) {
            return;
        }

        $districtName = trim($row[0]);

        // ตัวเลขทุกช่องผ่าน num(): รองรับสูตรที่ยังไม่ถูกคำนวณ ("=C3*100000/B3"), ตัวเลขมี
        // ลูกน้ำ/ช่องว่าง, "-" หรือช่องว่าง (นับเป็น 0) - ไม่ปล่อยให้ข้อความหลุดไปถึงฐานข้อมูล
        $targetB = $this->num($row[1] ?? null);
        $totalA  = $this->num($row[2] ?? null);

        // อัตราต่อแสน = A x 100,000 / B (ตามหัวคอลัมน์ของเทมเพลต) - คำนวณเองเสมอเมื่อมี B
        // เพื่อไม่พึ่งสูตร/ค่าที่แคชไว้ในไฟล์ (ไฟล์ที่ไม่เคยถูกเปิดคำนวณใน Excel จะไม่มีค่าแคช)
        $rate = $targetB > 0
            ? round($totalA * 100000 / $targetB, 2)
            : $this->num($row[3] ?? null);

        $newData = [
            'target_b'      => $targetB,
            'total_a'       => $totalA,
            'rate_per_100k' => $rate,
            'm10_oct'       => $this->num($row[4] ?? null),
            'm11_nov'       => $this->num($row[5] ?? null),
            'm12_dec'       => $this->num($row[6] ?? null),
            'm01_jan'       => $this->num($row[7] ?? null),
            'm02_feb'       => $this->num($row[8] ?? null),
            'm03_mar'       => $this->num($row[9] ?? null),
            'm04_apr'       => $this->num($row[10] ?? null),
            'm05_may'       => $this->num($row[11] ?? null),
            'm06_jun'       => $this->num($row[12] ?? null),
            'm07_jul'       => $this->num($row[13] ?? null),
            'm08_aug'       => $this->num($row[14] ?? null),
            'm09_sep'       => $this->num($row[15] ?? null),
        ];

        // Check for existing record
        $existing = Hi::where('year', $this->year)
            ->where('Province_id', $this->province_id)
            ->where('District_name', $districtName)
            ->first();

        if ($existing) {
            if ($this->duplicate_action === 'replace') {
                // Replace mode: always overwrite
                $existing->fill($newData)->save();
                $this->replacedCount++;
                return;
            }

            // Skip mode: compare each field; update only if something changed
            $hasChanges = false;
            foreach ($newData as $field => $newValue) {
                // Cast both sides to float for numeric comparison
                if ((float) $existing->$field !== (float) $newValue) {
                    $hasChanges = true;
                    break;
                }
            }

            if ($hasChanges) {
                // At least one field is different → update the record
                $existing->fill($newData)->save();
                $this->updatedCount++;
            } else {
                // All fields identical → truly skip
                $this->skippedCount++;
            }

            return;
        }

        // No existing record → insert new
        $this->importedCount++;

        Hi::create(array_merge([
            'year'          => $this->year,
            'Province_id'   => $this->province_id,
            'District_name' => $districtName,
        ], $newData));
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getReplacedCount()
    {
        return $this->replacedCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }
}
