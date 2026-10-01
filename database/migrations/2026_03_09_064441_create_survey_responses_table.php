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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('survey_templates')->onDelete('cascade');
            $table->string('fiscal_year');
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
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
