<?php

namespace App\Events\Scheduler;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduleRunStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $scheduleId,
        public int $runId,
        public string $commandSlug
    ) {}
}
