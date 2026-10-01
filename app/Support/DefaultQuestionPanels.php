<?php

namespace App\Support;

/**
 * Default "which of the 4 /awareness dashboard panels does this question
 * belong to" - keyed by the exact question wording used in the pre-FY69
 * Excel template and the FY69 template, matching the panel layout the app
 * used before this became admin-configurable (see
 * SurveyYearMapping::dashboard_panel).
 *
 * This is only ever a *starting point* for data that already exists: a
 * backfill for rows imported before dashboard_panel existed
 * (php artisan sodium-surveys:seed-default-panels), and for
 * sodium-surveys:migrate-legacy when it seeds mappings for the old
 * awareness_assessments / awareness_assessments_fy69 tables. Both only set
 * dashboard_panel when it isn't already set, so anything an admin has
 * since chosen from "การประเมินความตระหนักรู้ > ตั้งค่าคำถามและเกณฑ์การประเมิน"
 * is never overwritten. A brand new year's own wording won't match
 * anything here - that's expected, it just means the admin picks its
 * panels from scratch on that screen.
 */
class DefaultQuestionPanels
{
    // question_label text (pre-FY69 templates) => dashboard_panel (1-4), or
    // null for a question that was never charted in a panel before (the two
    // criteria questions themselves).
    const LEGACY = [
        'การบริโภคเกลือในปริมาณมากทำให้เกิดปัญหาสุขภาพได้ ใช่หรือไม่' => null, // is_aware_health - criteria only
        'คนทั่วไปไม่ควรบริโภคโซเดียมเกิน 2,000 มิลลิกรัมต่อวัน ใช่หรือไม่' => null, // is_know_limit - criteria only
        'รับประทานอาหารสำเร็จรูป หรือกึ่งสำเร็จรูป' => 1,
        'อาหารแช่แข็งในร้านสะดวกซื้อ' => 1,
        'อาหารหมักดอง หรือ แช่น้ำเกลือ' => 1,
        'อาหารปรุงเองที่บ้าน' => 3,
        'อาหารสั่งหรือซื้อจากนอกบ้าน' => 2,
        'มีการเติมเกลือ น้ำปลา ซอสถั่วเหลือง น้ำมันหอย ในระหว่างการปรุงอาหาร' => 2,
        'มีการเติมน้ำปลาหรือซีอิ๊วบนโต๊ะอาหารอีกครั้งก่อนรับประทาน' => 2,
        'มีการบริโภคอาหารประเภทที่มีเกลือในปริมาณสูงมาก' => 1,
        'คุณสั่งอาหารไม่เติมน้ำปลา หรือ ผงชูรส บ่อยแค่ไหน' => 3,
        'คุณตระหนักว่าการจำกัดการบริโภคโซเดียมและเกลือ มีความสำคัญมากเท่าใด' => 4,
        'ในแต่ละวันคุณได้พยายามจำกัดหรือลดปริมาณการบริโภคเกลือและโซเดียม' => 4,
        'คุณทราบปริมาณโซเดียมหรือเกลือในอาหารที่คุณรับประทานในแต่ละวัน' => 4,
    ];

    // question_label text (FY69 template) => dashboard_panel (1-4). Fields
    // never charted before (support_law, support_tax, social_*, heard_media,
    // nearby_restaurants_have_menu) are intentionally left out.
    const FY69 = [
        'ปรุงอาหารทานเองมีการเติมเครื่องปรุงรสเค็ม' => 2,
        'ซื้อแกงถุง/อาหารตามสั่ง เติมเครื่องปรุงเพิ่ม' => 2,
        'บะหมี่กึ่งสำเร็จรูป/อาหารสำเร็จรูปแบบกล่อง/อาหารขยะ/ขนมกรุบกรอบ' => 1,
        'อาหารแปรรูป/หมักดอง (เช่น ไส้กรอก แหนม หมูยอ ผักดอง)' => 1,
        'ตระหนักว่าทานเค็มมากไป ส่งผลเสียต่อสุขภาพ' => 4,
        'ทราบว่าไม่ควรทานเกลือเกิน 1 ช้อนชา/วัน' => 4,
        'คิดว่าปริมาณความเค็มที่ทานปัจจุบันอยู่ในระดับใด' => 4,
        'ความสำคัญของการลดโซเดียม' => 4,
        'ความพยายามในการลดโซเดียม' => 4,
        'ความมั่นใจในการลดโซเดียม' => 4,
        'ลดอาหารที่มีรสเค็มจัด' => 3,
        'ลดอาหารแปรรูป/กึ่งสำเร็จรูป' => 3,
        'หลีกเลี่ยง/ลดซดน้ำแกง/น้ำซุป' => 3,
        'ลดการจิ้มน้ำจิ้ม' => 3,
        'เพิ่มผักผลไม้สด' => 3,
        'ออกกำลังกายสม่ำเสมอ' => 3,
        'ดื่มน้ำเปล่า 8 แก้ว/วัน' => 3,
        'มั่นใจว่าปรับเปลี่ยนพฤติกรรมได้' => 3,
    ];

