<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentLog extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'agent_logs';

    protected $fillable = [
        'source_type',
        'source_file',
        'file_modified_at',
        'log_level',
        'log_timestamp',
        'service',
        'message',
        'structured_data',
        'session_id',
        'provider',
        'model',
        'tool_calls',
        'work_item_id',
        'file_checksum',
        'file_line_number',
    ];

    protected $casts = [
        'file_modified_at' => 'datetime',
        'log_timestamp' => 'datetime',
        'structured_data' => 'array',
        'tool_calls' => 'array',
        'file_line_number' => 'integer',
    ];

    /**
     * Get the work item associated with this log entry
     */
    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    /**
     * Scope for specific source types
     */
    public function scopeSourceType($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Scope for specific time range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('log_timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope for tool calls
     */
    public function scopeWithToolCalls($query)
    {
        return $query->whereNotNull('tool_calls');
    }

    /**
     * Scope for specific session
     */
    public function scopeSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
