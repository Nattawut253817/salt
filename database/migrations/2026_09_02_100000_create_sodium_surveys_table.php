<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * One table for every fiscal year's awareness/sodium survey, instead of
     * a brand new table + model + import class each time that year's form
     * changes (which is what awareness_assessments / awareness_assessments_fy69
     * required). Only the handful of demographic columns that never change
     * from year to year are real columns; every question answer - however
     * many questions that year's form has, worded however it words them -
     * is stored as JSON in survey_data. See survey_year_mappings for the
     * question_key -> label lookup used to make sense of that JSON later.
     */
    public function up(): void
    {
        // dropIfExists first: this table may already exist (e.g. created by
        // hand while this feature was being designed) with no data in it,
        // so it's safe to recreate it from scratch here.
        Schema::dropIfExists('sodium_surveys');
        Schema::create('sodium_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year');

            // Fixed columns - identical across every year's questionnaire
            $table->string('hospital_name')->nullable();
            $table->string('hcode')->nullable();
            $table->string('province_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('sub_district')->nullable();
            $table->dateTime('survey_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('age_range')->nullable();
            $table->string('education')->nullable();
            $table->string('congenital_disease')->nullable();

            // Everything that changes year to year: {"q1": "...", "q2": "...", ...}
            // in the order the questions appear in that year's Excel file.
            $table->json('survey_data')->nullable();

            $table->date('update_date')->nullable();
            $table->timestamps();

            $table->index('fiscal_year');
            $table->index('hcode');
            $table->index('province_name');
            $table->index('survey_date');
            // Speeds up the duplicate-row lookup the importer does for
            // every uploaded row (fiscal_year + hcode + survey_date).
            $table->index(['fiscal_year', 'hcode', 'survey_date'], 'sodium_surveys_dup_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sodium_surveys');
    }
};
