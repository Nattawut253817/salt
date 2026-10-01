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
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->boolean('is_read')->default(0)->after('user_id');
        });
        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->boolean('is_read')->default(0)->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropColumn('is_read');
        });
        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->dropColumn('is_read');
        });
    }
};
