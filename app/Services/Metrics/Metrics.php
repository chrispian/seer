<?php

namespace App\Services\Metrics;

interface Metrics
{
    public function inc(string $metric, array $labels = []): void;

    public function observe(string $metric, float $value, array $labels = []): void;
}
