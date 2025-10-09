<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'task_activities';

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'agent_id',
        'user_id',
        'activity_type',
        'action',
        'description',
        'changes',
        'metadata',
    ];

    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->created_at) {
                $model->created_at = now();
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'task_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class, 'agent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeForTask($query, string $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public static function logStatusChange(
        string $taskId,
        string $fromStatus,
        string $toStatus,
        ?string $agentId = null,
        ?int $userId = null,
        ?string $description = null
    ): self {
        return self::create([
            'task_id' => $taskId,
            'agent_id' => $agentId,
            'user_id' => $userId,
            'activity_type' => 'status_change',
            'action' => $toStatus,
            'description' => $description ?? "Status changed from {$fromStatus} to {$toStatus}",
            'changes' => [
                'from' => $fromStatus,
                'to' => $toStatus,
            ],
        ]);
    }

    public static function logContentUpdate(
        string $taskId,
        string $field,
        string $action = 'updated',
        ?string $agentId = null,
        ?int $userId = null,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'task_id' => $taskId,
            'agent_id' => $agentId,
            'user_id' => $userId,
            'activity_type' => 'content_update',
            'action' => $action,
            'description' => $description ?? "Updated {$field}",
            'changes' => [
                'field' => $field,
            ],
            'metadata' => $metadata,
        ]);
    }

    public static function logAssignment(
        string $taskId,
        ?string $agentId = null,
        ?int $userId = null,
        string $action = 'assigned',
        ?string $description = null
    ): self {
        return self::create([
            'task_id' => $taskId,
            'agent_id' => $agentId,
            'user_id' => $userId,
            'activity_type' => 'assignment',
            'action' => $action,
            'description' => $description ?? 'Task assigned',
            'changes' => [
                'agent_id' => $agentId,
                'user_id' => $userId,
            ],
        ]);
    }

    public static function logNote(
        string $taskId,
        string $description,
        string $action = 'note_added',
        ?string $agentId = null,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'task_id' => $taskId,
            'agent_id' => $agentId,
            'user_id' => $userId,
            'activity_type' => 'note',
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public static function logError(
        string $taskId,
        string $description,
        string $action = 'error_encountered',
        ?string $agentId = null,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'task_id' => $taskId,
            'agent_id' => $agentId,
            'user_id' => $userId,
            'activity_type' => 'error',
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public static function logArtifact(
        string $taskId,
        string $artifactId,
        string $feUri,
        string $filename,
        int $sizeBytes,
        ?string $agentId = null,
        ?int $userId = null
    ): self {
        return self::create([
            'task_id' => $taskId,
            'agent_id' => $agentId,
            'user_id' => $userId,
            'activity_type' => 'artifact_attached',
            'action' => 'file_attached',
            'description' => "Attached file: {$filename}",
            'metadata' => [
                'artifact_id' => $artifactId,
                'fe_uri' => $feUri,
                'filename' => $filename,
                'size_bytes' => $sizeBytes,
            ],
        ]);
    }
}
