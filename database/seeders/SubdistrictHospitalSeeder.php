<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SubdistrictHospitalImport;
use Illuminate\Support\Facades\DB;

class SubdistrictHospitalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = 'C:/Users/ADMIN/Desktop/รพ.สต. เขต 10.xlsx';

        if (!file_exists($filePath)) {
            $this->command->error("File not found at: $filePath");
            return;
        }

        $this->command->info("Importing data from $filePath...");

        // Optional: Clear existing data if needed, or just append
        // DB::table('subdistrict_hospital')->truncate();

        Excel::import(new SubdistrictHospitalImport, $filePath);

        $this->command->info("Import completed successfully.");
    }
}
