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
        Schema::create('salt_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('fiscal_year'); // ปีงบประมาณ
            $table->tinyInteger('quarter'); // ไตรมาส: 3, 6, 9, 12

            // Ans 1-4
            $table->text('ans_1_detail')->nullable();
            $table->string('ans_1_file')->nullable();
            $table->text('ans_2_detail')->nullable();
            $table->string('ans_2_file')->nullable();
            $table->text('ans_3_detail')->nullable();
            $table->string('ans_3_file')->nullable();
            $table->text('ans_4_detail')->nullable();
            $table->string('ans_4_file')->nullable();

            // Ans 5.1-5.5
            $table->text('ans_5_1_detail')->nullable();
            $table->string('ans_5_1_file')->nullable();
            $table->text('ans_5_2_detail')->nullable();
            $table->string('ans_5_2_file')->nullable();
            $table->text('ans_5_3_detail')->nullable();
            $table->string('ans_5_3_file')->nullable();
            $table->text('ans_5_4_detail')->nullable();
            $table->string('ans_5_4_file')->nullable();
            $table->text('ans_5_5_detail')->nullable();
            $table->string('ans_5_5_file')->nullable();

            // Footer sections (no file upload)
            $table->text('problems')->nullable();
            $table->text('suggestions')->nullable();

            $table->timestamps();

            // Unique constraint: one entry per user per year per quarter
            $table->unique(['user_id', 'fiscal_year', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salt_assessments');
    }
};
