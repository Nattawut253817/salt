<?php

namespace App\Console\Commands;

use App\Models\AwarenessPassSetting;
use App\Models\SodiumSurvey;
use App\Services\AwarenessPassResolver;
use App\Services\AwarenessScoreCalculator;
use App\Services\AwarenessYearGrid;
use Illuminate\Console\Command;

class WarmAwarenessCache extends Command
{
    protected $signature = 'awareness:warm-cache';

    protected $description = 'อุ่นแคชหน้า /awareness ล่วงหน้า (ตารางสรุปรายปี) เพื่อให้เปิดหน้าและเปลี่ยนตัวกรองเร็วตั้งแต่ครั้งแรก';

    public function handle(): int
    {
        $years = SodiumSurvey::query()->distinct()->pluck('fiscal_year');

        foreach ($years as $year) {
            $start = microtime(true);
            AwarenessYearGrid::forYear($year);

            if (AwarenessPassResolver::methodFor($year) === AwarenessPassSetting::METHOD_SCORE) {
                $calculator = new AwarenessScoreCalculator($year);
                if ($calculator->isFullyConfigured()) {
                    AwarenessYearGrid::scoreGrid($year, $calculator);
                }
            }

            $this->info(sprintf('ปี %s: %.2f วินาที', $year, microtime(true) - $start));
        }

        $this->info('เสร็จแล้ว');

        return self::SUCCESS;
    }
}
