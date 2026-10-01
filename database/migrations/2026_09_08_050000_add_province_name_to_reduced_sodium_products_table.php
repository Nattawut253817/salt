<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reduced_sodium_products has never had its own province column - the
 * admin listing/export/delete filters all resolve a product's province by
 * joining through user_id -> users.Province_id (see AdminController's
 * whereHas('user.province', ...) calls), which only works because every
 * product used to be added by a province-level user account.
 *
 * The "นำเข้า Excel" bulk import (AdminController::importSodiumProducts)
 * breaks that assumption: an admin (rank 1, who isn't tied to any one
 * province) imports rows for all 5 provinces in one file, each row
 * carrying its own จังหวัด value. Those rows need somewhere to record
 * that province independently of whoever's user_id ends up on them, so
 * this adds a plain nullable province_name column - populated by the
 * importer, left null on everything added the old way - and the admin
 * controller's province filters now match EITHER this column OR the
 * legacy user->province relationship, so old and new rows both filter
 * correctly (see AdminController::scopeProductsByProvince()).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->string('province_name')->nullable()->after('manufacturer_name');
        });
    }

    public function down(): void
    {
        Schema::table('reduced_sodium_products', function (Blueprint $table) {
            $table->dropColumn('province_name');
        });
    }
};