    // The pre-FY69 template's questions were always charted under a short
    // generic label rather than their full wording (FY69's own wording was
    // already short enough to use as-is). Kept here so the panel charts
    // look the same as before for this data; a brand new year's own
    // wording just falls back to showing in full - see shortLabelFor().
    const LEGACY_SHORT_LABELS = [
        'รับประทานอาหารสำเร็จรูป หรือกึ่งสำเร็จรูป' => 'อาหารกึ่งสำเร็จรูป',
        'อาหารแช่แข็งในร้านสะดวกซื้อ' => 'อาหารแช่แข็ง',
        'อาหารหมักดอง หรือ แช่น้ำเกลือ' => 'อาหารหมักดอง',
        'มีการบริโภคอาหารประเภทที่มีเกลือในปริมาณสูงมาก' => 'ทานอาหารโซเดียมสูง',
        'อาหารปรุงเองที่บ้าน' => 'ทำอาหารทานเอง',
        'อาหารสั่งหรือซื้อจากนอกบ้าน' => 'ทานอาหารนอกบ้าน',
        'มีการเติมเกลือ น้ำปลา ซอสถั่วเหลือง น้ำมันหอย ในระหว่างการปรุงอาหาร' => 'เติมเครื่องปรุงขณะทำ',
        'มีการเติมน้ำปลาหรือซีอิ๊วบนโต๊ะอาหารอีกครั้งก่อนรับประทาน' => 'เติมเครื่องปรุงบนโต๊ะ',
        'คุณสั่งอาหารไม่เติมน้ำปลา หรือ ผงชูรส บ่อยแค่ไหน' => 'สั่งไม่ใส่ผงชูรส',
        'คุณตระหนักว่าการจำกัดการบริโภคโซเดียมและเกลือ มีความสำคัญมากเท่าใด' => 'ระดับความสำคัญ',
        'ในแต่ละวันคุณได้พยายามจำกัดหรือลดปริมาณการบริโภคเกลือและโซเดียม' => 'ระดับความพยายาม',
        'คุณทราบปริมาณโซเดียมหรือเกลือในอาหารที่คุณรับประทานในแต่ละวัน' => 'ระดับความรู้',
    ];

    public static function panelFor(string $label): ?int
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }
        if (array_key_exists($label, self::LEGACY)) {
            return self::LEGACY[$label];
        }
        if (array_key_exists($label, self::FY69)) {
            return self::FY69[$label];
        }
        return null;
    }

    // The short label to chart a question's answers under, or the label
    // itself if there's no known shortening for it (a brand new year's own
    // wording, or FY69's - already short).
    public static function shortLabelFor(string $label): string
    {
        $trimmed = trim($label);
        if (isset(self::LEGACY_SHORT_LABELS[$trimmed])) {
            return self::LEGACY_SHORT_LABELS[$trimmed];
        }

        return self::stripLeadingNumber($trimmed);
    }

    // Excel headers often number each question ("2.1 ...", "3.2) ...",
    // "4. ...") for the person filling in the form - useful there, but not
    // meant to be part of the question's own wording, so the /awareness
    // charts show just the question text (matches every LEGACY_SHORT_LABELS
    // entry above and FY69's own wording, neither of which carries a
    // number). Falls back to the original label if stripping would leave
    // nothing (a label that's only a number, or doesn't match the pattern
    // at all).
    private static function stripLeadingNumber(string $label): string
    {
        $stripped = preg_replace('/^\s*\d+(?:\.\d+)*[\.\)]?\s+/u', '', $label);

        return $stripped !== null && trim($stripped) !== '' ? $stripped : $label;
    }
}
