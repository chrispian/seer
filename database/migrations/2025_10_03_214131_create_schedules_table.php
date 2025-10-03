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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('command_slug');
            $table->json('payload')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'failed'])->default('active');
            $table->enum('recurrence_type', ['one_off', 'daily_at', 'weekly_at', 'cron_expr'])->default('one_off');
            $table->string('recurrence_value')->nullable(); // "07:00", "MON,WED,FRI", cron expression
            $table->string('timezone')->default('UTC');
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('lock_owner')->nullable();
            $table->timestamp('last_tick_at')->nullable();
            $table->unsignedInteger('run_count')->default(0);
            $table->unsignedInteger('max_runs')->nullable(); // null = unlimited
            $table->timestamps();
            
            // Indexes for scheduler performance
            $table->index(['status', 'next_run_at']);
            $table->index(['locked_at', 'status']);
            $table->index('command_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
