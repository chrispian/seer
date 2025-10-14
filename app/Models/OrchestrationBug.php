<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrchestrationBug extends Model
{
    protected $fillable = [
        'bug_hash',
        'task_code',
        'error_message',
        'file_path',
        'line_number',
        'stack_trace',
        'context',
        'resolution',
        'resolved_at',
    ];

    protected $casts = [
        'context' => 'array',
        'resolved_at' => 'datetime',
        'line_number' => 'integer',
    ];

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeForTask($query, string $taskCode)
    {
        return $query->where('task_code', $taskCode);
    }

    public function markResolved(string $resolution): void
    {
        $this->update([
            'resolution' => $resolution,
            'resolved_at' => now(),
        ]);
    }
}
