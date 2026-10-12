<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * บันทึกการใช้งานระบบ (audit log): เข้า/ออกจากระบบ, OTP, รีเซ็ตรหัสผ่าน,
 * และการแก้ไขข้อมูลสำคัญของผู้ดูแล - เก็บย้อนหลัง 1 ปีแล้วลบอัตโนมัติ
 * (ดู App\Support\ActivityLog). ไม่เก็บรหัสผ่าน / OTP / เนื้อหาที่กรอก
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('actor_name', 255)->nullable();
            $table->string('event', 50)->index();
            $table->string('description', 500)->nullable();
            $table->text('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
