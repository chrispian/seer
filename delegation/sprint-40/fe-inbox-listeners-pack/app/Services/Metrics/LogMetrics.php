<?php

namespace App\Services\Metrics;

use Illuminate\Support\Facades\Log;

class LogMetrics implements Metrics
{
    public function inc(string $name, array $labels = [], float $value = 1.0): void
    {
        Log::info('metric.inc', ['name'=>$name,'labels'=>$labels,'value'=>$value]);
    }
    public function observe(string $name, float $value, array $labels = []): void
    {
        Log::info('metric.observe', ['name'=>$name,'labels'=>$labels,'value'=>$value]);
    }
}
