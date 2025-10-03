<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRun extends Model
{
    protected $fillable = [
        'schedule_id',
        'planned_run_at',
        'started_at',
        'completed_at',
        'status',
        'output',
        'error_message',
        'duration_ms',
        'job_id',
        'dedupe_key',
    ];

    protected $casts = [
        'planned_run_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
    ];

    /**
     * Get the schedule that owns this run
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Mark the run as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the run as completed successfully
     */
    public function markAsCompleted(string $output = null, int $durationMs = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'output' => $output,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Mark the run as failed
     */
    public function markAsFailed(string $errorMessage, int $durationMs = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Generate a dedupe key for idempotency
     */
    public static function generateDedupeKey(int $scheduleId, \DateTime $plannedRunAt): string
    {
        return hash('sha256', $scheduleId . '|' . $plannedRunAt->format('Y-m-d H:i:s'));
    }

    /**
     * Create a new schedule run with idempotency check
     */
    public static function createForSchedule(Schedule $schedule, \DateTime $plannedRunAt): ?self
    {
        $dedupeKey = self::generateDedupeKey($schedule->id, $plannedRunAt);

        // Check if run already exists
        $existing = self::where('dedupe_key', $dedupeKey)->first();
        if ($existing) {
            return $existing;
        }

        return self::create([
            'schedule_id' => $schedule->id,
            'planned_run_at' => $plannedRunAt,
            'dedupe_key' => $dedupeKey,
            'status' => 'pending',
        ]);
    }
}
