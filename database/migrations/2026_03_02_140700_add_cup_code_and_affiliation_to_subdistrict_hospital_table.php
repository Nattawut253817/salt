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
    Schema::table('subdistrict_hospitals', function (Blueprint $table) { // เติม s ตรงนี้
        $table->string('cup_code')->nullable();
        $table->string('affiliation')->nullable();
    });
}

public function down(): void
{
    Schema::table('subdistrict_hospitals', function (Blueprint $table) { // เติม s ตรงนี้
        $table->dropColumn(['cup_code', 'affiliation']);
    });
}
};