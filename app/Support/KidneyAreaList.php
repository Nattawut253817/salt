<?php

namespace App\Support;

use App\Models\OperatingAreaPin;

/**
 * รายงาน พชอ.ไต 1 ฉบับครอบคลุมได้หลายพื้นที่:
 *  - พื้นที่หลัก  = operating_area / operating_area_pin_id (คีย์เดิมของรายงาน)
 *  - พื้นที่อื่น  = reporter_metadata['_extra_pin_ids'] (รหัสหมุดที่ครอบคลุมเพิ่ม)
 *
 * คลาสนี้รวบรวมเป็นรายการ area_list (พื้นที่ + ประเด็น) เพื่อให้ทุกหน้า
 * (ตาราง/รายละเอียด/PDF/หน้าสาธารณะ/หน้าแรก) แสดงตรงกัน
 */
class KidneyAreaList
{
    public const META_KEY = '_extra_pin_ids';

    /** รหัสหมุดพื้นที่อื่นที่รายงานนี้ครอบคลุมเพิ่ม (ไม่รวมพื้นที่หลัก) */
    public static function extraPinIds($assessment): array
    {
        $meta = is_array($assessment->reporter_metadata ?? null) ? $assessment->reporter_metadata : [];
        $ids = $meta[self::META_KEY] ?? [];
        if (!is_array($ids)) {
            return [];
        }
        $primary = (int) ($assessment->operating_area_pin_id ?? 0);

        return collect($ids)
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => $v > 0 && $v !== $primary)
            ->unique()
            ->values()
            ->all();
    }

    /** ชื่อพื้นที่ (หมู่บ้าน/ตำบล/อำเภอ/จังหวัด) ของหมุด */
    public static function pinPlace(OperatingAreaPin $pin): ?string
    {
        return $pin->village_name_snapshot
            ?? optional($pin->village)->village_name
            ?? optional($pin->tambon)->tambon_name
            ?? optional($pin->district)->district_name
            ?? optional($pin->province)->province_name;
    }

    /** ประเด็น = หมายเหตุของหมุด เมื่อกรอกไว้และไม่ซ้ำกับชื่อพื้นที่ */
    public static function issueText($label, $place): ?string
    {
        $label = trim((string) $label);
        if ($label === '' || $label === trim((string) $place)) {
            return null;
        }

        return $label;
    }

    /**
     * ใส่ area_place / area_issue (พื้นที่หลัก, เหมือนเดิม) และ area_list
     * (ทุกพื้นที่ของรายงาน: place, issue, pin_id, primary) ให้แต่ละรายงาน
     *
     * @param iterable $items KidneyAssessment
     */
    public static function attach($items): void
    {
        $items = collect($items);
        if ($items->isEmpty()) {
            return;
        }

        $ids = $items->flatMap(function ($a) {
            return array_merge(
                $a->operating_area_pin_id ? [(int) $a->operating_area_pin_id] : [],
                self::extraPinIds($a)
            );
        })->unique()->values();

        $pins = $ids->isEmpty()
            ? collect()
            : OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])->whereIn('id', $ids)->get()->keyBy('id');

        foreach ($items as $item) {
            $list = [];

            $primaryPin = $item->operating_area_pin_id ? $pins->get((int) $item->operating_area_pin_id) : null;
            if ($primaryPin) {
                $place = self::pinPlace($primaryPin);
                $item->area_place = $place;
                $item->area_issue = self::issueText($primaryPin->label, $place);
                $list[] = ['pin_id' => (int) $primaryPin->id, 'place' => $place, 'issue' => $item->area_issue, 'primary' => true];
            } else {
                $item->area_place = $item->operating_area;
                $item->area_issue = null;
                if (trim((string) $item->operating_area) !== '') {
                    $list[] = ['pin_id' => null, 'place' => $item->operating_area, 'issue' => null, 'primary' => true];
                }
            }

            foreach (self::extraPinIds($item) as $pid) {
                $pin = $pins->get($pid);
                if (!$pin) {
                    continue; // หมุดถูกลบไปแล้ว
                }
                $place = self::pinPlace($pin);
                $list[] = ['pin_id' => (int) $pin->id, 'place' => $place, 'issue' => self::issueText($pin->label, $place), 'primary' => false];
            }

            $item->area_list = $list;
        }
    }

    /**
     * area_list ของ "หน่วย" (หน่วยงาน + พื้นที่หลัก) ที่รวมหลายไตรมาส:
     * พื้นที่หลักจากรายงานแรกที่ผูกหมุด + พื้นที่อื่นรวมจากทุกไตรมาส (ไม่ซ้ำ)
     *
     * @param iterable $assessments KidneyAssessment ของหน่วยเดียวกัน
     */
    public static function forUnit($assessments, ?string $fallbackName = null): array
    {
        $assessments = collect($assessments)->values();
        if ($assessments->isEmpty()) {
            return $fallbackName ? [['pin_id' => null, 'place' => $fallbackName, 'issue' => null, 'primary' => true]] : [];
        }

        self::attach($assessments);

        $list = [];
        $seen = [];
        $first = $assessments->first(fn($a) => !empty($a->operating_area_pin_id)) ?? $assessments->first();
        foreach ((array) ($first->area_list ?? []) as $row) {
            $seen[$row['pin_id'] ?? ('n:' . $row['place'])] = true;
            $list[] = $row;
        }
        foreach ($assessments as $a) {
            foreach ((array) ($a->area_list ?? []) as $row) {
                if (!empty($row['primary'])) {
                    continue;
                }
                $key = $row['pin_id'] ?? ('n:' . $row['place']);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $list[] = $row;
            }
        }

        if (empty($list) && $fallbackName) {
            $list[] = ['pin_id' => null, 'place' => $fallbackName, 'issue' => null, 'primary' => true];
        }

        return $list;
    }

    /** ข้อความสั้นสำหรับแสดงในบรรทัดเดียว เช่น "ก, ข, ค" */
    public static function inline(array $list, string $sep = ', '): string
    {
        return collect($list)->pluck('place')->filter()->implode($sep);
    }
}
