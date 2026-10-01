<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per fiscal year that has explicitly chosen how "ตระหนักรู้/ผ่าน
 * เกณฑ์" is decided for it - see App\Services\AwarenessPassResolver, which
 * every consumer of this concept (home dashboard map, /awareness report,
 * admin upload list badge) goes through instead of assuming one fixed rule.
 * A fiscal year with no row here behaves exactly like every year did before
 * this existed (METHOD_QUESTIONS).
 */
class AwarenessPassSetting extends Model
{
    const METHOD_QUESTIONS = 'questions';
    const METHOD_SCORE = 'score';

    // Presentation metadata for the "วิธีตั้งเกณฑ์ผ่าน/ไม่ผ่าน" picker on
    // "ตั้งค่าเกณฑ์ความตระหนักรู้".
    const METHODS = [
        self::METHOD_QUESTIONS => [
            'title' => 'วิธีที่ 1: เลือกคำถามเฉพาะ',
            'description' => 'ผ่านเกณฑ์เมื่อตอบคำถาม "เกณฑ์ข้อ 1" และ "เกณฑ์ข้อ 2" ด้านล่างนี้ตรงกับคำตอบที่กำหนดไว้ทั้งคู่ (แบบเดิม)',
            'icon' => 'fa-list-check',
            'color' => '#d97706',
        ],
        self::METHOD_SCORE => [
            'title' => 'วิธีที่ 2: คิดจากคะแนนที่ตั้งค่าไว้',
            'description' => 'ผ่านเกณฑ์เมื่อคะแนนรวมจากหน้า "ตั้งค่าคะแนนความตระหนักรู้" ถึงเกณฑ์ที่กำหนด (คะแนนรวม ≥ 19.2 จาก 32) - "เกณฑ์ข้อ 1/ข้อ 2" ด้านล่างจะไม่ถูกใช้แล้ว',
            'icon' => 'fa-calculator',
            'color' => '#7c3aed',
        ],
    ];

    protected $fillable = [
        'fiscal_year',
        'method',
    ];

    /**
     * Which method a fiscal year currently uses - METHOD_QUESTIONS when
     * nothing has been chosen for it yet (every year's behavior before this
     * setting existed).
     */
    public static function methodFor($fiscalYear): string
    {
        $method = static::where('fiscal_year', $fiscalYear)->value('method');
        return $method === self::METHOD_SCORE ? self::METHOD_SCORE : self::METHOD_QUESTIONS;
    }
}
