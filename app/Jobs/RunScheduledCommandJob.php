<?php

namespace App\Jobs;

use App\Models\ScheduleRun;
use App\Services\Commands\DSL\CommandRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunScheduledCommandJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $scheduleRunId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $scheduleRun = ScheduleRun::find($this->scheduleRunId);
        if (! $scheduleRun) {
            Log::warning('Schedule run not found', ['id' => $this->scheduleRunId]);

            return;
        }

        $schedule = $scheduleRun->schedule;
        if (! $schedule) {
            Log::warning('Schedule not found for run', ['run_id' => $this->scheduleRunId]);

            return;
        }

        $startTime = microtime(true);
        $scheduleRun->markAsStarted();

        try {
            // Build context for command execution
            $context = [
                'ctx' => [
                    'schedule' => [
                        'id' => $schedule->id,
                        'name' => $schedule->name,
                        'payload' => $schedule->payload ?? [],
                        'planned_run_at' => $scheduleRun->planned_run_at->toISOString(),
                    ],
                    'now' => now()->toISOString(),
                    'user' => ['id' => 1, 'name' => 'System'], // System user for scheduled tasks
                    'workspace' => ['id' => 1],
                    'session' => ['id' => 'scheduler-'.uniqid()],
                ],
            ];

            // Merge schedule payload into context
            if ($schedule->payload) {
                $context['ctx'] = array_merge($context['ctx'], $schedule->payload);
            }

            // Execute the command
            $runner = app(CommandRunner::class);
            $execution = $runner->execute($schedule->command_slug, $context);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($execution['success']) {
                $output = json_encode([
                    'execution' => $execution,
                    'summary' => 'Command executed successfully',
                ]);
                $scheduleRun->markAsCompleted($output, $durationMs);

                Log::info('Scheduled command completed successfully', [
                    'schedule_id' => $schedule->id,
                    'command_slug' => $schedule->command_slug,
                    'duration_ms' => $durationMs,
                ]);
            } else {
                $scheduleRun->markAsFailed($execution['error'] ?? 'Unknown error', $durationMs);

                Log::error('Scheduled command failed', [
                    'schedule_id' => $schedule->id,
                    'command_slug' => $schedule->command_slug,
                    'error' => $execution['error'],
                    'duration_ms' => $durationMs,
                ]);
            }

        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $scheduleRun->markAsFailed($e->getMessage(), $durationMs);

            Log::error('Scheduled command job failed', [
                'schedule_id' => $schedule->id,
                'command_slug' => $schedule->command_slug,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $scheduleRun = ScheduleRun::find($this->scheduleRunId);
        if ($scheduleRun) {
            $scheduleRun->markAsFailed('Job failed: '.$exception->getMessage());
        }

        Log::error('RunScheduledCommandJob failed', [
            'schedule_run_id' => $this->scheduleRunId,
            'error' => $exception->getMessage(),
        ]);
    }
}
