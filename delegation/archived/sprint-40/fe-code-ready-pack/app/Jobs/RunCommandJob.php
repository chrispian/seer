<?php

namespace App\Jobs;

use App\Models\ScheduleRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $scheduleId,
        public string $commandSlug,
        public array $payload,
        public $plannedAt
    ) {}

    public function handle(): void
    {
        $ctx = [
            'schedule' => ['id' => $this->scheduleId, 'planned_run_at' => $this->plannedAt],
            'payload' => $this->payload,
            'now' => now()->toIso8601String(),
        ];

        $run = ScheduleRun::where('schedule_id', $this->scheduleId)
            ->where('planned_run_at', $this->plannedAt)->first();
        if ($run) {
            $run->update(['status' => 'running', 'started_at' => now()]);
        }

        try {
            $result = app(\App\Services\Commands\DSL\Runner::class)->run($this->commandSlug, $ctx);
            if ($run) {
                $run->update(['status' => 'ok', 'finished_at' => now(), 'output_ref' => $result['ref'] ?? null]);
            }
        } catch (\Throwable $e) {
            if ($run) {
                $run->update(['status' => 'failed', 'finished_at' => now(), 'error' => $e->getMessage()]);
            }
            throw $e;
        }
    }
}
