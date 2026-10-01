<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Lets an admin explicitly HIDE a fiscal year from a module's search
     * filter / Excel-upload dropdowns even when that year already has real
     * data - see App\Models\FiscalYear::selectableYearsFor(). A row's
     * meaning is decided entirely by this flag: hidden=false is an
     * "enable this year even with zero data yet" row (the original
     * add-year feature), hidden=true is a "hide this year even though it
     * has real data" override. Either way this never touches the
     * underlying data itself - only what shows up in a dropdown.
     */
    public function up(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->boolean('hidden')->default(false)->after('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });
    }
};
