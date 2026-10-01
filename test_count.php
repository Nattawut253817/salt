<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fy69Data = \App\Models\AwarenessAssessmentFy69::query()->get();
$statsFy69Freq = [];
$statsFy69Freq['test_label'] = $fy69Data->countBy('freq_instant_food')->toArray();
echo json_encode($statsFy69Freq);
