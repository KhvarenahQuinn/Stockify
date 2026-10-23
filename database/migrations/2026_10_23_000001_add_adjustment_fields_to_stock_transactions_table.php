<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual corrections are still movements, so they belong in the same immutable
 * ledger as receipts and issues instead of writing products.current_stock
 * directly.
 *
 * `type` stays IN or OUT: the direction of the correction is the direction of
 * the stock movement. These two columns identify why the row exists and keep
 * adjustment rows distinguishable from supplier receipts and customer issues.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->boolean('is_adjustment')->default(false)->after('type');
            $table->string('adjustment_reason', 500)->nullable()->after('is_adjustment');

            $table->index(['is_adjustment', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['is_adjustment', 'created_at']);
            $table->dropColumn(['is_adjustment', 'adjustment_reason']);
        });
    }
};
