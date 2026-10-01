<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\SodiumProductTemplateDataSheet;
use App\Exports\Sheets\SodiumProductTemplateInstructionsSheet;

/**
 * Downloadable blank template for the "นำเข้า Excel" import modal on the
 * ผลิตภัณฑ์ลดโซเดียม admin page (AdminController::downloadSodiumProductsTemplate).
 * Sheet order matters: the data sheet MUST be first, since the importer
 * (App\Imports\ReducedSodiumProductImport) reads the file's first sheet.
 */
class SodiumProductTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new SodiumProductTemplateDataSheet(),
            new SodiumProductTemplateInstructionsSheet(),
        ];
    }
}
