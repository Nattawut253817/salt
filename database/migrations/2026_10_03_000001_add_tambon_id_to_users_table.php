<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambon_id was already accepted by AuthController::register()'s validator
 * (added earlier alongside Hospital_id/Subdistrict_Hospital_id) but the
 * users table never actually had a column for it, so the value was
 * silently dropped on every registration. This adds the column - same
 * nullable string convention as Province_id/District_id/hos_id above it -
 * so it can finally be saved.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('Tambon_id')->nullable()->after('District_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('Tambon_id');
        });
    }
};
