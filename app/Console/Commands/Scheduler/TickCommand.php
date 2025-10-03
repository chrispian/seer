<?php

namespace App\Console\Commands\Scheduler;

use App\Models\Schedule;
use App\Services\Scheduler\NextRunCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TickCommand extends Command
{
    protected $signature = 'frag:scheduler:tick';
    
    protected $description = 'Process scheduled commands that are due to run';

    public function __construct(
        protected NextRunCalculator $calculator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = Carbon::now();
        $this->info("Scheduler tick at {$now->toDateTimeString()}");

        // Find all active schedules that are due to run
        $dueSchedules = Schedule::due()->get();

        if ($dueSchedules->isEmpty()) {
            $this->info('No schedules due to run');
            return self::SUCCESS;
        }

        $this->info("Found {$dueSchedules->count()} schedule(s) due to run");

        $processedCount = 0;
        $errorCount = 0;

        foreach ($dueSchedules as $schedule) {
            try {
                $this->processSchedule($schedule, $now);
                $processedCount++;
                
                $this->line("✓ Processed schedule {$schedule->id}: {$schedule->name}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to process schedule {$schedule->id}: {$e->getMessage()}");
                
                // Log the full error for debugging
                \Log::error('Scheduler failed to process schedule', [
                    'schedule_id' => $schedule->id,
                    'schedule_name' => $schedule->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->newLine();
        $this->info("Scheduler tick completed: {$processedCount} processed, {$errorCount} errors");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function processSchedule(Schedule $schedule, Carbon $now): void
    {
        // Skip if this is a one-off schedule that has already run
        if ($schedule->recurrence_type === 'one_off' && $schedule->scheduleRuns()->exists()) {
            $this->line("  Skipping one-off schedule {$schedule->id} (already executed)");
            return;
        }

        // Lock the schedule for processing
        if (!$schedule->lock()) {
            $this->line("  Schedule {$schedule->id} is locked, skipping");
            return;
        }

        try {
            // Dispatch the job to run the scheduled command
            $schedule->createRun($now->toDateTime());

            // Calculate and update next run time
            if ($schedule->recurrence_type !== 'one_off') {
                try {
                    $nextRun = $this->calculator->calculateNextRun(
                        $schedule->recurrence_type,
                        $schedule->recurrence_value,
                        $schedule->timezone,
                        $now->toDateTime()
                    );

                    $schedule->recordRun($nextRun);

                    if ($nextRun) {
                        $nextRunLocal = Carbon::instance($nextRun)->setTimezone($schedule->timezone);
                        $this->line("  Next run scheduled for: {$nextRunLocal->toDateTimeString()} ({$schedule->timezone})");
                    }
                } catch (\Exception $e) {
                    $this->error("  Failed to calculate next run: {$e->getMessage()}");
                    // Don't fail the entire process, but log it
                    \Log::warning('Failed to calculate next run for schedule', [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage()
                    ]);
                    $schedule->unlock();
                }
            } else {
                // One-off schedule - mark as completed after running
                $schedule->update(['status' => 'completed']);
                $this->line("  One-off schedule marked as completed");
            }
        } catch (\Exception $e) {
            $schedule->unlock();
            throw $e;
        }
    }
}