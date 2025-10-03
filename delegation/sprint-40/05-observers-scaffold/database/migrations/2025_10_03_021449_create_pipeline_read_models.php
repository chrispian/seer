<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('schedule_metrics_daily', function (Blueprint $t) {
            $t->date('day');
            $t->integer('runs')->default(0);
            $t->integer('failures')->default(0);
            $t->bigInteger('duration_ms_sum')->default(0);
            $t->integer('duration_ms_count')->default(0);
            $t->primary(['day']);
        });

        Schema::create('command_runs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('slug');
            $t->string('status')->default('running'); // running|ok|failed
            $t->bigInteger('duration_ms')->nullable();
            $t->uuid('workspace_id')->nullable();
            $t->uuid('user_id')->nullable();
            $t->timestampTz('started_at')->useCurrent();
            $t->timestampTz('finished_at')->nullable();
            $t->index(['slug','started_at']);
        });

        Schema::create('command_activity', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('slug');
            $t->string('action'); // started|completed
            $t->uuid('run_id')->nullable();
            $t->uuid('workspace_id')->nullable();
            $t->uuid('user_id')->nullable();
            $t->json('payload')->nullable();
            $t->timestampTz('ts')->useCurrent();
            $t->index(['slug','ts']);
        });

        Schema::create('tool_metrics_daily', function (Blueprint $t) {
            $t->date('day');
            $t->string('tool');
            $t->integer('invocations')->default(0);
            $t->integer('errors')->default(0);
            $t->bigInteger('duration_ms_sum')->default(0);
            $t->integer('duration_ms_count')->default(0);
            $t->primary(['day','tool']);
        });

        Schema::create('tool_activity', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tool');
            $t->uuid('invocation_id')->nullable();
            $t->string('status')->default('ok');
            $t->bigInteger('duration_ms')->nullable();
            $t->string('command_slug')->nullable();
            $t->uuid('fragment_id')->nullable();
            $t->uuid('workspace_id')->nullable();
            $t->uuid('user_id')->nullable();
            $t->timestampTz('ts')->useCurrent();
            $t->index(['tool','ts']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('tool_activity');
        Schema::dropIfExists('tool_metrics_daily');
        Schema::dropIfExists('command_activity');
        Schema::dropIfExists('command_runs');
        Schema::dropIfExists('schedule_metrics_daily');
    }
};
