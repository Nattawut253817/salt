<?php

namespace Tests\Unit\Support;

use App\Support\DbCompat;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for the driver-portability bug reported against
 * MainController::kidneyDHBReport()'s "Reduced Sodium Product Count by
 * Province" query: it joined users.Province_id (a VARCHAR column) to
 * provinces.province_id (an INTEGER column) via a raw, hardcoded
 * Postgres-only `::text` cast + double-quoted column name. That syntax
 * is invalid under MySQL/MariaDB (the driver used locally via MAMP,
 * per .env's DB_CONNECTION=mysql) and either errors or silently drops
 * rows depending on the engine actually running - which is exactly
 * what "ข้อมูลมาไม่หมด" (incomplete data) on render.com looked like.
 *
 * These are pure string-building assertions - no database connection
 * or Laravel application bootstrap is needed to exercise them.
 */
class DbCompatTest extends TestCase
{
    public function test_cast_text_expr_on_postgres_quotes_the_column_and_casts_to_text(): void
    {
        // Postgres is strict about comparing a varchar to an integer
        // ("operator does not exist") and folds unquoted identifiers
        // to lowercase, which would miss the real "Province_id" column
        // Laravel's schema builder created with that exact mixed case.
        $this->assertSame(
            'users."Province_id"::text',
            DbCompat::castTextExpr('pgsql', 'users', 'Province_id')
        );
        $this->assertSame(
            'provinces."province_id"::text',
            DbCompat::castTextExpr('pgsql', 'provinces', 'province_id')
        );
    }

    public function test_cast_text_expr_on_mysql_family_needs_no_cast(): void
    {
        // MySQL/MariaDB coerce int<->string implicitly in a comparison
        // and column names are case-insensitive, so no cast or quoting
        // is needed - and none should be emitted, since `::text` is a
        // hard SQL syntax error under these engines.
        foreach (['mysql', 'mariadb', 'sqlite'] as $driver) {
            $this->assertSame(
                'users.Province_id',
                DbCompat::castTextExpr($driver, 'users', 'Province_id'),
                "driver={$driver}"
            );
        }
    }

    public function test_year_expr_matches_each_driver_syntax(): void
    {
        $this->assertSame('YEAR(update_date)', DbCompat::yearExpr('mysql', 'update_date'));
        $this->assertSame('EXTRACT(YEAR FROM update_date)', DbCompat::yearExpr('pgsql', 'update_date'));
        $this->assertSame("CAST(strftime('%Y', update_date) AS INTEGER)", DbCompat::yearExpr('sqlite', 'update_date'));
    }
}
