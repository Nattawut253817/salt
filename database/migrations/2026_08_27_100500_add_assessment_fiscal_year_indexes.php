<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * fiscal_year (and quarter, on kidney_assessments) are filtered on
     * every load of the salt/kidney admin list pages and in the sibling
     * lookups used for carry-forward backfill, but had no index - only
     * user_id was indexed (via the foreign key). Composite indexes here
     * let the whereIn(user_id) + where(fiscal_year) lookups resolve
     * without a secondary table scan.
     */
    public function up(): void
    {
        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->index(['user_id', 'fiscal_year'], 'salt_assessments_user_id_fiscal_year_index');
        });

        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->index(['user_id', 'fiscal_year'], 'kidney_assessments_user_id_fiscal_year_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salt_assessments', function (Blueprint $table) {
            $table->dropIndex('salt_assessments_user_id_fiscal_year_index');
        });

        Schema::table('kidney_assessments', function (Blueprint $table) {
            $table->dropIndex('kidney_assessments_user_id_fiscal_year_index');
        });
    }
};
