<?php

namespace App\Imports;

use App\Models\SubdistrictHospital;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SubdistrictHospitalImport implements ToModel, WithStartRow
{
    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip empty or incomplete rows
        if (!isset($row[0]) || empty($row[0]) || count($row) < 8) {
            return null;
        }

        return new SubdistrictHospital([
            'hospital_code_5_digit' => (string) $row[0],
            'hospital_name' => trim($row[1]),
            'district' => $row[2],
            'province' => $row[3],
            'district_id' => $row[4],
            'province_id' => $row[5],
            'cup_code' => (string) $row[6],
            'affiliation' => $row[7],
            'subdistrict_code' => $row[4], // Mapping district_id to subdistrict_code as per previous SQL example
        ]);
    }
}
