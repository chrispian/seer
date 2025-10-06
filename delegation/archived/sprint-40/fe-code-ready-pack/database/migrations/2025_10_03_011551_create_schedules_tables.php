<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('user_id');
            $t->uuid('workspace_id')->nullable();
            $t->string('slug');
            $t->string('command_slug');
            $t->json('payload')->default(DB::raw('(JSON_OBJECT())'));

            $t->string('schedule_kind'); // one_off|daily_at|cron|rrule
            $t->string('tz')->default('America/Chicago');
            $t->timestampTz('run_at_local')->nullable();
            $t->string('daily_local_time')->nullable();
            $t->string('cron_expr')->nullable();
            $t->text('rrule')->nullable();

            $t->timestampTz('next_run_at')->nullable();
            $t->timestampTz('last_run_at')->nullable();

            $t->string('status')->default('active'); // active|paused|completed|canceled
            $t->integer('run_count')->default(0);
            $t->integer('max_runs')->nullable();

            $t->timestampTz('locked_at')->nullable();
            $t->string('lock_owner')->nullable();
            $t->timestampTz('last_tick_at')->nullable();

            $t->timestampsTz();

            $t->index(['next_run_at']);
            $t->index(['user_id']);
        });

        Schema::create('schedule_runs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('schedule_id');
            $t->timestampTz('planned_run_at');
            $t->timestampTz('started_at')->nullable();
            $t->timestampTz('finished_at')->nullable();
            $t->string('status')->default('queued'); // queued|running|ok|failed|skipped
            $t->text('error')->nullable();
            $t->string('output_ref')->nullable();
            $t->timestampTz('created_at')->useCurrent();
            $t->unique(['schedule_id', 'planned_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_runs');
        Schema::dropIfExists('schedules');
    }
};
