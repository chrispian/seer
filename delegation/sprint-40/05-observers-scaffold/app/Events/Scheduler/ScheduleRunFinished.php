<?php

namespace App\Events\Scheduler;

class ScheduleRunFinished
{
    public function __construct(
        public string $scheduleId, public string $runId, public string $commandSlug, public ?string $workspaceId, public ?string $userId, public string $plannedAt,
        public string $status, public float $durationMs, public ?string $error = null
    ) {}
}
