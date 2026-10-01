<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The public /awareness page filters sodium_surveys by fiscal_year,
     * province_name and district_name TOGETHER (any subset of the three,
     * always in that order), and its province/district breakdown charts
     * GROUP BY those same two columns. Until now each column only had its
     * own separate index (fiscal_year, province_name, district_name -
     * see the two earlier migrations that added them), which lets MySQL
     * use ONE of them per query rather than the exact combination actually
     * filtered on - the more filters an admin picks (year + province +
     * district), the less any single-column index actually narrows the
     * scan. A composite index on the same three columns, in the order
     * they're always applied, lets a query using any left-to-right prefix
     * of them (year alone; year+province; year+province+district) go
     * straight to the matching rows instead of scanning every row for
     * that year (or the whole table, for the "ทั้งหมด"/no-year-filter case)
     * and checking each one - which is the main reason the page and its
     * filters got slower as more fiscal years' worth of data piled up.
     *
     * created_at also gets its own index: the paginated assessment list
     * on that same page always ORDER BY created_at DESC, which for an
     * unfiltered ("ทั้งหมด") or large result set forces a full sort of
     * every matching row before MySQL can return just the first 15.
     */
    public function up(): void
    {
        Schema::table('sodium_surveys', function (Blueprint $table) {
            $table->index(['fiscal_year', 'province_name', 'district_name'], 'sodium_surveys_year_province_district_idx');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sodium_surveys', function (Blueprint $table) {
            $table->dropIndex('sodium_surveys_year_province_district_idx');
            $table->dropIndex(['created_at']);
        });
    }
};
