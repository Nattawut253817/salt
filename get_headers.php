<?php
require 'vendor/autoload.php';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('temp_import.xlsx');
$worksheet = $spreadsheet->getActiveSheet();
$highestColumn = $worksheet->getHighestColumn();
$headers = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
print_r($headers);
