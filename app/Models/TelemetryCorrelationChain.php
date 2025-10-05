<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TelemetryCorrelationChain extends Model
{
    protected $fillable = [
        'chain_id',
        'root_correlation_id',
        'depth',
        'started_at',
        'completed_at',
        'total_events',
        'chain_metadata',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'chain_metadata' => 'array',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeByDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', $depth);
    }

    public function scopeTimeRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('started_at', [$start, $end]);
    }

    // Relationships
    public function events()
    {
        return $this->hasMany(TelemetryEvent::class, 'correlation_id', 'root_correlation_id');
    }

    // Helper methods
    public function getDurationMs(): ?float
    {
        if (! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMilliseconds($this->completed_at);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return $this->chain_metadata[$key] ?? $default;
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markFailed(?string $reason = null): void
    {
        $metadata = $this->chain_metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'chain_metadata' => $metadata,
        ]);
    }
}
