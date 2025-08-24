<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $primaryKey = 'fragment_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'emails' => 'array',
        'phones' => 'array',
        'state' => 'array',
    ];

    protected $fillable = [
        'fragment_id',
        'full_name',
        'emails',
        'phones',
        'organization',
        'state'
    ];

    public function fragment(): BelongsTo
    {
        return $this->belongsTo(Fragment::class, 'fragment_id');
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('full_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('organization', 'LIKE', "%{$searchTerm}%")
              ->orWhereJsonContains('emails', $searchTerm)
              ->orWhereHas('fragment', function($fragmentQuery) use ($searchTerm) {
                  $fragmentQuery->where('message', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    public function getPrimaryEmailAttribute(): ?string
    {
        $emails = $this->emails;
        return is_array($emails) && !empty($emails) ? $emails[0] : null;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: 'Unnamed Contact';
    }
}
