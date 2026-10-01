<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'FY69 Records: ' . \App\Models\AwarenessAssessmentFy69::count() . PHP_EOL;
echo 'FY68 Records: ' . \App\Models\AwarenessAssessment::count() . PHP_EOL;
