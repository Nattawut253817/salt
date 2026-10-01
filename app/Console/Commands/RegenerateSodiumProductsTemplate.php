<?php

namespace App\Console\Commands;

use App\Exports\SodiumProductTemplateExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Regenerates the fixed .xlsx template served by
 * AdminController::downloadSodiumProductsTemplate() - that route always
 * hands out the static file at storage/app/excel-templates/
 * sodium-products-template.xlsx as-is, it does NOT build one on the fly,
 * so any change to SodiumProductTemplateDataSheet / SodiumProductTemplate
 * InstructionsSheet (columns, dropdowns, wording, ...) only reaches admins
 * downloading the template once this command has been run to rewrite that
 * file from the current sheet classes.
 *
 * Run this once after editing either sheet class, e.g. after the
 * ปริมาณโซเดียมก่อนปรับสูตร column was added:
 *
 * php artisan templates:regenerate-sodium-products
 */
class RegenerateSodiumProductsTemplate extends Command
{
    protected $signature = 'templates:regenerate-sodium-products';

    protected $description = 'Rebuild storage/app/excel-templates/sodium-products-template.xlsx from SodiumProductTemplateExport';

    public function handle(): int
    {
        Excel::store(new SodiumProductTemplateExport(), 'excel-templates/sodium-products-template.xlsx', 'local');

        $this->info('เขียนไฟล์ storage/app/excel-templates/sodium-products-template.xlsx ใหม่เรียบร้อยแล้ว');

        return self::SUCCESS;
    }
}
