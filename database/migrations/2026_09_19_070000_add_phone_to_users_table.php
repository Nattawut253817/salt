<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds เบอร์โทรศัพท์ (phone number) to the registration form on /staff and
 * the admin "แก้ไขข้อมูลผู้ใช้งาน" screens. Nullable/free-text (not unique)
 * since existing accounts have none and the field is optional at
 * registration - same treatment as User_position.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('User_lastname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
