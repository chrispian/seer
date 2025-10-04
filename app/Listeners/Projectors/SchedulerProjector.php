<?php

namespace App\Listeners\Projectors;

use App\Events\Scheduler\ScheduleRunFinished;
use App\Events\Scheduler\ScheduleRunStarted;
use Illuminate\Support\Facades\DB;

class SchedulerProjector
{
    public function onRunStarted(ScheduleRunStarted $event): void
    {
        $today = now()->format('Y-m-d');

        $existing = DB::table('schedule_metrics_daily')
            ->where('day', $today)
            ->first();

        if ($existing) {
            DB::table('schedule_metrics_daily')
                ->where('day', $today)
                ->update(['runs' => $existing->runs + 1]);
        } else {
            DB::table('schedule_metrics_daily')->insert([
                'day' => $today,
                'runs' => 1,
                'failures' => 0,
                'duration_ms_sum' => 0,
                'duration_ms_count' => 0,
            ]);
        }
    }

    public function onRunFinished(ScheduleRunFinished $event): void
    {
        $today = now()->format('Y-m-d');

        $existing = DB::table('schedule_metrics_daily')
            ->where('day', $today)
            ->first();

        if ($existing) {
            $updateData = [
                'duration_ms_sum' => $existing->duration_ms_sum + $event->durationMs,
                'duration_ms_count' => $existing->duration_ms_count + 1,
            ];

            if ($event->status !== 'ok') {
                $updateData['failures'] = $existing->failures + 1;
            }

            DB::table('schedule_metrics_daily')
                ->where('day', $today)
                ->update($updateData);
        } else {
            DB::table('schedule_metrics_daily')->insert([
                'day' => $today,
                'runs' => 0,
                'failures' => $event->status !== 'ok' ? 1 : 0,
                'duration_ms_sum' => $event->durationMs,
                'duration_ms_count' => 1,
            ]);
        }
    }
}
