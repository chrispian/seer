<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TelemetryHealthCheck extends Model
{
    protected $fillable = [
        'component',
        'check_name',
        'is_healthy',
        'error_message',
        'response_time_ms',
        'check_metadata',
        'checked_at',
    ];

    protected $casts = [
        'is_healthy' => 'boolean',
        'response_time_ms' => 'decimal:3',
        'check_metadata' => 'array',
        'checked_at' => 'datetime',
    ];

    // Scopes
    public function scopeByComponent(Builder $query, string $component): Builder
    {
        return $query->where('component', $component);
    }

    public function scopeByCheckName(Builder $query, string $checkName): Builder
    {
        return $query->where('check_name', $checkName);
    }

    public function scopeHealthy(Builder $query): Builder
    {
        return $query->where('is_healthy', true);
    }

    public function scopeUnhealthy(Builder $query): Builder
    {
        return $query->where('is_healthy', false);
    }

    public function scopeTimeRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('checked_at', [$start, $end]);
    }

    public function scopeRecent(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('checked_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeSlowChecks(Builder $query, float $thresholdMs = 1000): Builder
    {
        return $query->where('response_time_ms', '>', $thresholdMs);
    }

    // Helper methods
    public function isHealthy(): bool
    {
        return $this->is_healthy;
    }

    public function isSlow(float $thresholdMs = 1000): bool
    {
        return $this->response_time_ms && $this->response_time_ms > $thresholdMs;
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return $this->check_metadata[$key] ?? $default;
    }

    public function getFormattedResponseTime(): string
    {
        if (! $this->response_time_ms) {
            return 'N/A';
        }

        return number_format($this->response_time_ms, 1).'ms';
    }

    public function getFormattedTimestamp(): string
    {
        return $this->checked_at->format('Y-m-d H:i:s');
    }

    public function getStatusIcon(): string
    {
        return $this->is_healthy ? '✅' : '❌';
    }
}
