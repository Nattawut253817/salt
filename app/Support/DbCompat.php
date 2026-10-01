<?php

namespace App\Support;

/**
 * Small, pure (no DB connection needed) helpers for writing raw SQL
 * fragments that behave correctly on every database driver this app
 * runs under - MySQL locally via MAMP, PostgreSQL in production on
 * render.com (config/database.php's 'default' falls back to 'pgsql'
 * whenever DB_CONNECTION isn't set, while the local .env pins 'mysql').
 *
 * Hardcoding one engine's syntax into a raw SQL fragment (a Postgres
 * `::text` cast, MySQL's `YEAR()` function, etc.) silently breaks on
 * the other engine - either with a hard SQL syntax error, or, worse,
 * a join/filter that just stops matching rows and quietly drops data.
 * These helpers centralize the per-driver branching in one tested
 * place instead of scattering engine assumptions across controllers.
 */
class DbCompat
{
    /**
     * Build a `{$table}.{$column}` SQL expression safe to compare
     * against another column of a different type (e.g. a VARCHAR
     * against an INTEGER), for the given database driver.
     *
     * users.Province_id is a string column while provinces.province_id
     * is an integer primary key (see the migrations), so joining them
     * needs a cast on PostgreSQL - Postgres has no implicit
     * varchar<->integer comparison and raises "operator does not
     * exist" without one. MySQL/MariaDB/SQLite coerce the comparison
     * implicitly, so no cast is needed (and none is emitted) there.
     *
     * On PostgreSQL the column name is also wrapped in double quotes
     * to preserve the exact mixed case Laravel's schema builder
     * created it with ("Province_id") - Postgres folds an unquoted
     * identifier to lowercase, which would not match the real column.
     */
    public static function castTextExpr(string $driver, string $table, string $column): string
    {
        return match ($driver) {
            'pgsql' => "{$table}.\"{$column}\"::text",
            'sqlsrv' => "CAST({$table}.[{$column}] AS NVARCHAR(50))",
            default => "{$table}.{$column}",
        };
    }

    /**
     * Build a SQL expression that extracts the calendar year out of a
     * date/datetime column, for the given database driver. MySQL's
     * `YEAR(col)` function does not exist on PostgreSQL (which needs
     * `EXTRACT(YEAR FROM col)`) or SQLite (which needs `strftime`).
     */
    public static function yearExpr(string $driver, string $column): string
    {
        return match ($driver) {
            'pgsql' => "EXTRACT(YEAR FROM {$column})",
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER)",
            default => "YEAR({$column})",
        };
    }
}
