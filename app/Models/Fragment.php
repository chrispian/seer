<?php

namespace App\Models;

use App\Enums\FragmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fragment extends Model
{
    use SoftDeletes;

    protected $casts = [
        'pinned' => 'boolean',
        'importance' => 'integer',
        'confidence' => 'integer',
        'tags' => 'array',
        'relationships' => 'array',
        'metadata' => 'array',
        'state' => 'array',
        'state_json' => 'array',
        'type' => FragmentType::class,
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function objectType(): BelongsTo
    {
        return $this->belongsTo(ObjectType::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_key', 'key');
    }

    // Tag relationships
    public function fragmentTags(): HasMany
    {
        return $this->hasMany(FragmentTag::class);
    }

    // Link relationships
    public function linksFrom(): HasMany
    {
        return $this->hasMany(FragmentLink::class, 'from_id');
    }

    public function linksTo(): HasMany
    {
        return $this->hasMany(FragmentLink::class, 'to_id');
    }

    // Article usage
    public function articleUsages(): HasMany
    {
        return $this->hasMany(ArticleFragment::class);
    }

    // Typed object relationships
    public function todo(): HasOne
    {
        return $this->hasOne(Todo::class, 'fragment_id');
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class, 'fragment_id');
    }

    public function link(): HasOne
    {
        return $this->hasOne(Link::class, 'fragment_id');
    }

    public function file(): HasOne
    {
        return $this->hasOne(File::class, 'fragment_id');
    }

    public function calendarEvent(): HasOne
    {
        return $this->hasOne(CalendarEvent::class, 'fragment_id');
    }

    public function meeting(): HasOne
    {
        return $this->hasOne(Meeting::class, 'fragment_id');
    }
}

