<?php

namespace App\Listeners\Metrics;

use App\Events\FragmentAccepted;
use App\Events\FragmentArchived;
use App\Services\Metrics\Metrics;
use Illuminate\Support\Facades\App;

class FragmentMetricsListener
{
    protected Metrics $m;

    public function __construct()
    {
        $driver = config('metrics.driver', 'null');
        $this->m = match ($driver) {
            'log' => App::make(\App\Services\Metrics\LogMetrics::class),
            'prom' => App::make(\App\Services\Metrics\PromMetrics::class),
            default => App::make(\App\Services\Metrics\NullMetrics::class),
        };
    }

    public function onAccepted(FragmentAccepted $e): void
    {
        $this->m->inc('fragments.accepted.total');
        // Optional: observe review time if provided in updates (ms)
        if (isset($e->updates['review_time_ms'])) {
            $this->m->observe('fragments.review_time_ms', (float) $e->updates['review_time_ms']);
        }
    }

    public function onArchived(FragmentArchived $e): void
    {
        $this->m->inc('fragments.archived.total', [], count($e->fragmentIds));
    }
}
