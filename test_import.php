<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\AwarenessAssessmentFy69::truncate();

// Create a dummy CSV file with 2500 rows
$fp = fopen('test.csv', 'w');
// header
fputcsv($fp, ['hospital_name', 'hcode', 'province_name', 'district_name', 'sub_district', 'survey_date', 'gender', 'age_range', 'education', 'congenital_disease']);
for ($i = 0; $i < 2500; $i++) {
    fputcsv($fp, ['Hospital A', '12345' . $i, 'Prov', 'Dist', 'Sub', '2023-01-01', 'Male', '20-30', 'BA', 'None']);
}
fclose($fp);

$import = new \App\Imports\AwarenessAssessmentFy69Import('2569', 'skip');
\Maatwebsite\Excel\Facades\Excel::import($import, 'test.csv');

echo "Finished import!\n";
echo "Imported Count inside class: " . $import->getImportedCount() . "\n";
echo "DB Records: " . \App\Models\AwarenessAssessmentFy69::count() . "\n";

unlink('test.csv');
