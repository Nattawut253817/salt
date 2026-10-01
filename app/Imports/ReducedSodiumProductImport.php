<?php

namespace App\Imports;

use App\Models\ReducedSodiumProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Importer behind the "นำเข้า Excel" modal on the ผลิตภัณฑ์ลดโซเดียม admin
 * page (App\Http\Controllers\AdminController::importSodiumProducts).
 *
 * Matches the เมนูลดโซเดียม import's shape: the admin picks ปีงบประมาณ and
 * จังหวัด once for the whole file (Step 1 of the import modal) rather than
 * per row - columns A/B in the sheet stay for backward familiarity with an
 * earlier version of this template but are never read here, so a stray
 * value (or a blank) in either can't split one batch across years/
 * provinces. Both are written straight into the fiscal_year and
 * province_name columns for every row (see the
 * add_fiscal_year_to_reduced_sodium_products_table and
 * add_province_name_to_reduced_sodium_products_table migrations, added
 * because reduced_sodium_products otherwise only has a year/province via
 * user_id -> users.Province_id and update_date).
 *
 * A row counts as a duplicate of an existing product when ปีงบประมาณ +
 * จังหวัด + ชื่อผลิตภัณฑ์ + ผู้ผลิต/แหล่งผลิต all match
 * (case/whitespace-insensitive), then either updates in place (if
 * anything actually changed) or is skipped, or is fully replaced, per
 * $duplicateAction - see the import modal's own wording for exactly what
 * each option promises.
 *
 * Accepts a photo per row via $rawRowImages (Excel row number => raw
 * ['contents' => binary string, 'extension' => string]), extracted by the
 * controller from the sheet's embedded drawings before this class ever
 * sees the file (Maatwebsite\Excel's ToCollection has no access to
 * embedded images) but NOT YET written to disk - this class only writes a
 * row's picture to storage once it has confirmed that row has a real
 * ชื่อผลิตภัณฑ์, so a picture pasted into a row that never gets a name
 * (still blank, or left over from testing) never becomes an orphaned file
 * under storage/app/public/products.
 *
 * Also implements WithMultipleSheets (see sheets() below) so only the
 * template's first "ข้อมูล" sheet is ever handed to collection() - the
 * template also ships a "คำแนะนำ" instructions sheet, and without this,
 * Maatwebsite\Excel calls collection() once per sheet in the workbook (its
 * documented default for a plain ToCollection importer), so
 * assertHeaderMatches() would reject even a perfectly valid file the
 * moment it reached that instructions sheet, since its row 1 looks
 * nothing like this importer's real header.
 */
class ReducedSodiumProductImport implements ToCollection, WithMultipleSheets
{
    /**
     * Restricts Maatwebsite\Excel to sheet index 0 ("ข้อมูล") only - see the
     * class docblock. Returning $this keyed by that index means collection()
     * below still runs exactly as before, just never for the template's
     * other sheet(s).
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    /**
     * Canonical header row for this template, in order - must match
     * App\Exports\Sheets\SodiumProductTemplateDataSheet::array() exactly
     * (columns A-J). Validated BEFORE any row is processed (see
     * assertHeaderMatches()) so a file whose columns don't match this
     * layout is rejected outright and nothing from it is saved.
     */
    protected const EXPECTED_HEADERS = [
        'ปีงบประมาณ',
        'จังหวัด',
        'ชื่อผลิตภัณฑ์ *',
        'ประเภทผลิตภัณฑ์',
        'ปริมาณโซเดียมก่อนปรับสูตร (มก.)',
        'ปริมาณโซเดียมหลังปรับสูตร (มก.)',
        'มาตรฐาน/การรับรอง',
        'ผู้ผลิต/แหล่งผลิต',
        'รูปภาพสินค้า',
        'หมายเหตุ',
    ];

    protected int $fiscalYear;
    protected string $province;
    protected string $duplicateAction;
    protected array $rawRowImages;

    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected int $replacedCount = 0;
    protected int $invalidCount = 0;

