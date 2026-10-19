<?php

namespace App\Console\Commands;

use App\Models\KidneyAssessment;
use App\Models\OperatingAreaPin;
use App\Support\KidneyAreaList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ล้างข้อมูลพื้นที่ดำเนินงาน พชอ.ไต ที่ "ลบแล้วแต่ยังค้าง"
 *
 *   php artisan kidney:cleanup-areas                    ดูรายงานเฉยๆ (ไม่แก้ข้อมูล)
 *   php artisan kidney:cleanup-areas --apply            ลบข้อมูลกำพร้า (อ้างหมุด/รายงานที่ไม่มีอยู่แล้ว)
 *   php artisan kidney:cleanup-areas --apply --unreported
 *        ลบ "การเลือกพื้นที่" ที่ยังไม่มีรายงานเลย (แถว เลือกพื้นที่แล้ว · ยังไม่มีรายงานผล) ด้วย
 *   php artisan kidney:cleanup-areas --apply --unreported --pins
 *        ลบ "หมุดพื้นที่" (ไม่ใช่หมุดแสดงผล) ที่ไม่มีรายงานเลยออกจากระบบด้วย
 */
class CleanupKidneyAreas extends Command
{
    protected $signature = 'kidney:cleanup-areas {--apply : ลบจริง (ไม่ใส่ = ดูรายงานเฉยๆ)} {--unreported : รวมการเลือกพื้นที่ที่ยังไม่มีรายงาน} {--pins : รวมหมุดพื้นที่ที่ไม่มีรายงาน}';
    protected $description = 'ล้างข้อมูลพื้นที่ดำเนินงาน พชอ.ไต ที่ลบแล้วแต่ยังค้างอยู่';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'โหมดลบจริง' : 'โหมดตรวจสอบ (ไม่แก้ข้อมูล) - ใส่ --apply เพื่อลบจริง');

        $pinIds = OperatingAreaPin::pluck('id')->map(fn($v) => (int) $v)->all();
        $pinSet = array_flip($pinIds);

        // รหัสหมุดที่มีรายงานอ้างถึง (พื้นที่หลัก + พื้นที่ร่วมในรายงานเดียวกัน)
        $reported = [];
        foreach (KidneyAssessment::get(['operating_area_pin_id', 'reporter_metadata']) as $r) {
            if ($r->operating_area_pin_id) {
                $reported[(int) $r->operating_area_pin_id] = true;
            }
            foreach (KidneyAreaList::extraPinIds($r) as $x) {
                $reported[(int) $x] = true;
            }
        }

        // 1) การเลือกพื้นที่ที่อ้างหมุดที่ถูกลบไปแล้ว
        $orphanSelections = collect();
        if (Schema::hasTable('kidney_operating_area_selections')) {
            $orphanSelections = DB::table('kidney_operating_area_selections')->get(['id', 'pin_id'])
                ->filter(fn($r) => !isset($pinSet[(int) $r->pin_id]))->pluck('id');
        }
        $this->line('การเลือกพื้นที่ที่หมุดถูกลบแล้ว: ' . $orphanSelections->count());

        // 2) รูปแนบของรายงานที่ถูกลบไปแล้ว
        $orphanImages = collect();
        if (Schema::hasTable('kidney_assessment_images')) {
            $orphanImages = DB::table('kidney_assessment_images as i')
                ->leftJoin('kidney_assessments as a', 'a.id', '=', 'i.kidney_assessment_id')
                ->whereNull('a.id')->pluck('i.id');
        }
        $this->line('รูปแนบของรายงานที่ถูกลบแล้ว: ' . $orphanImages->count());

        // 3) (เลือกได้) การเลือกพื้นที่ที่ยังไม่มีรายงาน
        $unreportedSelections = collect();
        if ($this->option('unreported') && Schema::hasTable('kidney_operating_area_selections')) {
            $unreportedSelections = DB::table('kidney_operating_area_selections')->get(['id', 'pin_id'])
                ->filter(fn($r) => isset($pinSet[(int) $r->pin_id]) && !isset($reported[(int) $r->pin_id]))->pluck('id');
            $this->line('การเลือกพื้นที่ที่ยังไม่มีรายงาน: ' . $unreportedSelections->count());
        }

        // 4) (เลือกได้) หมุดที่ไม่มีรายงานเลย (ไม่นับหมุดแสดงผล)
        $unreportedPins = collect();
        if ($this->option('pins')) {
            $q = OperatingAreaPin::query();
            if (OperatingAreaPin::hasDisplayColumns()) {
                $q->where(fn($w) => $w->whereNull('display_only')->orWhere('display_only', false));
            }
            $unreportedPins = $q->pluck('id')->filter(fn($id) => !isset($reported[(int) $id]))->values();
            $this->line('หมุดพื้นที่ที่ไม่มีรายงานเลย: ' . $unreportedPins->count());
        }

        if (!$apply) {
            $this->comment('ยังไม่ได้ลบอะไร');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($orphanSelections, $orphanImages, $unreportedSelections, $unreportedPins) {
            foreach ($orphanSelections->chunk(500) as $c) {
                DB::table('kidney_operating_area_selections')->whereIn('id', $c)->delete();
            }
            foreach ($orphanImages->chunk(500) as $c) {
                DB::table('kidney_assessment_images')->whereIn('id', $c)->delete();
            }
            foreach ($unreportedSelections->chunk(500) as $c) {
                DB::table('kidney_operating_area_selections')->whereIn('id', $c)->delete();
            }
            foreach ($unreportedPins->chunk(500) as $c) {
                DB::table('kidney_operating_area_selections')->whereIn('pin_id', $c)->delete();
                OperatingAreaPin::whereIn('id', $c)->delete();
            }
        });

        Cache::flush();
        $this->info('ล้างข้อมูลเรียบร้อย และล้างแคชแล้ว');

        return self::SUCCESS;
    }
}
