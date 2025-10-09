<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandAuditLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'arguments' => 'array',
        'options' => 'array',
        'is_destructive' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDestructive($query)
    {
        return $query->where('is_destructive', true);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByCommand($query, string $commandName)
    {
        return $query->where('command_name', $commandName);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed')
            ->orWhere(function ($q) {
                $q->where('status', 'completed')
                    ->where('exit_code', '!=', 0);
            });
    }
}
