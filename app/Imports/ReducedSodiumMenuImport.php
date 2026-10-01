<?php

namespace App\Imports;

use App\Models\ReducedSodiumMenu;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReducedSodiumMenuImport implements ToCollection
{
    public $importedCount = 0;

    public function collection(Collection $rows)
    {
        \Log::info('Excel Import - Total Rows: ' . $rows->count());

        // Log first 10 rows to see structure
        $firstRows = $rows->take(10);
        \Log::info('Excel Import - First 10 Rows Raw:', $firstRows->toArray());

        $headerRow = null;
        $dataStartIndex = 0;

        // Try to find the header row
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $rowString = implode(' ', array_map('strval', $rowArray));
            if (str_contains($rowString, 'เมนู') || str_contains($rowString, 'อาหาร') || str_contains($rowString, 'รายการ')) {
                $headerRow = $rowArray;
                $dataStartIndex = $index + 1;
                \Log::info('Found Header Row at index ' . $index, $headerRow);
                break;
            }
        }

        // If no header found, assume it starts at row 0 (index 0)
        $processedCount = 0;
        foreach ($rows->slice($dataStartIndex) as $row) {
            $rowArray = $row->toArray();

            // Skip empty rows
            if (empty(array_filter($rowArray)))
                continue;

            // Map based on index (assuming new structure from image)
            // 0: Year, 1: Prov, 2: Dist, 3: OrgType, 4: OrgName, 5: KitchenType, 6: MenuName, 7: Before, 8: After

            $menuName = $rowArray[6] ?? null;
            if (empty($menuName))
                continue;

            try {
                ReducedSodiumMenu::create([
                    'year' => is_numeric($rowArray[0]) ? $rowArray[0] : (date('Y') + 543),
                    'province' => $rowArray[1] ?? '-',
                    'district' => $rowArray[2] ?? '-',
                    'org_type' => $rowArray[3] ?? '-',
                    'org_name' => $rowArray[4] ?? '-',
                    'kitchen_type' => $rowArray[5] ?? 'โรงครัว',
                    'menu_name' => $menuName,
                    'sodium_before' => is_numeric($rowArray[7]) ? $rowArray[7] : null,
                    'sodium_after' => is_numeric($rowArray[8]) ? $rowArray[8] : null,
                    'user_id' => Auth::id(),
                    'update_date' => now(),
                ]);
                $this->importedCount++;
            } catch (\Exception $e) {
                \Log::error('Individual Row Import Error:', ['row' => $rowArray, 'error' => $e->getMessage()]);
            }
        }

        \Log::info('Import Processed: ' . $processedCount . ' rows.');
    }
}
