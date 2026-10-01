<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * One row per (fiscal_year, question_key). Populated automatically by
     * SodiumSurveyImport from each uploaded file's header row - uploading a
     * brand new year's form is what defines its questions here, no code
     * change required. semantic_key is optional and only set for the
     * handful of questions reports need to find across years despite the
     * wording/column position changing (e.g. "is_aware_health").
     */
    public function up(): void
    {
        Schema::dropIfExists('survey_year_mappings');
        Schema::create('survey_year_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year');
            // 'q1', 'q2', ... - the key used inside sodium_surveys.survey_data
            $table->string('question_key');
            // The literal question text/header from that year's Excel file
            $table->text('question_label')->nullable();
            // Optional canonical name so reports can resolve "the awareness
            // question" (etc.) for whichever fiscal year they're looking at,
            // even though its wording and column position differ by year.
            $table->string('semantic_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['fiscal_year', 'question_key']);
            $table->index('semantic_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_year_mappings');
    }
};
