<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Lets a Level 1 (User_rank_id == 1, สคร. เขต 10) admin mark one
     * submitted quarterly record - which is already scoped to exactly one
     * (agency, fiscal_year, quarter) by each table's unique constraint - as
     * "confirmed/verified". confirmed_at/confirmed_by double as the
     * "is this record confirmed" flag (null = not confirmed), so no
     * separate boolean column is needed, mirroring how is_read already
     * works on both tables.
     */
    public function up(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('is_read');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')
                ->constrained('users')->nullOnDelete();
        });
        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('is_read');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_at', 'confirmed_by']);
        });
        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_at', 'confirmed_by']);
        });
    }
};
