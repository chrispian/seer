<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TelemetryMetric extends Model
{
    protected $fillable = [
        'metric_name',
        'component',
        'metric_type',
        'value',
        'labels',
        'timestamp',
        'aggregation_period',
    ];

    protected $casts = [
        'value' => 'decimal:6',
        'labels' => 'array',
        'timestamp' => 'datetime',
    ];

    // Scopes for common queries
    public function scopeByMetricName(Builder $query, string $metricName): Builder
    {
        return $query->where('metric_name', $metricName);
    }

    public function scopeByComponent(Builder $query, string $component): Builder
    {
        return $query->where('component', $component);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('metric_type', $type);
    }

    public function scopeTimeRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }

    public function scopeAggregationPeriod(Builder $query, string $period): Builder
    {
        return $query->where('aggregation_period', $period);
    }

    public function scopeRawMetrics(Builder $query): Builder
    {
        return $query->where('aggregation_period', 'raw');
    }

    public function scopeWithLabel(Builder $query, string $key, string $value): Builder
    {
        return $query->whereJsonContains('labels->'.$key, $value);
    }

    // Helper methods
    public function getLabelValue(string $key, $default = null)
    {
        return $this->labels[$key] ?? $default;
    }

    public function isCounter(): bool
    {
        return $this->metric_type === 'counter';
    }

    public function isGauge(): bool
    {
        return $this->metric_type === 'gauge';
    }

    public function isHistogram(): bool
    {
        return $this->metric_type === 'histogram';
    }

    public function getFormattedValue(): string
    {
        return number_format($this->value, 2);
    }

    public function getFormattedTimestamp(): string
    {
        return $this->timestamp->format('Y-m-d H:i:s');
    }
}
