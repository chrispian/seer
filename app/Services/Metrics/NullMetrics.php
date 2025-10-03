<?php

namespace App\Services\Metrics;

class NullMetrics implements Metrics
{
    public function inc(string $metric, array $labels = []): void
    {
        // Do nothing
    }

    public function observe(string $metric, float $value, array $labels = []): void
    {
        // Do nothing
    }
}