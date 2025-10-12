<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSession extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'work_sessions';

    protected $guarded = [];

    protected $casts = [
        'context_stack' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_active_seconds' => 'integer',
        'tasks_completed' => 'integer',
        'artifacts_created' => 'integer',
    ];

    protected $fillable = [
        'session_key',
        'agent_id',
        'user_id',
        'chat_session_id',
        'source',
        'session_type',
        'status',
        'context_stack',
        'active_project_id',
        'active_sprint_id',
        'active_task_id',
        'metadata',
        'started_at',
        'paused_at',
        'resumed_at',
        'ended_at',
        'total_active_seconds',
        'summary',
        'tasks_completed',
        'artifacts_created',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class, 'agent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function activeSprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'active_sprint_id');
    }

    public function activeTask(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'active_task_id');
    }

    public function contextHistory(): HasMany
    {
        return $this->hasMany(SessionContextHistory::class, 'session_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SessionActivity::class, 'session_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForAgent($query, string $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('started_at', 'desc');
    }

    public function getActiveContextAttribute(): ?array
    {
        $stack = $this->context_stack ?? [];

        return end($stack) ?: null;
    }

    public function getDurationInSecondsAttribute(): int
    {
        if ($this->ended_at) {
            return $this->started_at->diffInSeconds($this->ended_at);
        }

        if ($this->paused_at) {
            return $this->total_active_seconds;
        }

        return $this->started_at->diffInSeconds(now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
