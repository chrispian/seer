<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionActivity extends Model
{
    use HasFactory;

    protected $table = 'session_activities';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected $fillable = [
        'session_id',
        'activity_type',
        'command',
        'description',
        'task_id',
        'sprint_id',
        'metadata',
        'occurred_at',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class, 'session_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'task_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByType($query, string $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    public function scopeForTask($query, string $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('occurred_at', 'desc');
    }
}
