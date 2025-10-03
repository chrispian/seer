<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

class Schedule extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'schedules';
    protected $guarded = [];
    protected $casts = [
        'payload' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'run_at_local' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            if (!$m->id) $m->id = (string) Str::uuid();
        });
    }

    public function computeAndPersistNextRun(): void
    {
        $this->last_run_at = now();
        $this->run_count = ($this->run_count ?? 0) + 1;

        if ($this->schedule_kind === 'one_off') {
            $this->status = 'completed';
            $this->next_run_at = null;
        } elseif ($this->schedule_kind === 'daily_at' && $this->daily_local_time) {
            $this->next_run_at = app(\App\Services\Scheduler\NextRunCalculator::class)
                ->forDailyAt($this->daily_local_time, $this->tz)
                ->firstUtcAfter(CarbonImmutable::now('UTC'));
        }
        $this->locked_at = null;
        $this->lock_owner = null;
        $this->save();
    }
}
