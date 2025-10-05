<?php

namespace App\Listeners\Projectors;

use App\Events\Scheduler\ScheduleRunFinished;
use App\Events\Scheduler\ScheduleRunStarted;
use Illuminate\Support\Facades\DB;

class SchedulerProjector
{
    public function onRunStarted(ScheduleRunStarted $e): void
    {
        // intentionally minimal; aggregate on finish
    }

    public function onRunFinished(ScheduleRunFinished $e): void
    {
        $day = date('Y-m-d');
        $row = DB::table('schedule_metrics_daily')->where('day', $day)->first();
        if ($row) {
            DB::table('schedule_metrics_daily')->where('day', $day)->update([
                'runs' => $row->runs + 1,
                'failures' => $row->failures + ($e->status === 'ok' ? 0 : 1),
                'duration_ms_sum' => $row->duration_ms_sum + (int) $e->durationMs,
                'duration_ms_count' => $row->duration_ms_count + 1,
            ]);
        } else {
            DB::table('schedule_metrics_daily')->insert([
                'day' => $day,
                'runs' => 1,
                'failures' => ($e->status === 'ok' ? 0 : 1),
                'duration_ms_sum' => (int) $e->durationMs,
                'duration_ms_count' => 1,
            ]);
        }
    }
}
