<?php

namespace App\Imports;

use App\Models\ReducedSodiumMenu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Importer behind the "นำเข้า Excel" modal on the เมนูลดโซเดียม admin page
 * (App\Http\Controllers\AdminController::importSodiumMenus). Unlike the
 * older App\Imports\ReducedSodiumMenuImport this one:
 *
 *  - takes the fiscal year and province from the import dialog, not from
 *    the file itself - columns A (ปีงบประมาณ) / B (จังหวัด) in the sheet
 *    are informational only and are never read here, so a stray typo in
 *    the file can't split one batch across years/provinces;
 *  - treats a row as a duplicate of an existing menu when fiscal year,
 *    province, district, org name, kitchen/restaurant type and menu name
 *    all match, and then either skips it (updating in place if any of the
 *    remaining fields actually changed) or fully replaces it, per
 *    $duplicateAction - see the import modal's own wording for exactly
 *    what each option promises;
 *  - accepts a photo per row via $rawRowImages (Excel row number => raw
 *    ['contents' => binary string, 'extension' => string]), extracted by
 *    the controller from the sheet's embedded drawings before this class
 *    ever sees the file (Maatwebsite\Excel's ToCollection has no access to
 *    embedded images) but NOT YET written to disk - this class only
 *    writes a row's picture to storage once it has confirmed that row has
 *    a real ชื่อเมนูอาหาร, so a picture pasted into a row that never gets a
 *    name (still blank, or left over from testing) never becomes an
 *    orphaned file under storage/app/public/menus;
 *  - implements WithMultipleSheets (see sheets() below) so only the
 *    template's first "ข้อมูล" sheet is ever handed to collection() - the
 *    blank template also ships a "รายชื่ออำเภอ" dropdown-reference sheet and
 *    a "คำแนะนำ" instructions sheet, and without this, Maatwebsite\Excel
 *    calls collection() once per sheet in the workbook (its documented
 *    default for a plain ToCollection importer), so assertHeaderMatches()
 *    would reject even a perfectly valid file the moment it reached one of
 *    those other sheets, since neither's row 1 looks anything like this
 *    importer's real header.
 */
class ReducedSodiumMenuImportV2 implements ToCollection, WithMultipleSheets
{
    /**
     * Restricts Maatwebsite\Excel to sheet index 0 ("ข้อมูล") only - see the
     * class docblock. Returning $this keyed by that index means collection()
     * below still runs exactly as before, just never for the template's
     * other sheets.
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    /**
     * Canonical header row for this template, in order - must match
     * App\Exports\Sheets\SodiumMenuTemplateDataSheet::array() exactly
     * (columns A-K). Validated BEFORE any row is processed (see
     * assertHeaderMatches()) so a file whose columns don't match this
     * layout is rejected outright and nothing from it is saved.
     */
    protected const EXPECTED_HEADERS = [
        'ปีงบประมาณ',
        'จังหวัด',
        'อำเภอ',
        'ประเภทหน่วยงาน',
        'หน่วยงาน',
        'โรงครัวรพ./ร้านอาหารในรพ',
        'ชื่อเมนูอาหาร *',
        'โซเดียมก่อนปรับสูตร (มก.)',
        'โซเดียมหลังปรับสูตร (มก.)',
        'หมายเหตุ',
        'รูปภาพเมนู',
    ];

    protected string $fiscalYear;
    protected string $province;
    protected string $duplicateAction;
    protected array $rawRowImages;

    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected int $replacedCount = 0;

    public function __construct(string $fiscalYear, string $province, string $duplicateAction, array $rawRowImages = [])
    {
        $this->fiscalYear = $fiscalYear;
        $this->province = $province;
        $this->duplicateAction = $duplicateAction;
        $this->rawRowImages = $rawRowImages;

        \Log::info('==== SODIUM MENU IMPORT START (year=' . $fiscalYear . ', province=' . $province . ', mode=' . $duplicateAction . ') ====');
    }

