<?php

namespace App\Services;

use App\Models\AwarenessPassSetting;
use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ตารางสรุปของ /awareness ต่อ "ปีงบประมาณ" - คำนวณครั้งเดียวต่อปี (ไม่ผูกกับตัวกรองจังหวัด/อำเภอ)
 * เก็บเป็น จังหวัด x อำเภอ x ค่าคำตอบ -> จำนวน แล้วเก็บไว้ในแคช
 *
 * ก่อนหน้านี้ทุกครั้งที่เปลี่ยนจังหวัด/อำเภอ ต้องยิง GROUP BY / JSON_EXTRACT สแกนตาราง sodium_surveys
 * ใหม่ทั้งชุด (ข้อละ 1 สแกน) ตอนนี้สแกนแค่ครั้งแรกต่อปี หลังจากนั้นทุกตัวกรองกรองจากตารางนี้ใน PHP ทันที
 * (อัปเดตล่วงหน้าได้ด้วย `php artisan awareness:warm-cache`)
 *
 * คีย์แคชผูกกับเวลาแก้ไขการตั้งค่า (survey_year_mappings / awareness_pass_settings) เหมือนแคชหลัก
 * บันทึกตั้งค่าแล้วคำนวณใหม่ทันที ส่วนข้อมูลที่นำเข้าใหม่จะขึ้นภายใน TTL (60 นาที) เหมือนเดิม
 */
class AwarenessYearGrid
{
    public const TTL_MINUTES = 60;

    /**
     * @return array{demo: array, pass: ?array, panels: array}
     *   demo   : [[province, district, gender, age_range, education, n], ...]
     *   pass   : null (ปีนี้ไม่ใช้วิธี "questions" หรือยังไม่ตั้งเกณฑ์) หรือ [[province, district, pass, total], ...]
     *   panels : [question_key => [[province, district, value, n], ...]]
     */
    public static function forYear($year): array
    {
        $year = (string) $year;
        $key = 'awareness_yeargrid:' . md5(json_encode([
            $year,
            SurveyYearMapping::where('fiscal_year', $year)->max('updated_at'),
            AwarenessPassSetting::max('updated_at'),
        ]));

        return Cache::remember($key, now()->addMinutes(self::TTL_MINUTES), function () use ($year) {
            return self::build($year);
        });
    }

    public static function build(string $year): array
    {
        $base = SodiumSurvey::query()->where('fiscal_year', $year);

        // ข้อมูลผู้ตอบ: 1 สแกน (ไม่แตะ survey_data) - แทนการดึง age_range ทุกแถวมานับใน PHP
        $demo = (clone $base)
            ->select(['province_name', 'district_name', 'gender', 'age_range', 'education'])
            ->selectRaw('COUNT(*) as n')
            ->groupBy(['province_name', 'district_name', 'gender', 'age_range', 'education'])
            ->get()
            ->map(fn ($r) => [$r->province_name, $r->district_name, $r->gender, $r->age_range, $r->education, (int) $r->n])
            ->all();

        // เกณฑ์ผ่าน/ไม่ผ่านแบบ "questions" (ปีแบบ score ใช้ตารางคะแนนแยกต่างหาก - ดู scoreGrid())
        $pass = null;
        if (AwarenessPassResolver::methodFor($year) !== AwarenessPassSetting::METHOD_SCORE) {
            $awareMappings = SurveyYearMapping::criteriaMappingsFor($year, 'is_aware_health');
            $limitMappings = SurveyYearMapping::criteriaMappingsFor($year, 'is_know_limit');
            if ($awareMappings->isNotEmpty() && $limitMappings->isNotEmpty()) {
                $awareCond = self::criteriaSqlCond($awareMappings);
                $limitCond = self::criteriaSqlCond($limitMappings);
                $pass = (clone $base)
                    ->select(['province_name', 'district_name'])
                    ->selectRaw("SUM(CASE WHEN {$awareCond} AND {$limitCond} THEN 1 ELSE 0 END) as pass_count")
                    ->selectRaw('COUNT(*) as total_count')
                    ->groupBy(['province_name', 'district_name'])
                    ->get()
                    ->map(fn ($r) => [$r->province_name, $r->district_name, (int) $r->pass_count, (int) $r->total_count])
                    ->all();
            }
        }

        // 4 แผงคำถาม: ข้อละ 1 สแกน แต่ทำครั้งเดียวต่อปี (เดิมทำซ้ำทุกครั้งที่เปลี่ยนตัวกรอง)
        $panels = [];
        $questionKeys = SurveyYearMapping::where('fiscal_year', $year)
            ->whereIn('dashboard_panel', SurveyYearMapping::DASHBOARD_PANEL_IDS)
            ->pluck('question_key')
            ->unique();
        foreach ($questionKeys as $questionKey) {
            $expr = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$questionKey}'))";
            $panels[$questionKey] = (clone $base)
                ->select(['province_name', 'district_name'])
                ->selectRaw("{$expr} as field_value")
                ->selectRaw('COUNT(*) as n')
                ->groupBy('province_name', 'district_name', DB::raw($expr))
                ->get()
                ->map(fn ($r) => [$r->province_name, $r->district_name, $r->field_value, (int) $r->n])
                ->all();
        }

