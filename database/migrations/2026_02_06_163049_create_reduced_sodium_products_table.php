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
        Schema::create('reduced_sodium_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('product_image')->nullable();
            $table->string('product_name');
            $table->decimal('sodium_amount', 10, 2)->nullable();
            $table->string('standard_certification')->nullable();
            $table->string('manufacturer_name')->nullable();
            $table->timestamp('update_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reduced_sodium_products');
    }
};
