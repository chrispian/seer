<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'operation_details' => 'array',
        'risk_factors' => 'array',
        'dry_run_result' => 'array',
        'approved_at' => 'datetime',
        'timeout_at' => 'datetime',
    ];

    public function fragment(): BelongsTo
    {
        return $this->belongsTo(Fragment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeTimedOut($query)
    {
        return $query->where('status', 'pending')
            ->where('timeout_at', '<', now());
    }

    public function scopeByConversation($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function isTimedOut(): bool
    {
        return $this->status === 'pending' && $this->timeout_at && $this->timeout_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isTimedOut();
    }
}
