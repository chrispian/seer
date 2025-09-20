<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fragment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'project_id' => 'integer',
        'pinned' => 'boolean',
        'importance' => 'integer',
        'confidence' => 'integer',
        'tags' => 'array',
        'relationships' => 'array',
        'metadata' => 'array',
        'parsed_entities' => 'array',
        'selection_stats' => 'array',
        'state' => 'array',
        'state_json' => 'array',
        'deleted_at' => 'datetime',
        'hash_bucket' => 'integer',
        'model_provider' => 'string',
        'model_name' => 'string',
    ];

    // Relationships
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    // Override type access to return relationship when loaded
    public function __get($key)
    {
        if ($key === 'type' && $this->relationLoaded('type')) {
            return $this->getRelation('type');
        }

        return parent::__get($key);
    }

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

    // FULLTEXT search scope
    public function scopeFulltextSearch($query, string $searchTerm)
    {
        return $query->whereRaw(
            'MATCH(title, message) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$searchTerm]
        )->selectRaw(
            '*, MATCH(title, message) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance',
            [$searchTerm]
        )->orderByDesc('relevance');
    }

    // Boolean mode FULLTEXT search for exact matches
    public function scopeFulltextSearchBoolean($query, string $searchTerm)
    {
        // Prepare search term for boolean mode (add + for required words)
        $booleanTerm = collect(explode(' ', $searchTerm))
            ->map(fn ($word) => '+'.$word)
            ->implode(' ');

        return $query->whereRaw(
            'MATCH(title, message) AGAINST(? IN BOOLEAN MODE)',
            [$booleanTerm]
        );
    }

    // Search by tags
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    // Search by multiple tags (AND)
    public function scopeWithAllTags($query, array $tags)
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }

        return $query;
    }

    // Search by multiple tags (OR)
    public function scopeWithAnyTag($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    // Search by entity presence
    public function scopeHasEntity($query, string $entityType)
    {
        return $query->whereJsonLength("parsed_entities->{$entityType}", '>', 0);
    }

    // Search by person mention
    public function scopeWithMention($query, string $person)
    {
        return $query->where(function ($q) use ($person) {
            $q->whereJsonContains('metadata->people', $person)
                ->orWhereJsonContains('parsed_entities->people', $person);
        });
    }

    // Date range scopes
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Session scope
    public function scopeInSession($query, string $sessionId)
    {
        return $query->whereJsonContains('metadata->session_id', $sessionId);
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

    // Todo-specific scopes
    public function scopeTodos($query)
    {
        $todoType = \App\Models\Type::where('value', 'todo')->first();

        return $todoType ? $query->where('type_id', $todoType->id) : $query->whereRaw('1 = 0');
    }

    public function scopeOpenTodos($query)
    {
        $todoType = \App\Models\Type::where('value', 'todo')->first();
        if (! $todoType) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('type_id', $todoType->id)
            ->where(function ($q) {
                $q->whereRaw("(state::jsonb->>'status') = ?", ['open'])
                    ->orWhereNull('state')
                    ->orWhere(function ($subq) {
                        $subq->whereNotNull('state')
                            ->whereRaw("(state::jsonb->>'status') IS NULL");
                    });
            });
    }

    public function scopeCompletedTodos($query)
    {
        $todoType = \App\Models\Type::where('value', 'todo')->first();
        if (! $todoType) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('type_id', $todoType->id)
            ->whereRaw("(state::jsonb->>'status') = ?", ['complete']);
    }

    public function scopeTodosByStatus($query, string $status)
    {
        return $status === 'completed'
            ? $query->completedTodos()
            : $query->openTodos();
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

    public function getBodyAttribute(): string
    {
        return $this->edited_message ?? $this->message ?? '';
    }
}
