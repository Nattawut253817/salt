<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reduced_sodium_menus', function (Blueprint $table) {
            $table->string('agency')->nullable()->after('sodium_after')->comment('ชื่อหน่วยงาน');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reduced_sodium_menus', function (Blueprint $table) {
            $table->dropColumn('agency');
        });
    }
};
