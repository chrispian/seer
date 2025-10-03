<?php

namespace App\Services\Metrics;

// Stub for Prometheus client integration; wire your preferred package here.
class PromMetrics implements Metrics
{
    public function inc(string $name, array $labels = [], float $value = 1.0): void {}
    public function observe(string $name, float $value, array $labels = []): void {}
}
