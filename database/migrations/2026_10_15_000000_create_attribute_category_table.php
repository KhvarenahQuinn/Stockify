<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which attributes apply to which categories.
 *
 * Without this, attributes are global master data and the product form has to
 * offer every attribute regardless of what is being catalogued. The pivot lets
 * a category declare its specifications (packaging does not need "Storage
 * Temperature"; cold chain does) and lets the product form narrow the picker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['attribute_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_category');
    }
};