<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // MySQL auto-generates index names by joining every column name, and
    // "kidney_assessments_user_id_fiscal_year_quarter_operating_area_unique"
    // (Laravel's default for this column set) is over MySQL's 64-character
    // identifier limit, so a short explicit name is required here.
    private const INDEX_NAME = 'kidney_assessments_uid_fy_q_area_unique';

    /**
     * Run the migrations.
     *
     * A district can now run more than one operating area (e.g. several
     * รพ.สต. within the same อำเภอ) side by side within the same
     * fiscal_year + quarter, each needing its own saved row instead of
     * overwriting a single shared one. Widen the natural key from
     * (user_id, fiscal_year, quarter) to also include operating_area so
     * different areas no longer collide. MySQL treats every NULL as
     * distinct within a unique index, so legacy rows (operating_area IS
     * NULL, from before this feature existed) stay safely non-colliding
     * with each other too - no data migration needed.
     *
     * Each step below is defensive because MySQL's DDL statements commit
     * immediately and are NOT rolled back together as one transaction: if
     * an earlier run of this migration failed partway through (as happened
     * here, on the identifier-length error above), the old unique index
     * may already be gone even though the migration itself was not marked
     * as run. Re-running it must not error out just because part of the
     * work was already done.
     */
    public function up(): void
    {
        if ($this->indexExists('kidney_assessments', 'kidney_assessments_user_id_fiscal_year_quarter_unique')) {
            Schema::table('kidney_assessments', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'fiscal_year', 'quarter']);
            });
        }

        if (!$this->indexExists('kidney_assessments', self::INDEX_NAME)) {
            Schema::table('kidney_assessments', function (Blueprint $table) {
                $table->unique(['user_id', 'fiscal_year', 'quarter', 'operating_area'], self::INDEX_NAME);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('kidney_assessments', self::INDEX_NAME)) {
            Schema::table('kidney_assessments', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }

        if (!$this->indexExists('kidney_assessments', 'kidney_assessments_user_id_fiscal_year_quarter_unique')) {
            Schema::table('kidney_assessments', function (Blueprint $table) {
                $table->unique(['user_id', 'fiscal_year', 'quarter']);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(1) AS cnt FROM information_schema.STATISTICS
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, $table, $indexName]
        );

        return (int) ($result[0]->cnt ?? 0) > 0;
    }
};
