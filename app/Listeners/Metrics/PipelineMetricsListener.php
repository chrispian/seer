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
    protected Metrics $metrics;

    public function __construct()
    {
        $driver = config('metrics.driver', 'null');
        $this->metrics = match($driver) {
            'log' => App::make(\App\Services\Metrics\LogMetrics::class),
            default => App::make(\App\Services\Metrics\NullMetrics::class),
        };
    }

    public function onScheduleRunStarted(ScheduleRunStarted $event): void
    {
        $this->metrics->inc('schedule.runs.started');
    }

    public function onScheduleRunFinished(ScheduleRunFinished $event): void
    {
        $this->metrics->inc('schedule.runs.finished', ['status' => $event->status]);
        $this->metrics->observe('schedule.run.duration_ms', (float) $event->durationMs);
        if ($event->status !== 'ok') {
            $this->metrics->inc('schedule.runs.failed');
        }
    }

    public function onToolInvoked(ToolInvoked $event): void
    {
        $this->metrics->inc('tools.invoked', ['tool' => $event->tool]);
    }

    public function onToolCompleted(ToolCompleted $event): void
    {
        $this->metrics->inc('tools.completed', ['tool' => $event->tool, 'status' => $event->status]);
        $this->metrics->observe('tools.duration_ms', (float) $event->durationMs, ['tool' => $event->tool]);
        if ($event->status !== 'ok') {
            $this->metrics->inc('tools.errors', ['tool' => $event->tool]);
        }
    }

    public function onCommandStarted(CommandStarted $event): void
    {
        $this->metrics->inc('commands.started', ['slug' => $event->slug]);
    }

    public function onCommandCompleted(CommandCompleted $event): void
    {
        $this->metrics->inc('commands.completed', ['slug' => $event->slug, 'status' => $event->status]);
        $this->metrics->observe('commands.duration_ms', (float) $event->durationMs, ['slug' => $event->slug]);
        if ($event->status !== 'ok') {
            $this->metrics->inc('commands.failed', ['slug' => $event->slug]);
        }
    }
}