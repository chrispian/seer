<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkItem extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'work_items';

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'state' => 'array',
        'metadata' => 'array',
        'delegation_context' => 'array',
        'delegation_history' => 'array',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    protected $fillable = [
        'type',
        'parent_id',
        'assignee_type',
        'assignee_id',
        'status',
        'priority',
        'project_id',
        'tags',
        'state',
        'metadata',
        'delegation_status',
        'delegation_context',
        'delegation_history',
        'estimated_hours',
        'actual_hours',
        'agent_content',
        'plan_content',
        'context_content',
        'todo_content',
        'summary_content',
        'pr_url',
        'completed_at',
    ];

    /**
     * Get the parent work item
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'parent_id');
    }

    /**
     * Get child work items
     */
    public function children(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'parent_id');
    }

    /**
     * Get all task assignments for this work item
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Get the current active assignment
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(TaskAssignment::class)
            ->whereIn('status', ['assigned', 'started'])
            ->latest('assigned_at');
    }

    /**
     * Get the assigned agent (when assignee_type is 'agent')
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class, 'assignee_id');
    }

    /**
     * Get the assigned user (when assignee_type is 'user')
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id')
            ->where('assignee_type', 'user');
    }

    /**
     * Get the sprint items (many-to-many through sprint_items)
     */
    public function sprintItems(): HasMany
    {
        return $this->hasMany(SprintItem::class);
    }

    /**
     * Get the sprints this work item belongs to
     */
    public function sprints()
    {
        return $this->hasManyThrough(
            Sprint::class,
            SprintItem::class,
            'work_item_id',
            'id',
            'id',
            'sprint_id'
        );
    }

    /**
     * Scope for items assigned to agents
     */
    public function scopeAssignedToAgents($query)
    {
        return $query->where('assignee_type', 'agent');
    }

    /**
     * Scope for items assigned to users
     */
    public function scopeAssignedToUsers($query)
    {
        return $query->where('assignee_type', 'user');
    }

    /**
     * Scope by delegation status
     */
    public function scopeByDelegationStatus($query, string $status)
    {
        return $query->where('delegation_status', $status);
    }

    /**
     * Scope for unassigned items
     */
    public function scopeUnassigned($query)
    {
        return $query->where('delegation_status', 'unassigned');
    }
}