    public function __construct(int $fiscalYear, string $province, string $duplicateAction, array $rawRowImages = [])
    {
        $this->fiscalYear = $fiscalYear;
        $this->province = $province;
        $this->duplicateAction = $duplicateAction;
        $this->rawRowImages = $rawRowImages;

        \Log::info('==== SODIUM PRODUCT IMPORT START (year=' . $fiscalYear . ', province=' . $province . ', mode=' . $duplicateAction . ') ====');
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

        // Columns 0/1 (ปีงบประมาณ/จังหวัด) intentionally ignored - see the
        // class docblock. Data columns start at index 2.
        $productName = trim((string) ($rowArray[2] ?? ''));
        $productType = trim((string) ($rowArray[3] ?? '')) ?: null;
        $sodiumAmountBefore = is_numeric($rowArray[4] ?? null) ? (float) $rowArray[4] : null;
        $sodiumAmount = is_numeric($rowArray[5] ?? null) ? (float) $rowArray[5] : null;
        $standard = trim((string) ($rowArray[6] ?? '')) ?: null;
        $manufacturer = trim((string) ($rowArray[7] ?? '')) ?: null;

        if ($productName === '') {
            $this->invalidCount++;
            return;
        }

        // Only write this row's picture to disk now that the row is known
        // to be a real product (has a product name) - see the class docblock.
        $imagePath = $this->writeRowImage($excelRowNumber);

        $newData = [
            'fiscal_year' => $this->fiscalYear,
            'province_name' => $this->province,
            'product_name' => $productName,
            'product_type' => $productType,
            'sodium_amount_before' => $sodiumAmountBefore,
            'sodium_amount' => $sodiumAmount,
            'standard_certification' => $standard,
            'manufacturer_name' => $manufacturer,
            'user_id' => $user?->id,
            'update_date' => now(),
        ];

        $existing = ReducedSodiumProduct::where('fiscal_year', $this->fiscalYear)
            ->where('province_name', $this->province)
            ->whereRaw('LOWER(TRIM(product_name)) = ?', [mb_strtolower($productName)])
            ->where(function ($q) use ($manufacturer) {
                if ($manufacturer === null) {
                    $q->whereNull('manufacturer_name');
                } else {
                    $q->whereRaw('LOWER(TRIM(manufacturer_name)) = ?', [mb_strtolower($manufacturer)]);
                }
            })
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
            $changed = (float) ($existing->sodium_amount ?? 0) !== (float) ($sodiumAmount ?? 0)
                || (float) ($existing->sodium_amount_before ?? 0) !== (float) ($sodiumAmountBefore ?? 0)
                || $existing->product_type !== $productType
                || $existing->standard_certification !== $standard
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
        ReducedSodiumProduct::create($newData);
        $this->importedCount++;
    }

    /**
     * Writes this row's raw extracted picture (if any) to
     * storage/app/public/products and returns its relative path - called
     * only after importRow() has confirmed the row has a real product
     * name, so a picture belonging to a nameless/blank row is simply
     * discarded here and never touches disk.
     */
    protected function writeRowImage(int $excelRowNumber): ?string
    {
        $raw = $this->rawRowImages[$excelRowNumber] ?? null;
        if ($raw === null) {
            return null;
        }

        Storage::disk('public')->makeDirectory('products');
        $filename = 'product_import_' . time() . '_' . $excelRowNumber . '_' . uniqid() . '.' . $raw['extension'];
        Storage::disk('public')->put('products/' . $filename, $raw['contents']);

        return 'products/' . $filename;
    }

    /**
     * Swaps in a newly-extracted photo for an existing product, deleting
     * the old file so re-imports don't quietly pile up orphaned images.
     */
    protected function applyImage(ReducedSodiumProduct $existing, ?string $imagePath, array &$newData): void
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

    public function getInvalidCount(): int
    {
        return $this->invalidCount;
    }
}
