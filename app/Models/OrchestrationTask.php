<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrchestrationTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sprint_id',
        'type',
        'parent_id',
        'assignee_type',
        'assignee_id',
        'project_id',
        'task_code',
        'title',
        'description',
        'status',
        'delegation_status',
        'priority',
        'phase',
        'tags',
        'state',
        'delegation_context',
        'delegation_history',
        'estimated_hours',
        'actual_hours',
        'hash',
        'metadata',
        'agent_config',
        'agent_content',
        'plan_content',
        'context_content',
        'todo_content',
        'summary_content',
        'file_path',
        'pr_url',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'agent_config' => 'array',
        'tags' => 'array',
        'state' => 'array',
        'delegation_context' => 'array',
        'delegation_history' => 'array',
        'phase' => 'integer',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(OrchestrationSprint::class, 'sprint_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrchestrationEvent::class, 'entity_id')
            ->where('entity_type', 'task');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrchestrationTask::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrchestrationTask::class, 'parent_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'work_item_id', 'id');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(TaskAssignment::class, 'work_item_id', 'id')
            ->whereIn('status', ['assigned', 'started'])
            ->latest('assigned_at');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class, 'assignee_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id')
            ->where('assignee_type', 'user');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class, 'task_id', 'id');
    }

    public function scopeAssignedToAgents($query)
    {
        return $query->where('assignee_type', 'agent');
    }

    public function scopeAssignedToUsers($query)
    {
        return $query->where('assignee_type', 'user');
    }

    public function scopeByDelegationStatus($query, string $status)
    {
        return $query->where('delegation_status', $status);
    }

    public function scopeUnassigned($query)
    {
        return $query->where('delegation_status', 'unassigned');
    }

    public function generateHash(): string
    {
        $timestamp = $this->updated_at ? $this->updated_at->timestamp : now()->timestamp;
        
        return hash('sha256', 
            $this->task_code . 
            json_encode($this->metadata ?? []) . 
            $timestamp
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($task) {
            // Set updated_at if not already set (during creation)
            if (!$task->updated_at) {
                $task->updated_at = now();
            }
            
            // Generate hash on create or when code/metadata changes
            if (!$task->exists || $task->isDirty(['task_code', 'metadata'])) {
                $task->hash = $task->generateHash();
            }
        });
    }
}
