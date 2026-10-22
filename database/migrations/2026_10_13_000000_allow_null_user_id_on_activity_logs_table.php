<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


/**
 * Makes activity_logs.user_id nullable so system-initiated actions can be
 * recorded, and switches the constraint to ON DELETE SET NULL so removing a
 * user preserves their audit trail instead of blocking the delete.
 *
 * The create_activity_logs_table migration was already applied on existing
 * installations, so the column is altered here rather than only edited there.
 */
return new class extends Migration
{
    /**
     * SQLite enforces foreign keys through a connection pragma rather than a
     * named constraint, so dropForeign() has nothing to drop and throws. Only
     * engines that model constraints as droppable DDL call it.
     */
    protected function supportsNamedForeignKeys(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->supportsNamedForeignKeys()) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        if ($this->supportsNamedForeignKeys()) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // System-initiated rows cannot satisfy the original NOT NULL constraint.
        // Deleting them here would silently destroy audit history, so the
        // rollback is refused instead and the operator decides what to do with
        // the orphaned rows.
        $systemRows = DB::table('activity_logs')->whereNull('user_id')->count();

        if ($systemRows > 0) {
            throw new \RuntimeException(
                "Cannot restore the NOT NULL constraint on activity_logs.user_id: "
                ."{$systemRows} system-initiated row(s) have no user. Reassign or "
                .'delete them before rolling back this migration.'
            );
        }

        if ($this->supportsNamedForeignKeys()) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};