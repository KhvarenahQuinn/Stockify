<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A stock opname sheet is one physical audit session: a single reference, one
 * auditor, and many counted lines.
 *
 * The pre-existing stock_opnames table records one product per row and stays
 * exactly as it is - the single-line count flow is still live. This pair of
 * tables adds the multi-line audit sheet on top of it rather than reshaping
 * it, so nothing that reads stock_opnames changes behaviour.
 *
 * Totals (items, matched, deficit, surplus, net variance) are deliberately NOT
 * stored. They are derived from the items on read, which means a sheet can
 * never disagree with its own lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('auditor_id')->constrained('users');
            $table->date('audit_date');
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'ADJUSTED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('audit_date');
            $table->index('status');
        });

        Schema::create('stock_opname_sheet_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_sheet_id')
                ->constrained('stock_opname_sheets')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();

            // system_stock is the snapshot taken when the line was counted, so a
            // later stock movement cannot rewrite history. variance is derived
            // server-side from these two and is never trusted from the client.
            $table->unsignedInteger('system_stock');
            $table->unsignedInteger('physical_stock');
            $table->integer('variance');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            // Counting the same product twice on one sheet is always a mistake.
            $table->unique(['stock_opname_sheet_id', 'product_id'], 'opname_sheet_items_unique_product');
            $table->index('stock_opname_sheet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_sheet_items');
        Schema::dropIfExists('stock_opname_sheets');
    }
};