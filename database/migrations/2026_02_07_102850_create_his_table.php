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
        Schema::create('his', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->comment('ปีงบประมาณ');
            $table->integer('Province_id');
            $table->string('District_name');
            $table->integer('target_b')->comment('ค่า B (ประชากร)');
            $table->integer('total_a')->comment('ค่า A (รวมทั้งหมด)');
            $table->decimal('rate_per_100k', 10, 2)->comment('อัตราต่อแสน');

            // Monthly data (Oct - Sep)
            $table->integer('m10_oct')->nullable();
            $table->integer('m11_nov')->nullable();
            $table->integer('m12_dec')->nullable();
            $table->integer('m01_jan')->nullable();
            $table->integer('m02_feb')->nullable();
            $table->integer('m03_mar')->nullable();
            $table->integer('m04_apr')->nullable();
            $table->integer('m05_may')->nullable();
            $table->integer('m06_jun')->nullable();
            $table->integer('m07_jul')->nullable();
            $table->integer('m08_aug')->nullable();
            $table->integer('m09_sep')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('his');
    }
};
