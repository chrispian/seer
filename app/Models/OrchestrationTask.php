<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrchestrationTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sprint_id',
        'task_code',
        'title',
        'status',
        'priority',
        'phase',
        'hash',
        'metadata',
        'agent_config',
        'file_path',
    ];

    protected $casts = [
        'metadata' => 'array',
        'agent_config' => 'array',
        'phase' => 'integer',
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
