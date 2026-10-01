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
        Schema::create('awareness_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year')->nullable(); // From dropdown

            // Excel Columns
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
            $table->string('is_aware_health')->nullable();
            $table->string('is_know_limit')->nullable();
            $table->string('freq_instant_food')->nullable();
            $table->string('freq_frozen_food')->nullable();
            $table->string('freq_pickled_food')->nullable();
            $table->string('freq_home_cooked')->nullable();
            $table->string('freq_outside_food')->nullable();
            $table->string('add_seasoning_cook')->nullable();
            $table->string('add_sauce_table')->nullable();
            $table->string('freq_high_sodium')->nullable();
            $table->string('order_no_msg')->nullable();
            $table->string('importance_level')->nullable();
            $table->string('effort_level')->nullable();
            $table->string('knowledge_level')->nullable();
            $table->date('update_date')->nullable(); // 'update' column from Excel

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awareness_assessments');
    }
};
