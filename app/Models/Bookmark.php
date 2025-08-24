<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'name',
        'fragment_ids',
        'last_viewed_at',
    ];

    protected $casts = [
        'fragment_ids' => 'array',
        'last_viewed_at' => 'datetime',
    ];

    public function fragments()
    {
        return Fragment::whereIn('id', $this->fragment_ids ?? []);
    }

    public function getFirstFragmentAttribute()
    {
        $fragmentIds = $this->fragment_ids ?? [];
        if (empty($fragmentIds)) {
            return null;
        }

        return Fragment::find($fragmentIds[0]);
    }

    public function updateLastViewed(): void
    {
        $this->update(['last_viewed_at' => now()]);
    }
}
