<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds ปริมาณโซเดียมก่อนปรับสูตร (sodium amount BEFORE reformulation) as its
 * own column, alongside the existing sodium_amount column which has always
 * meant "after" (see the "ปริมาณโซเดียมหลังปรับสูตร (มก.)" label used
 * throughout the admin UI for it). Placed right after product_type so the
 * before/after pair sits together in the same order used in the manual
 * entry form and the "นำเข้า Excel" import template. Nullable, like
 * sodium_amount, so existing rows are unaffected.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->decimal('sodium_amount_before', 10, 2)->nullable()->after('product_type');
        });
    }

    public function down(): void
    {
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->dropColumn('sodium_amount_before');
        });
    }
};
