<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ยุบกรอบ /awareness จาก 4 เหลือ 3: คำถามที่เคยอยู่กรอบ 3 (พฤติกรรมสุขภาพและการสั่งอาหาร)
 * ย้ายไปกรอบ 2 (การปรุง การสั่งอาหาร และการทานนอกบ้าน) ยกเว้น "มั่นใจว่าปรับเปลี่ยนพฤติกรรมได้"
 * ซึ่งเป็นทัศนคติ จึงย้ายไปกรอบ 4 (ความตระหนักรู้ ทัศนคติ และความพยายาม)
 * เลขกรอบ 4 ไม่ถูกเลื่อน ข้อมูลกรอบอื่นไม่ถูกแตะ
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('survey_year_mappings')
            ->where('dashboard_panel', 3)
            ->where('question_label', 'like', '%มั่นใจว่าปรับเปลี่ยนพฤติกรรมได้%')
            ->update(['dashboard_panel' => 4]);

        DB::table('survey_year_mappings')
            ->where('dashboard_panel', 3)
            ->update(['dashboard_panel' => 2]);
    }

    public function down(): void
    {
        // ย้อนกลับไม่ได้ว่าข้อไหนเคยอยู่กรอบ 3 - ไม่ทำอะไร
    }
};
