<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * FY69's Excel form split the old single "อายุ" column into several
     * new demographic detail columns that no earlier year's file has:
     * birth date, a "don't know birth date" flag, age in months, income,
     * a free-text "other education", a "no congenital disease" flag, and
     * free-text "other congenital disease". SodiumSurveyImport already
     * preserves these for every year via survey_data (JSON) even without
     * this migration - this just promotes them to real, directly
     * queryable columns (nullable, since no year before FY69 ever
     * collected them) instead of leaving them buried in that JSON.
     *
     * See SodiumSurveyImport::OPTIONAL_FIELD_LABELS for the header-text
     * match that fills these in; a year whose file doesn't have one of
     * these columns at all simply leaves it null.
     */
    public function up(): void
    {
        Schema::table('sodium_surveys', function (Blueprint $table) {
            $table->dateTime('birth_date')->nullable()->after('age_range');
            $table->string('birth_date_unknown')->nullable()->after('birth_date');
            $table->string('age_months')->nullable()->after('birth_date_unknown');
            $table->string('income')->nullable()->after('age_months');
            $table->string('education_other')->nullable()->after('education');
            $table->string('no_congenital_disease')->nullable()->after('congenital_disease');
            $table->string('congenital_disease_other')->nullable()->after('no_congenital_disease');
        });
    }

    public function down(): void
    {
        Schema::table('sodium_surveys', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'birth_date_unknown',
                'age_months',
                'income',
                'education_other',
                'no_congenital_disease',
                'congenital_disease_other',
            ]);
        });
    }
};
