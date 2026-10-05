<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->decimal('buy_price', 15, 2);
            $table->decimal('sell_price', 15, 2);
            $table->unsignedInteger('min_stock');
            $table->unsignedInteger('current_stock')->default(0);
            $table->string('unit', 20);
            $table->string('image', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
