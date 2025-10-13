<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrchestrationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'entity_type',
        'entity_id',
        'correlation_id',
        'session_key',
        'agent_id',
        'payload',
        'emitted_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'emitted_at' => 'datetime',
        'archived_at' => 'datetime',
        'agent_id' => 'integer',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('emitted_at', 'desc')->limit($limit);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    public function scopeByCorrelation($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId)
                     ->orderBy('emitted_at', 'asc');
    }

    public function scopeBySession($query, string $sessionKey)
    {
        return $query->where('session_key', $sessionKey)
                     ->orderBy('emitted_at', 'asc');
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('emitted_at', [$start, $end]);
    }

    public function scopeByActor($query, int $actorId)
    {
        return $query->where('agent_id', $actorId)
                     ->orWhereJsonContains('payload->actor', $actorId);
    }

    public function scopeByEntityChain($query, string $entityType, int $entityId)
    {
        $event = self::byEntity($entityType, $entityId)->first();
        
        if (!$event || !$event->correlation_id) {
            return $query->where('id', -1);
        }

        return $query->where('correlation_id', $event->correlation_id)
                     ->orderBy('emitted_at', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (! $event->emitted_at) {
                $event->emitted_at = now();
            }
            if (! $event->correlation_id) {
                $event->correlation_id = \Illuminate\Support\Str::uuid();
            }
        });
    }
}
