<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which saved operating-area pins (kidney_operating_area_pins) an account has
 * chosen to use in the พชอ.ไต assessment form ("เลือกพื้นที่ดำเนินงาน" ->
 * "เลือกพื้นที่นี้"). The assessment form's "พื้นที่ดำเนินงาน" dropdown lists
 * only these (plus areas already reported on), not every pin in scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kidney_operating_area_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pin_id');
            $table->timestamps();

            $table->unique(['user_id', 'pin_id'], 'kidney_oa_selections_user_pin_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kidney_operating_area_selections');
    }
};
