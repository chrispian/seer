<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FragMetricsBackfill extends Command
{
    protected $signature = 'frag:metrics:backfill {--days=30}';

    protected $description = 'Backfill inbox_metrics_daily from fragments history';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $start = CarbonImmutable::now()->subDays($days);

        $rows = DB::table('fragments')
            ->selectRaw("date(reviewed_at) as day, count(*) filter (where inbox_status='accepted') as accepted, count(*) filter (where inbox_status='archived') as archived, sum(extract(epoch from (reviewed_at - coalesce(inbox_at, created_at))) * 1000) as sum_ms, count(reviewed_at) as cnt_ms")
            ->where('reviewed_at', '>=', $start)
            ->groupBy('day')
            ->get();

        foreach ($rows as $r) {
            $exists = DB::table('inbox_metrics_daily')->where('day', $r->day)->first();
            if ($exists) {
                DB::table('inbox_metrics_daily')->where('day', $r->day)->update([
                    'accepted_count' => (int) $r->accepted,
                    'archived_count' => (int) $r->archived,
                    'review_time_ms_sum' => (int) $r->sum_ms,
                    'review_time_ms_count' => (int) $r->cnt_ms,
                ]);
            } else {
                DB::table('inbox_metrics_daily')->insert([
                    'day' => $r->day,
                    'accepted_count' => (int) $r->accepted,
                    'archived_count' => (int) $r->archived,
                    'review_time_ms_sum' => (int) $r->sum_ms,
                    'review_time_ms_count' => (int) $r->cnt_ms,
                ]);
            }
        }

        $this->info('Backfill complete');

        return self::SUCCESS;
    }
}
