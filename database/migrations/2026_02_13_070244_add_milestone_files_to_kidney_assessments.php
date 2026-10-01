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
            $table->string('category_1_file')->nullable();
            $table->string('category_2_file')->nullable();
            $table->string('category_3_file')->nullable();
            $table->string('category_4_file')->nullable();
            $table->string('category_5_file')->nullable();
            $table->string('category_6_file')->nullable();
            $table->string('category_7_file')->nullable();
            $table->string('category_8_1_file')->nullable();
            $table->string('category_8_2_file')->nullable();
            $table->string('category_8_3_file')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'category_1_file',
                'category_2_file',
                'category_3_file',
                'category_4_file',
                'category_5_file',
                'category_6_file',
                'category_7_file',
                'category_8_1_file',
                'category_8_2_file',
                'category_8_3_file'
            ]);
        });
    }
};
