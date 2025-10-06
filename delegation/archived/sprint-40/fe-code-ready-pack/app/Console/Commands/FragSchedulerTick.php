<?php

namespace App\Console\Commands;

use App\Jobs\RunCommandJob;
use App\Models\Schedule;
use App\Models\ScheduleRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FragSchedulerTick extends Command
{
    protected $signature = 'frag:scheduler:tick {--limit=50}';

    protected $description = 'Claim due schedules and enqueue runs';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $owner = gethostname().':'.getmypid();

        $due = Schedule::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('locked_at')
                    ->orWhere('locked_at', '<', now()->subMinutes(5));
            })
            ->orderBy('next_run_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($due as $sch) {
            DB::transaction(function () use ($sch, $owner) {
                $fresh = Schedule::lockForUpdate()->find($sch->id);
                if (! $fresh) {
                    return;
                }
                if ($fresh->locked_at && $fresh->locked_at->gt(now()->subMinutes(5))) {
                    return;
                }

                $fresh->locked_at = now();
                $fresh->lock_owner = $owner;
                $fresh->last_tick_at = now();
                $fresh->save();

                ScheduleRun::firstOrCreate(
                    ['schedule_id' => $fresh->id, 'planned_run_at' => $fresh->next_run_at],
                    ['status' => 'queued']
                );

                dispatch(new RunCommandJob($fresh->id, $fresh->command_slug, $fresh->payload, $fresh->next_run_at));

                $fresh->computeAndPersistNextRun();
            });
        }

        $this->info("Tick complete: processed {$due->count()}");

        return self::SUCCESS;
    }
}
