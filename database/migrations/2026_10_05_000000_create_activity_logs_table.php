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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Nullable so system-initiated actions can be logged without a user,
            // matching ActivityLoggerService::log()'s User|int|null contract.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->text('description');
            $table->string('ip_address', 45);
            $table->timestamps();
            // ReportService filters/sorts on these columns; without them the
            // activity report and the DISTINCT action dropdown scan the table.
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
