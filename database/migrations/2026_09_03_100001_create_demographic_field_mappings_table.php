<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One row per (fiscal_year, field_key) - an admin override of which
     * raw Excel column index actually holds that fixed demographic field
     * for that year's uploaded file, for the 3 fields whose position can
     * vary: gender, age_range, education (see
     * App\Models\DemographicFieldMapping::FIELDS). No row for a given
     * (fiscal_year, field_key) means "use the usual position" - see
     * SodiumSurveyImport::FIXED_COLUMN_COUNT / DemographicFieldMapping::columnIndexFor().
     *
     * Set from "การประเมินความตระหนักรู้ > ตั้งค่าแดชบอร์ด" - see
     * App\Http\Controllers\SurveyQuestionSettingsController::updateDemographicMapping().
     */
    public function up(): void
    {
        Schema::create('demographic_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year');
            $table->string('field_key');
            $table->unsignedInteger('column_index');
            $table->timestamps();

            $table->unique(['fiscal_year', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demographic_field_mappings');
    }
};
