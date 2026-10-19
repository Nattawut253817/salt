<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** ดัชนีสำหรับเรียงตาม update_date - ลด filesort ในหน้ารายการเมนู/ผลิตภัณฑ์ลดโซเดียม */
    public function up(): void
    {
        Schema::table('reduced_sodium_menus', function (Blueprint $table) {
            $table->index('update_date', 'rsm_update_date_index');
        });
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->index('update_date', 'rsp_update_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('reduced_sodium_menus', function (Blueprint $table) {
            $table->dropIndex('rsm_update_date_index');
        });
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->dropIndex('rsp_update_date_index');
        });
    }
};
