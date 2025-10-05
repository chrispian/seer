<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TelemetryEvent extends Model
{
    protected $fillable = [
        'correlation_id',
        'event_type',
        'event_name',
        'timestamp',
        'component',
        'operation',
        'metadata',
        'context',
        'performance',
        'message',
        'level',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'metadata' => 'array',
        'context' => 'array',
        'performance' => 'array',
    ];

    protected $dates = [
        'timestamp',
        'created_at',
        'updated_at',
    ];

    // Scopes for common queries
    public function scopeByCorrelationId(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    public function scopeByEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByComponent(Builder $query, string $component): Builder
    {
        return $query->where('component', $component);
    }

    public function scopeByLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeTimeRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    public function scopeErrors(Builder $query): Builder
    {
        return $query->whereIn('level', ['error', 'critical']);
    }

    public function scopePerformanceIssues(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('level', 'warning')
                ->orWhere('event_name', 'like', '%slow%')
                ->orWhere('event_name', 'like', '%timeout%');
        });
    }

    // Relationships
    public function correlationChain()
    {
        return $this->belongsTo(TelemetryCorrelationChain::class, 'correlation_id', 'root_correlation_id');
    }

    public function relatedEvents()
    {
        return $this->hasMany(TelemetryEvent::class, 'correlation_id', 'correlation_id');
    }

    // Helper methods
    public function getDurationMs(): ?float
    {
        return $this->performance['duration_ms'] ?? null;
    }

    public function getMemoryUsage(): ?int
    {
        return $this->performance['memory_usage'] ?? null;
    }

    public function isError(): bool
    {
        return in_array($this->level, ['error', 'critical']);
    }

    public function isPerformanceEvent(): bool
    {
        return ! empty($this->performance);
    }

    public function getFormattedTimestamp(): string
    {
        return $this->timestamp->format('Y-m-d H:i:s');
    }

    public function getContextValue(string $key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}
