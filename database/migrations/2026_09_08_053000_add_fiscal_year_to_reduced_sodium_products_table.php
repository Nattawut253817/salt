<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reduced_sodium_products never had an explicit fiscal year - the
 * "ปีงบประมาณ" filter on the admin listing has always been derived from
 * YEAR(update_date), the row's last-saved timestamp, rather than a real
 * stored value (unlike reduced_sodium_menus.year, an actual integer
 * column). That's fine for one-at-a-time manual entry - the year you
 * enter something is exactly the fiscal year it counts toward, so
 * update_date is 90% correct by design there - but it breaks the moment
 * a Buddhist-year column shows up as a real, user-editable field in the
 * "นำเข้า Excel" import template: the year in the file is whatever the
 * admin typed, not whenever the import happened to run.
 *
 * fiscal_year is nullable so old rows added before this migration are
 * unaffected - AdminController's year filters (see
 * scopeProductsByFiscalYear()) fall back to YEAR(update_date) for any row
 * where this is null.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->integer('fiscal_year')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->dropColumn('fiscal_year');
        });
    }
};
