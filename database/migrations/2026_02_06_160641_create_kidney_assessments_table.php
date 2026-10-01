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
        Schema::create('kidney_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('fiscal_year');
            $table->tinyInteger('quarter');

            // Category 1-7
            $table->text('category_1')->nullable();
            $table->text('category_2')->nullable();
            $table->text('category_3')->nullable();
            $table->text('category_4')->nullable();
            $table->text('category_5')->nullable();
            $table->text('category_6')->nullable();
            $table->text('category_7')->nullable();

            // Category 8 sub-items
            $table->text('category_8_1')->nullable();
            $table->text('category_8_2')->nullable();
            $table->text('category_8_3')->nullable();

            // Main sections
            $table->text('problems_obstacles')->nullable();
            $table->text('recommendations_opportunities')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['user_id', 'fiscal_year', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kidney_assessments');
    }
};
