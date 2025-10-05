<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TelemetryPerformanceSnapshot extends Model
{
    protected $fillable = [
        'component',
        'operation',
        'duration_ms',
        'memory_usage_bytes',
        'cpu_usage_percent',
        'resource_metrics',
        'performance_class',
        'recorded_at',
    ];

    protected $casts = [
        'duration_ms' => 'decimal:3',
        'memory_usage_bytes' => 'integer',
        'cpu_usage_percent' => 'integer',
        'resource_metrics' => 'array',
        'recorded_at' => 'datetime',
    ];

    // Scopes
    public function scopeByComponent(Builder $query, string $component): Builder
    {
        return $query->where('component', $component);
    }

    public function scopeByOperation(Builder $query, string $operation): Builder
    {
        return $query->where('operation', $operation);
    }

    public function scopeByPerformanceClass(Builder $query, string $class): Builder
    {
        return $query->where('performance_class', $class);
    }

    public function scopeTimeRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    public function scopeSlowOperations(Builder $query): Builder
    {
        return $query->whereIn('performance_class', ['slow', 'critical']);
    }

    public function scopeFastOperations(Builder $query): Builder
    {
        return $query->where('performance_class', 'fast');
    }

    public function scopeHighMemoryUsage(Builder $query, int $thresholdBytes = 268435456): Builder // 256MB
    {
        return $query->where('memory_usage_bytes', '>', $thresholdBytes);
    }

    public function scopeHighCpuUsage(Builder $query, int $thresholdPercent = 80): Builder
    {
        return $query->where('cpu_usage_percent', '>', $thresholdPercent);
    }

    // Helper methods
    public function isSlow(): bool
    {
        return in_array($this->performance_class, ['slow', 'critical']);
    }

    public function isFast(): bool
    {
        return $this->performance_class === 'fast';
    }

    public function getFormattedDuration(): string
    {
        if ($this->duration_ms < 1000) {
            return number_format($this->duration_ms, 1).'ms';
        } else {
            return number_format($this->duration_ms / 1000, 2).'s';
        }
    }

    public function getFormattedMemoryUsage(): string
    {
        if (! $this->memory_usage_bytes) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->memory_usage_bytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, 1).$units[$i];
    }

    public function getFormattedCpuUsage(): string
    {
        if (! $this->cpu_usage_percent) {
            return 'N/A';
        }

        return $this->cpu_usage_percent.'%';
    }

    public function getResourceMetricValue(string $key, $default = null)
    {
        return $this->resource_metrics[$key] ?? $default;
    }

    public function getPerformanceClassIcon(): string
    {
        return match ($this->performance_class) {
            'fast' => '🟢',
            'normal' => '🟡',
            'slow' => '🟠',
            'critical' => '🔴',
            default => '⚪'
        };
    }

    public function getFormattedTimestamp(): string
    {
        return $this->recorded_at->format('Y-m-d H:i:s');
    }
}
