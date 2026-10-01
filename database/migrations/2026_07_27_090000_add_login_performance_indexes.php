<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * These columns are filtered on the pages a user lands on immediately
     * after signing in (admin badge counts + the assessment dashboards),
     * but had no indexes, forcing full table scans on every request.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_approved', 'users_is_approved_index');
            $table->index('Province_id', 'users_province_id_index');
            $table->index('District_id', 'users_district_id_index');
            $table->index('hos_id', 'users_hos_id_index');
        });

        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->index('is_read', 'kidney_assessments_is_read_index');
        });

        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->index('is_read', 'salt_assessments_is_read_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_approved_index');
            $table->dropIndex('users_province_id_index');
            $table->dropIndex('users_district_id_index');
            $table->dropIndex('users_hos_id_index');
        });

        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropIndex('kidney_assessments_is_read_index');
        });

        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->dropIndex('salt_assessments_is_read_index');
        });
    }
};
