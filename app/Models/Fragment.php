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
        'project_id' => 'integer',
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
        'hash_bucket' => 'integer',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function vaultModel(): BelongsTo
    {
        return $this->belongsTo(Vault::class, 'vault');
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

    // Search scopes for autocomplete
    public function scopeSearchContent($query, string $searchTerm)
    {
        return $query->where('message', 'LIKE', "%{$searchTerm}%");
    }

    public function scopeForAutocomplete($query, int $limit = 10)
    {
        return $query->select(['id', 'message', 'type', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    public function scopeForVault($query, $vault)
    {
        return $query->where('vault', $vault);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForVaultAndProject($query, $vault, $projectId = null)
    {
        $query = $query->where('vault', $vault);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query;
    }

    public function getTitleAttribute(): string
    {
        // Try to extract a title from the message
        $lines = explode("\n", $this->message);
        $firstLine = trim($lines[0]);

        // If first line looks like a title (short, no periods at end)
        if (strlen($firstLine) <= 50 && ! str_ends_with($firstLine, '.')) {
            return $firstLine;
        }

        // Otherwise extract first sentence
        $sentences = explode('.', $this->message);
        $firstSentence = trim($sentences[0]);

        if (strlen($firstSentence) <= 60) {
            return $firstSentence;
        }

        // Fallback to truncated message
        return substr($this->message, 0, 50).(strlen($this->message) > 50 ? '...' : '');
    }

    public function getPreviewAttribute(): string
    {
        return substr($this->message, 0, 120).(strlen($this->message) > 120 ? '...' : '');
    }
}
