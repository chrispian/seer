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
