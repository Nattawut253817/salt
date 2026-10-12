<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * รูปภาพประกอบของแบบรายงาน พชอ.ไต - สูงสุด 3 รูปต่อ 1 ข้อ (category_1..7, 8_1..8_3)
 * ต่อ 1 รายงาน (kidney_assessments). ไฟล์จริงเก็บใน disk "public"
 * (storage/app/public/kidney_assessments/images/...) ส่วนตารางนี้เก็บ path
 * ขนาดไฟล์ และขนาดภาพ (กว้าง x สูง) ไว้ใช้จัดวางตอนแสดงผลและตอน export.
 */
return new class extends Migration
{
    public function up(): void
    {
        // KidneyImageService::available() อาจสร้างตารางนี้ให้แล้วตอนใช้งานครั้งแรก
        if (Schema::hasTable('kidney_assessment_images')) {
            return;
        }

        Schema::create('kidney_assessment_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kidney_assessment_id');
            $table->string('category', 10);          // 1..7, 8_1, 8_2, 8_3
            $table->unsignedTinyInteger('slot');     // 1..3
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 50)->nullable();
            $table->unsignedInteger('size')->default(0);        // bytes (หลังย่อภาพ)
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->unique(['kidney_assessment_id', 'category', 'slot'], 'kidney_img_assessment_cat_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kidney_assessment_images');
    }
};
