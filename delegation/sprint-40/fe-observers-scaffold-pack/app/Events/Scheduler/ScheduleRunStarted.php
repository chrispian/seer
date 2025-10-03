<?php
namespace App\Events\Scheduler;
class ScheduleRunStarted { public function __construct(
    public string $scheduleId, public string $runId, public string $commandSlug, public ?string $workspaceId, public ?string $userId, public string $plannedAt
){} }
