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
        // Schedule metrics - daily aggregations
        Schema::create('schedule_metrics_daily', function (Blueprint $table) {
            $table->date('day');
            $table->integer('runs')->default(0);
            $table->integer('failures')->default(0);
            $table->bigInteger('duration_ms_sum')->default(0);
            $table->integer('duration_ms_count')->default(0);
            $table->primary(['day']);
        });

        // Command execution tracking
        Schema::create('command_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug');
            $table->string('status')->default('running'); // running|ok|failed
            $table->bigInteger('duration_ms')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('finished_at')->nullable();
            $table->index(['slug', 'started_at']);
        });

        // Command activity log
        Schema::create('command_activity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug');
            $table->string('action'); // started|completed
            $table->uuid('run_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestampTz('ts')->useCurrent();
            $table->index(['slug', 'ts']);
        });

        // Tool metrics - daily aggregations
        Schema::create('tool_metrics_daily', function (Blueprint $table) {
            $table->date('day');
            $table->string('tool');
            $table->integer('invocations')->default(0);
            $table->integer('errors')->default(0);
            $table->bigInteger('duration_ms_sum')->default(0);
            $table->integer('duration_ms_count')->default(0);
            $table->primary(['day', 'tool']);
        });

        // Tool activity log
        Schema::create('tool_activity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tool');
            $table->uuid('invocation_id')->nullable();
            $table->string('status')->default('ok');
            $table->bigInteger('duration_ms')->nullable();
            $table->string('command_slug')->nullable();
            $table->unsignedBigInteger('fragment_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestampTz('ts')->useCurrent();
            $table->index(['tool', 'ts']);
        });

        // Fragment lifecycle metrics
        Schema::create('fragment_metrics_daily', function (Blueprint $table) {
            $table->date('day');
            $table->string('type');
            $table->integer('created')->default(0);
            $table->integer('updated')->default(0);
            $table->integer('deleted')->default(0);
            $table->primary(['day', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fragment_metrics_daily');
        Schema::dropIfExists('tool_activity');
        Schema::dropIfExists('tool_metrics_daily');
        Schema::dropIfExists('command_activity');
        Schema::dropIfExists('command_runs');
        Schema::dropIfExists('schedule_metrics_daily');
    }
};