    public function collection(Collection $rows)
    {
        // Strict header check FIRST - before any row is touched, so a
        // mismatched file is rejected with nothing saved to the database
        // (see assertHeaderMatches()). This replaces the old keyword-sniff
        // that used to guess where the header row sat.
        $this->assertHeaderMatches($rows);

        $user = Auth::user();

        // Header is always row 0 (index 0) once assertHeaderMatches() has
        // passed - data starts at index 1.
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }
            $excelRowNumber = $index + 1;
            $this->importRow($row, $excelRowNumber, $user);
        }
    }

    /**
     * Compares row 0 against EXPECTED_HEADERS, column by column (trimmed).
     * Throws with a clear Thai message on the first mismatch - the
     * controller catches this as a normal \Exception, rolls back the DB
     * transaction, and shows the message to the admin, so a wrong/garbled
     * file never reaches importRow() at all.
     */
    protected function assertHeaderMatches(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw new \Exception('ไฟล์ไม่มีข้อมูล กรุณาตรวจสอบไฟล์ที่อัปโหลด');
        }

        $header = $rows->first();
        foreach (self::EXPECTED_HEADERS as $i => $expected) {
            $actual = trim((string) ($header[$i] ?? ''));
            if ($actual !== $expected) {
                throw new \Exception(
                    'หัวคอลัมน์ไม่ตรงกับเทมเพลตที่กำหนด (คอลัมน์ที่ ' . ($i + 1)
                    . ' ควรเป็น "' . $expected . '" แต่พบ "' . $actual . '") '
                    . 'กรุณาดาวน์โหลดเทมเพลตล่าสุดแล้วลองใหม่อีกครั้ง'
                );
            }
        }
    }

    protected function importRow(Collection $row, int $excelRowNumber, $user): void
    {
        $rowArray = $row->toArray();
        if (empty(array_filter($rowArray, fn($v) => $v !== null && $v !== ''))) {
            return;
        }

        // Columns 0/1 (ปีงบประมาณ/จังหวัด) intentionally ignored - see
        // the class docblock.
        $district = trim((string) ($rowArray[2] ?? '')) ?: '-';
        $orgType = trim((string) ($rowArray[3] ?? '')) ?: '-';
        $orgName = trim((string) ($rowArray[4] ?? '')) ?: '-';
        $kitchenType = trim((string) ($rowArray[5] ?? '')) ?: 'โรงครัว';
        $menuName = trim((string) ($rowArray[6] ?? ''));

        if ($menuName === '') {
            return;
        }

        $sodiumBefore = is_numeric($rowArray[7] ?? null) ? (float) $rowArray[7] : null;
        $sodiumAfter = is_numeric($rowArray[8] ?? null) ? (float) $rowArray[8] : null;

        // Only write this row's picture to disk now that the row is known
        // to be a real menu (has a menu name) - see the class docblock.
        $imagePath = $this->writeRowImage($excelRowNumber);

        $newData = [
            'year' => $this->fiscalYear,
            'province' => $this->province,
            'district' => $district,
            'org_type' => $orgType,
            'org_name' => $orgName,
            'kitchen_type' => $kitchenType,
            'menu_name' => $menuName,
            'sodium_before' => $sodiumBefore,
            'sodium_after' => $sodiumAfter,
            'user_id' => $user?->id,
            'update_date' => now(),
        ];

        $existing = ReducedSodiumMenu::where('year', $this->fiscalYear)
            ->where('province', $this->province)
            ->where('district', $district)
            ->where('org_name', $orgName)
            ->where('kitchen_type', $kitchenType)
            ->where('menu_name', $menuName)
            ->first();

        if ($existing) {
            if ($this->duplicateAction === 'replace') {
                $this->applyImage($existing, $imagePath, $newData);
                $existing->fill($newData)->save();
                $this->replacedCount++;
                return;
            }

            // 'skip' mode: only touch the row if something actually
            // changed, so re-uploading the same file twice is a true no-op.
            $changed = (float) ($existing->sodium_before ?? 0) !== (float) ($sodiumBefore ?? 0)
                || (float) ($existing->sodium_after ?? 0) !== (float) ($sodiumAfter ?? 0)
                || $existing->org_type !== $orgType
                || ($imagePath !== null && $existing->product_image !== $imagePath);

            if ($changed) {
                $this->applyImage($existing, $imagePath, $newData);
                $existing->fill($newData)->save();
                $this->updatedCount++;
            } else {
                $this->skippedCount++;
            }
            return;
        }

        if ($imagePath !== null) {
            $newData['product_image'] = $imagePath;
        }
        ReducedSodiumMenu::create($newData);
        $this->importedCount++;
    }

    /**
     * Writes this row's raw extracted picture (if any) to
     * storage/app/public/menus and returns its relative path - called only
     * after importRow() has confirmed the row has a real menu name, so a
     * picture belonging to a nameless/blank row is simply discarded here
     * and never touches disk.
     */
    protected function writeRowImage(int $excelRowNumber): ?string
    {
        $raw = $this->rawRowImages[$excelRowNumber] ?? null;
        if ($raw === null) {
            return null;
        }

        Storage::disk('public')->makeDirectory('menus');
        $filename = 'menu_import_' . time() . '_' . $excelRowNumber . '_' . uniqid() . '.' . $raw['extension'];
        Storage::disk('public')->put('menus/' . $filename, $raw['contents']);

        return 'menus/' . $filename;
    }

    /**
     * Swaps in a newly-extracted photo for an existing menu, deleting the
     * old file so re-imports don't quietly pile up orphaned images.
     */
    protected function applyImage(ReducedSodiumMenu $existing, ?string $imagePath, array &$newData): void
    {
        if ($imagePath === null) {
            return;
        }
        if ($existing->product_image && $existing->product_image !== $imagePath) {
            Storage::disk('public')->delete($existing->product_image);
        }
        $newData['product_image'] = $imagePath;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getReplacedCount(): int
    {
        return $this->replacedCount;
    }
}
