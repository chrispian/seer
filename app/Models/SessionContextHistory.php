<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionContextHistory extends Model
{
    use HasFactory;

    protected $table = 'session_context_history';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'context_data' => 'array',
        'switched_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    protected $fillable = [
        'session_id',
        'action',
        'context_type',
        'context_id',
        'context_data',
        'switched_at',
        'duration_seconds',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class, 'session_id');
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByType($query, string $contextType)
    {
        return $query->where('context_type', $contextType);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('switched_at', 'desc');
    }
}
