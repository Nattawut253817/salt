<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('awareness_assessments_fy69');
        Schema::create('awareness_assessments_fy69', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year')->default('2569');
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

            // 1.1 - 1.4
            $table->string('add_seasoning_cook')->nullable();
            $table->string('add_sauce_table')->nullable();
            $table->string('freq_instant_food')->nullable();
            $table->string('freq_processed_food')->nullable();

            // 2.1 - 2.3
            $table->string('is_aware_health')->nullable();
            $table->string('is_know_limit')->nullable();
            $table->string('is_appropriate_intake')->nullable();

            // 3.1 - 3.3
            $table->string('importance_level')->nullable();
            $table->string('effort_level')->nullable();
            $table->string('knowledge_level')->nullable();

            // 3.2 - 3.16 (Behavioral & Social)
            $table->string('behavioral_reduce_salty')->nullable();
            $table->string('behavioral_reduce_processed')->nullable();
            $table->string('behavioral_reduce_soup')->nullable();
            $table->string('behavioral_reduce_dipping')->nullable();
            $table->string('behavioral_increase_veg')->nullable();
            $table->string('behavioral_exercise')->nullable();
            $table->string('behavioral_drink_water')->nullable();
            $table->string('behavioral_confidence_change')->nullable();
            $table->string('support_law')->nullable();
            $table->string('support_tax')->nullable();
            $table->string('social_adjust_if_relative_sick')->nullable();
            $table->string('social_relative_likes_salty')->nullable();
            $table->string('social_relative_recommends')->nullable();
            $table->string('heard_media')->nullable();
            $table->string('nearby_restaurants_have_menu')->nullable();

            $table->date('update_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awareness_assessments_fy69');
    }
};
