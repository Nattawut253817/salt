<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The public /awareness page (MainController::awareness()) filters and
     * groups by both province_name and district_name in exactly the same
     * way - but only province_name had a database index. Every query
     * grouped or filtered by district_name (the district breakdown table,
     * the per-district pass/fail computation) was doing a full table scan
     * across sodium_surveys, which is the main reason that page was slow
     * to load as the table grew. This mirrors the existing province_name
     * index onto district_name so both dimensions are equally fast.
     */
    public function up(): void
    {
        Schema::table('sodium_surveys', function (Blueprint $table) {
            $table->index('district_name');
        });
    }

    public function down(): void
    {
        Schema::table('sodium_surveys', function (Blueprint $table) {
            $table->dropIndex(['district_name']);
        });
    }
};
