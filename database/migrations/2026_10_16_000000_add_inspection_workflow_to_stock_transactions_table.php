<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FE-005: the movement form collects more than product + quantity, and the
     * workflow gains a physical-inspection pass between submission and the
     * manager's decision.
     *
     * The inspection columns are deliberately nullable rather than a second enum
     * value: `status` stays the single source of truth for the decision
     * (PENDING / APPROVED / REJECTED / COMPLETED) and the inspection simply
     * refines what PENDING means. That keeps every existing row valid and stops
     * an enum change from invalidating a half-finished approval.
     */
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            // Submission detail.
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destination')->nullable();
            $table->string('reference_note', 120)->nullable();
            $table->string('document_path')->nullable();

            // Physical inspection, recorded by warehouse staff.
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->unsignedInteger('inspected_quantity')->nullable();
            $table->text('inspection_note')->nullable();

            // The queue screens filter on status + inspection state together.
            $table->index(['status', 'inspected_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'inspected_at']);
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('inspected_by');

            $table->dropColumn([
                'destination',
                'reference_note',
                'document_path',
                'inspected_at',
                'inspected_quantity',
                'inspection_note',
            ]);
        });
    }
};