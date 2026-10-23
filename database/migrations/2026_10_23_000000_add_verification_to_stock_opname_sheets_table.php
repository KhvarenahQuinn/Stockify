<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A submitted count is only a proposal. The official balance changes when a
 * manager or admin verifies it, so the verifier and the moment are part of the
 * audit record rather than being inferred from the status alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opname_sheets', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->after('auditor_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_sheets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');
        });
    }
};
