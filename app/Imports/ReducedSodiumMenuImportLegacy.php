<?php

namespace App\Imports;

use App\Models\ReducedSodiumMenu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Importer for the OLD "เมนูลดโซเดียม" Excel format - fiscal years 2567 and
 * 2568, from before today's "นำเข้า Excel" template existed. Unlike
 * ReducedSodiumMenuImportV2 (fiscal year 2569 onward, completely
 * unchanged by this class), these historical files bundle several
 * provinces' data together in one workbook, so year and province are read
 * PER ROW from the file itself rather than taken from the import dialog -
 * see ReducedSodiumMenuLegacyRowMapper, which does that pure field
 * mapping and is unit-tested in isolation.
 *
 * These old files never carried embedded menu photos, so - unlike V2 -
 * this importer has no $rawRowImages parameter and never touches
 * storage/app/public/menus.
 */
class ReducedSodiumMenuImportLegacy implements ToCollection, WithMultipleSheets
{
    /**
     * Restricts Maatwebsite\Excel to sheet index 0 only, same reasoning
     * as ReducedSodiumMenuImportV2::sheets().
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    protected string $fallbackYear;
    protected string $duplicateAction;

    /**
     * When set (a rank-2/สสจ. user's own province name), any row whose
     * own province cell doesn't match is skipped rather than imported -
     * mirrors the province scoping AdminController::importSodiumMenus()
     * already enforces for the 2569+ path, where the dialog's single
     * province is the only one ever written. Null (rank-1/central users)
     * means every province in the file is imported as-is.
     */
    protected ?string $restrictToProvince;

    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected int $replacedCount = 0;

    public function __construct(string $fallbackYear, string $duplicateAction, ?string $restrictToProvince = null)
    {
        $this->fallbackYear = $fallbackYear;
        $this->duplicateAction = $duplicateAction;
        $this->restrictToProvince = $restrictToProvince;

        \Log::info('==== SODIUM MENU LEGACY IMPORT START (fallback year=' . $fallbackYear . ', mode=' . $duplicateAction . ', restrictToProvince=' . ($restrictToProvince ?? '-') . ') ====');
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            throw new \Exception('ไฟล์ไม่มีข้อมูล กรุณาตรวจสอบไฟล์ที่อัปโหลด');
        }

        ReducedSodiumMenuLegacyRowMapper::assertHeaderMatches($rows->first()->toArray());

        $user = Auth::user();

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }
            $this->importRow($row->toArray(), $user);
        }
    }

    protected function importRow(array $rowArray, $user): void
    {
        $mapped = ReducedSodiumMenuLegacyRowMapper::mapRow($rowArray, $this->fallbackYear);
        if ($mapped === null) {
            return;
        }

        if ($this->restrictToProvince !== null && $mapped['province'] !== $this->restrictToProvince) {
            $this->skippedCount++;
            return;
        }

        $newData = [
            'year' => $mapped['year'],
            'province' => $mapped['province'],
            'district' => $mapped['district'],
            'org_type' => $mapped['org_type'],
            'org_name' => $mapped['org_name'],
            'kitchen_type' => $mapped['kitchen_type'],
            'menu_name' => $mapped['menu_name'],
            'sodium_before' => $mapped['sodium_before'],
            'sodium_after' => $mapped['sodium_after'],
            'user_id' => $user?->id,
            'update_date' => now(),
        ];

        $existing = ReducedSodiumMenu::where('year', $mapped['year'])
            ->where('province', $mapped['province'])
            ->where('district', $mapped['district'])
            ->where('org_name', $mapped['org_name'])
            ->where('kitchen_type', $mapped['kitchen_type'])
            ->where('menu_name', $mapped['menu_name'])
            ->first();

        if ($existing) {
            if ($this->duplicateAction === 'replace') {
                $existing->fill($newData)->save();
                $this->replacedCount++;
                return;
            }

            // 'skip' mode: only touch the row if something actually
            // changed, so re-uploading the same file twice is a true no-op
            // - same rule ReducedSodiumMenuImportV2 applies.
            $changed = (float) ($existing->sodium_before ?? 0) !== (float) ($mapped['sodium_before'] ?? 0)
                || (float) ($existing->sodium_after ?? 0) !== (float) ($mapped['sodium_after'] ?? 0)
                || $existing->org_type !== $mapped['org_type'];

            if ($changed) {
                $existing->fill($newData)->save();
                $this->updatedCount++;
            } else {
                $this->skippedCount++;
            }
            return;
        }

        ReducedSodiumMenu::create($newData);
        $this->importedCount++;
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
