<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Admin-managed list of which fiscal years are selectable (for search
     * filters and Excel-upload dropdowns) per module - see
     * App\Models\FiscalYear::MODULES for the fixed list of module keys.
     *
     * This is intentionally separate from every module's own data table: a
     * year shows up here the moment an admin adds it via "จัดการปีงบประมาณ",
     * even before any real data exists for it yet, and every module's own
     * "distinct fiscal years actually in the data" query still applies on
     * top of this (see FiscalYear::yearsFor()) so a year that already has
     * real data always stays selectable even if its row here is removed.
     */
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('module', 40);
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['module', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};
