<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'command_slug',
        'payload',
        'status',
        'recurrence_type',
        'recurrence_value',
        'timezone',
        'next_run_at',
        'last_run_at',
        'locked_at',
        'lock_owner',
        'last_tick_at',
        'run_count',
        'max_runs',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'locked_at' => 'datetime',
        'last_tick_at' => 'datetime',
        'run_count' => 'integer',
        'max_runs' => 'integer',
    ];

    /**
     * Get the schedule runs for this schedule
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ScheduleRun::class);
    }

    /**
     * Get the schedule runs for this schedule (alias for runs)
     */
    public function scheduleRuns(): HasMany
    {
        return $this->runs();
    }

    /**
     * Create a new run for this schedule
     */
    public function createRun(\DateTime $plannedRunAt): ScheduleRun
    {
        $run = ScheduleRun::createForSchedule($this, $plannedRunAt);

        // Dispatch the job to execute the command
        \App\Jobs\RunScheduledCommandJob::dispatch($run->id);

        return $run;
    }

    /**
     * Check if the schedule is due for execution
     */
    public function isDue(): bool
    {
        return $this->status === 'active'
            && $this->next_run_at
            && $this->next_run_at->isPast()
            && (! $this->max_runs || $this->run_count < $this->max_runs);
    }

    /**
     * Check if the schedule is currently locked
     */
    public function isLocked(): bool
    {
        return $this->locked_at && $this->locked_at->gt(now()->subMinutes(5));
    }

    /**
     * Lock the schedule for processing
     */
    public function lock(): bool
    {
        return $this->update([
            'locked_at' => now(),
            'lock_owner' => gethostname().':'.getmypid(),
            'last_tick_at' => now(),
        ]);
    }

    /**
     * Release the lock on the schedule
     */
    public function unlock(): bool
    {
        return $this->update([
            'locked_at' => null,
            'lock_owner' => null,
        ]);
    }

    /**
     * Record a completed run and update next run time
     */
    public function recordRun(?\DateTime $nextRunAt = null): void
    {
        $this->increment('run_count');
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $nextRunAt,
            'locked_at' => null,
            'lock_owner' => null,
        ]);

        // If max runs reached, mark as completed
        if ($this->max_runs && $this->run_count >= $this->max_runs) {
            $this->update(['status' => 'completed']);
        }
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for due schedules
     */
    public function scopeDue($query)
    {
        return $query->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('locked_at')
                    ->orWhere('locked_at', '<', now()->subMinutes(5));
            })
            ->where(function ($q) {
                $q->whereNull('max_runs')
                    ->orWhereRaw('run_count < max_runs');
            });
    }
}
