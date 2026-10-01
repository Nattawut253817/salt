<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UsersExport implements WithMultipleSheets
{
    use Exportable;
    
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];

        $query = User::select('Province_id')->distinct();
        
        if (!empty($this->filters['province_id'])) {
            $query->where('Province_id', $this->filters['province_id']);
        }
        if (!empty($this->filters['district_id'])) {
            $query->where('District_id', $this->filters['district_id']);
        }
        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $query->where('is_approved', $this->filters['status']);
        }
        if (!empty($this->filters['rank_id'])) {
            $query->where('User_rank_id', $this->filters['rank_id']);
        }

        $provinceIds = $query->pluck('Province_id')->filter()->toArray();
        $provinces = Province::whereIn('province_id', $provinceIds)->orderBy('province_name', 'asc')->get();

        // 1. Add ODPC 10 Sheet if applicable
        $showODPC = true;
        if (!empty($this->filters['rank_id']) && $this->filters['rank_id'] != 1) {
            $showODPC = false;
        }

        if ($showODPC) {
            $odpcExists = User::where('User_rank_id', 1);
            if (!empty($this->filters['province_id'])) $odpcExists->where('Province_id', $this->filters['province_id']);
            if ($odpcExists->exists()) {
                $sheets[] = new UsersPerProvinceSheet('ODPC10', $this->filters);
            }
        }

        // 2. Add Province Sheets (Excluding Rank 1 internally in UsersPerProvinceSheet)
        foreach ($provinces as $province) {
            $sheets[] = new UsersPerProvinceSheet($province, $this->filters);
        }

        $hasNullProvince = (clone $query)->whereNull('Province_id')->exists();
        if ($hasNullProvince) {
            // Check if there are non-Rank 1 users without a province
            $nullProvQuery = User::whereNull('Province_id')->where('User_rank_id', '!=', 1);
            if ($nullProvQuery->exists()) {
                $sheets[] = new UsersPerProvinceSheet(null, $this->filters);
            }
        }

        return $sheets;
    }
}
