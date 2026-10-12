<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตัวนับจำนวนการเข้าชมของหน้าสาธารณะ แยกตามหน้า (page_key = ชื่อ route)
 * started_at = วันที่เริ่มนับของหน้านั้น ใช้แสดง "เริ่มนับ ..." ที่ท้ายเว็บ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_views')) {
            return;
        }

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 100)->unique();
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
