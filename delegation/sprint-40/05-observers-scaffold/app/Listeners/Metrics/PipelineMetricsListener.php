<?php

namespace App\Listeners\Metrics;

use App\Services\Metrics\Metrics;
use Illuminate\Support\Facades\App;
use App\Events\Scheduler\ScheduleRunStarted;
use App\Events\Scheduler\ScheduleRunFinished;
use App\Events\Tools\ToolInvoked;
use App\Events\Tools\ToolCompleted;
use App\Events\Commands\CommandStarted;
use App\Events\Commands\CommandCompleted;

class PipelineMetricsListener
{
    protected Metrics $m;

    public function __construct()
    {
        $driver = config('metrics.driver','null');
        $this->m = match($driver) {
            'log' => App::make(\App\Services\Metrics\LogMetrics::class),
            'prom' => App::make(\App\Services\Metrics\PromMetrics::class),
            default => App::make(\App\Services\Metrics\NullMetrics::class),
        };
    }

    public function onScheduleRunStarted(ScheduleRunStarted $e): void
    {
        $this->m->inc('schedule.runs.started');
    }

    public function onScheduleRunFinished(ScheduleRunFinished $e): void
    {
        $this->m->inc('schedule.runs.finished', ['status'=>$e->status]);
        $this->m->observe('schedule.run.duration_ms', (float) $e->durationMs);
        if ($e->status !== 'ok') $this->m->inc('schedule.runs.failed');
    }

    public function onToolInvoked(ToolInvoked $e): void
    {
        $this->m->inc('tools.invoked', ['tool'=>$e->tool]);
    }

    public function onToolCompleted(ToolCompleted $e): void
    {
        $this->m->inc('tools.completed', ['tool'=>$e->tool, 'status'=>$e->status]);
        $this->m->observe('tools.duration_ms', (float) $e->durationMs, ['tool'=>$e->tool]);
        if ($e->status !== 'ok') $this->m->inc('tools.errors', ['tool'=>$e->tool]);
    }

    public function onCommandStarted(CommandStarted $e): void
    {
        $this->m->inc('commands.started', ['slug'=>$e->slug]);
    }

    public function onCommandCompleted(CommandCompleted $e): void
    {
        $this->m->inc('commands.completed', ['slug'=>$e->slug, 'status'=>$e->status]);
        $this->m->observe('commands.duration_ms', (float) $e->durationMs, ['slug'=>$e->slug]);
        if ($e->status !== 'ok') $this->m->inc('commands.failed', ['slug'=>$e->slug]);
    }
}