        return ['demo' => $demo, 'pass' => $pass, 'panels' => $panels];
    }

    /**
     * ตาราง จังหวัด x อำเภอ -> [pass, total] ของปีแบบ "score" (ให้คะแนนทีละแถวใน PHP - ส่วนที่แพงที่สุด)
     * คีย์แคชเหมือนเดิมทุกประการ (ย้ายมาจาก MainController) จึงใช้แคชเก่าต่อได้
     */
    public static function scoreGrid($year, AwarenessScoreCalculator $calculator): array
    {
        $gridKey = 'awareness_scoregrid:' . md5(json_encode([
            $year,
            SurveyYearMapping::where('fiscal_year', $year)->max('updated_at'),
            AwarenessPassSetting::max('updated_at'),
        ]));

        return Cache::remember($gridKey, now()->addMinutes(self::TTL_MINUTES), function () use ($year, $calculator) {
            $grid = [];
            // ไล่แถวดิบ (ไม่สร้างโมเดล Eloquent) - ต่อแถวเหลือแค่ json_decode 1 ครั้ง + คำนวณคะแนน
            DB::table((new SodiumSurvey)->getTable())->where('fiscal_year', $year)
                ->select(['province_name', 'district_name', 'survey_data', 'id'])
                ->chunkById(2000, function ($rows) use (&$grid, $calculator) {
                    foreach ($rows as $row) {
                        $data = json_decode((string) $row->survey_data, true);
                        $score = $calculator->computeFromData(is_array($data) ? $data : []);
                        $province = (string) $row->province_name;
                        $district = (string) $row->district_name;
                        $cell = $grid[$province][$district] ?? ['pass' => 0, 'total' => 0];
                        $cell['total']++;
                        if ($score && $score['is_pass']) {
                            $cell['pass']++;
                        }
                        $grid[$province][$district] = $cell;
                    }
                });

            return $grid;
        });
    }

    /** "(expr IN (...) OR expr IN (...))" ข้ามทุกคำถามที่ผูกกับเกณฑ์ข้อนั้น - ไม่มีเลยให้ "1=0" (ไม่ผ่านเลย) */
    private static function criteriaSqlCond($mappings): string
    {
        $parts = [];
        foreach ($mappings as $m) {
            $passValues = $m->criteria_pass_values ?: SurveyYearMapping::DEFAULT_CRITERIA_PASS_VALUES;
            $expr = "JSON_UNQUOTE(JSON_EXTRACT(`survey_data`, '$.{$m->question_key}'))";
            $parts[] = "{$expr} IN (" . SurveyYearMapping::sqlInList($passValues) . ")";
        }

        return $parts ? '(' . implode(' OR ', $parts) . ')' : '1=0';
    }
}
