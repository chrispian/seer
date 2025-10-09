<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'stream',
        'type',
        'task_id',
        'project_id',
        'to_agent_id',
        'from_agent_id',
        'headers',
        'envelope',
        'read_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'envelope' => 'array',
        'read_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'task_id');
    }

    public function toAgent(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class, 'to_agent_id');
    }

    public function fromAgent(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class, 'from_agent_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeByStream($query, string $stream)
    {
        return $query->where('stream', $stream);
    }

    public function scopeByTask($query, string $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeByAgent($query, string $agentId)
    {
        return $query->where(function ($q) use ($agentId) {
            $q->where('to_agent_id', $agentId)
                ->orWhere('from_agent_id', $agentId);
        });
    }

    public function scopeToAgent($query, string $agentId)
    {
        return $query->where('to_agent_id', $agentId);
    }

    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return false;
        }

        $this->read_at = now();

        return $this->save();
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
