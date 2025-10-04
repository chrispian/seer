<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FragBackfillMetrics extends Command
{
    protected $signature = 'frag:backfill:metrics {--days=30 : Number of days to backfill}';

    protected $description = 'Backfill metrics data from existing fragments and schedule runs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $this->info("Backfilling metrics for the last {$days} days");

        $this->backfillFragmentMetrics($days);
        $this->backfillScheduleMetrics($days);

        $this->info('Metrics backfill completed successfully');

        return self::SUCCESS;
    }

    protected function backfillFragmentMetrics(int $days): void
    {
        $this->info('Backfilling fragment metrics...');

        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Fragment creation metrics
        $fragmentData = DB::table('fragments')
            ->select(
                DB::raw('DATE(created_at) as day'),
                'type',
                DB::raw('COUNT(*) as created_count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'), 'type')
            ->get();

        foreach ($fragmentData as $data) {
            DB::table('fragment_metrics_daily')
                ->updateOrInsert(
                    ['day' => $data->day, 'type' => $data->type],
                    ['created' => $data->created_count]
                );
        }

        $this->info("Backfilled fragment metrics for {$fragmentData->count()} day/type combinations");
    }

    protected function backfillScheduleMetrics(int $days): void
    {
        $this->info('Backfilling schedule metrics...');

        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Check if schedule_runs table exists
        if (! DB::getSchemaBuilder()->hasTable('schedule_runs')) {
            $this->warn('schedule_runs table not found, skipping schedule metrics backfill');

            return;
        }

        // Schedule run metrics
        $scheduleData = DB::table('schedule_runs')
            ->select(
                DB::raw('DATE(completed_at) as day'),
                DB::raw('COUNT(*) as total_runs'),
                DB::raw('SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failures'),
                DB::raw('SUM(duration_ms) as duration_sum'),
                DB::raw('COUNT(duration_ms) as duration_count')
            )
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->get();

        foreach ($scheduleData as $data) {
            if ($data->day) {
                DB::table('schedule_metrics_daily')
                    ->updateOrInsert(
                        ['day' => $data->day],
                        [
                            'runs' => $data->total_runs,
                            'failures' => $data->failures,
                            'duration_ms_sum' => $data->duration_sum ?? 0,
                            'duration_ms_count' => $data->duration_count ?? 0,
                        ]
                    );
            }
        }

        $this->info("Backfilled schedule metrics for {$scheduleData->count()} days");
    }
}
