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
        Schema::create('reduced_sodium_menus', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('province');
            $table->string('district');
            $table->string('org_type');
            $table->string('org_name');
            $table->string('kitchen_type');
            $table->string('menu_name');
            $table->decimal('sodium_before', 10, 2)->nullable();
            $table->decimal('sodium_after', 10, 2)->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('product_image')->nullable();
            $table->timestamp('update_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reduced_sodium_menus');
    }
};
