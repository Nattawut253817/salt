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
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->json('reporter_metadata')->nullable()->after('recommendations_opportunities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropColumn('reporter_metadata');
        });
    }
};
