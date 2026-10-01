<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One row per fiscal year that has explicitly chosen HOW "ตระหนักรู้/
     * ผ่านเกณฑ์" is decided for its rows - see App\Models\AwarenessPassSetting
     * and App\Services\AwarenessPassResolver, which every place that reads
     * this concept (the home dashboard map, the /awareness report's pass/
     * fail charts, and the admin upload list's badge) now goes through
     * instead of always assuming the older "2 specific questions" method.
     *
     * A fiscal year with no row here defaults to 'questions' (the original
     * behavior every already-configured year keeps using unless an admin
     * explicitly switches it from "ตั้งค่าเกณฑ์ความตระหนักรู้").
     */
    public function up(): void
    {
        Schema::create('awareness_pass_settings', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year')->unique();
            $table->string('method')->default('questions'); // 'questions' | 'score'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awareness_pass_settings');
    }
};
