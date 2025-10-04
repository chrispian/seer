<?php

namespace App\Services\Metrics;

use Illuminate\Support\Facades\Log;

class LogMetrics implements Metrics
{
    public function inc(string $metric, array $labels = []): void
    {
        Log::info("Metric increment: {$metric}", ['labels' => $labels]);
    }

    public function observe(string $metric, float $value, array $labels = []): void
    {
        Log::info("Metric observation: {$metric} = {$value}", ['labels' => $labels]);
    }
}
