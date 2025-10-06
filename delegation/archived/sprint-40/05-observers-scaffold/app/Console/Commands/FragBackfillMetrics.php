<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FragBackfillMetrics extends Command
{
    protected $signature = 'frag:backfill:metrics {--days=30}';

    protected $description = 'Backfill schedule and tool metrics daily aggregates from activity tables';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = CarbonImmutable::now()->subDays($days)->startOfDay();

        // Tool metrics from tool_activity
        if (Schema::hasTable('tool_activity')) {
            $rows = DB::table('tool_activity')
                ->selectRaw("date(ts) as day, tool, count(*) as c, sum(case when status != 'ok' then 1 else 0 end) as errs, sum(coalesce(duration_ms,0)) as sum_ms, sum(case when duration_ms is not null then 1 else 0 end) as cnt_ms")
                ->where('ts', '>=', $since)
                ->groupBy('day', 'tool')->get();
            foreach ($rows as $r) {
                $exists = DB::table('tool_metrics_daily')->where(['day' => $r->day, 'tool' => $r->tool])->first();
                $data = ['invocations' => (int) $r->c, 'errors' => (int) $r->errs, 'duration_ms_sum' => (int) $r->sum_ms, 'duration_ms_count' => (int) $r->cnt_ms];
                if ($exists) {
                    DB::table('tool_metrics_daily')->where(['day' => $r->day, 'tool' => $r->tool])->update($data);
                } else {
                    DB::table('tool_metrics_daily')->insert(array_merge(['day' => $r->day, 'tool' => $r->tool], $data));
                }
            }
        }

        // Scheduler metrics from schedule_runs
        if (Schema::hasTable('schedule_runs')) {
            $rows = DB::table('schedule_runs')
                ->selectRaw("date(started_at) as day, count(*) as c, sum(case when status='failed' then 1 else 0 end) as fails, sum(extract(epoch from (finished_at - started_at))*1000) as sum_ms, sum(case when finished_at is not null and started_at is not null then 1 else 0 end) as cnt_ms")
                ->where('started_at', '>=', $since)
                ->groupBy('day')->get();
            foreach ($rows as $r) {
                $exists = DB::table('schedule_metrics_daily')->where('day', $r->day)->first();
                $data = ['runs' => (int) $r->c, 'failures' => (int) $r->fails, 'duration_ms_sum' => (int) $r->sum_ms, 'duration_ms_count' => (int) $r->cnt_ms];
                if ($exists) {
                    DB::table('schedule_metrics_daily')->where('day', $r->day)->update($data);
                } else {
                    DB::table('schedule_metrics_daily')->insert(array_merge(['day' => $r->day], $data));
                }
            }
        }

        $this->info('Backfill done');

        return self::SUCCESS;
    }
}
