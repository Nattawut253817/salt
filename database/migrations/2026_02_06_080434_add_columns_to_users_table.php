<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('prefix')->nullable()->after('id');
            $table->string('User_firstname')->nullable()->after('prefix');
            $table->string('User_lastname')->nullable()->after('User_firstname');
            $table->string('User_position')->nullable()->after('User_lastname');
            $table->string('User_rank_id')->nullable()->after('User_position');
            $table->string('Province_id')->nullable()->after('User_rank_id');
            $table->string('District_id')->nullable()->after('Province_id');
            $table->string('Con_name')->nullable()->after('District_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
