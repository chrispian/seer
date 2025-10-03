<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\Scheduler\ScheduleRunStarted;
use App\Events\Scheduler\ScheduleRunFinished;
use App\Events\Tools\ToolInvoked;
use App\Events\Tools\ToolCompleted;
use App\Events\Commands\CommandStarted;
use App\Events\Commands\CommandCompleted;
use App\Events\Fragments\FragmentCreated;
use App\Events\Fragments\FragmentUpdated;
use App\Events\Fragments\FragmentDeleted;

use App\Listeners\Projectors\SchedulerProjector;
use App\Listeners\Projectors\ToolProjector;
use App\Listeners\Projectors\CommandProjector;
use App\Listeners\Projectors\FragmentLifecycleProjector;

use App\Listeners\Metrics\PipelineMetricsListener;

class FragmentsPipelineEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ScheduleRunStarted::class => [
            SchedulerProjector::class . '@onRunStarted',
            PipelineMetricsListener::class . '@onScheduleRunStarted',
        ],
        ScheduleRunFinished::class => [
            SchedulerProjector::class . '@onRunFinished',
            PipelineMetricsListener::class . '@onScheduleRunFinished',
        ],
        ToolInvoked::class => [
            ToolProjector::class . '@onToolInvoked',
            PipelineMetricsListener::class . '@onToolInvoked',
        ],
        ToolCompleted::class => [
            ToolProjector::class . '@onToolCompleted',
            PipelineMetricsListener::class . '@onToolCompleted',
        ],
        CommandStarted::class => [
            CommandProjector::class . '@onCommandStarted',
            PipelineMetricsListener::class . '@onCommandStarted',
        ],
        CommandCompleted::class => [
            CommandProjector::class . '@onCommandCompleted',
            PipelineMetricsListener::class . '@onCommandCompleted',
        ],
        FragmentCreated::class => [
            FragmentLifecycleProjector::class . '@onCreated',
        ],
        FragmentUpdated::class => [
            FragmentLifecycleProjector::class . '@onUpdated',
        ],
        FragmentDeleted::class => [
            FragmentLifecycleProjector::class . '@onDeleted',
        ],
    ];
}