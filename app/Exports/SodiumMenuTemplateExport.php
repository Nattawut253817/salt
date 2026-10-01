<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\SodiumMenuTemplateDataSheet;
use App\Exports\Sheets\SodiumMenuTemplateInstructionsSheet;

/**
 * Downloadable blank template for the "นำเข้า Excel" import modal on the
 * เมนูลดโซเดียม admin page (AdminController::downloadSodiumMenusTemplate).
 * Sheet order matters: the data sheet MUST be first, since the importer
 * (App\Imports\ReducedSodiumMenuImportV2) reads the file's first sheet.
 */
class SodiumMenuTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new SodiumMenuTemplateDataSheet(),
            new SodiumMenuTemplateInstructionsSheet(),
        ];
    }
}
