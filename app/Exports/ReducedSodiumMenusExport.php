<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\MenusSummarySheet;
use App\Exports\Sheets\MenusProvinceSheet;

class ReducedSodiumMenusExport implements WithMultipleSheets
{
    protected $stats;
    protected $filters;
    protected $provinces;

    public function __construct($stats, $filters, $provinces)
    {
        $this->stats = $stats;
        $this->filters = $filters;
        $this->provinces = $provinces;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Summary
        $sheets[] = new MenusSummarySheet($this->stats);

        // Individual Province Sheets
        foreach ($this->provinces as $provinceName) {
            $sheets[] = new MenusProvinceSheet($provinceName, $this->filters);
        }

        return $sheets;
    }
}
