<?php

namespace App\Models;

use App\Database\JsonCastingBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

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
        'inbox_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $fragment) {
            if (empty($fragment->type)) {
                $fragment->type = 'log';
            }

            $fragment->tags = self::ensureArray($fragment->tags);
            $fragment->relationships = self::ensureArray($fragment->relationships);
            
            // Set default inbox fields if not specified
            if (!isset($fragment->inbox_status)) {
                $fragment->inbox_status = 'pending';
            }
            if (!isset($fragment->inbox_at)) {
                $fragment->inbox_at = now();
            }
            
            // Validate state against type schema if validation is enabled
            $fragment->validateTypeSchema();
        });

        static::updating(function (self $fragment) {
            // Validate state changes against type schema
            if ($fragment->isDirty('state') || $fragment->isDirty('type')) {
                $fragment->validateTypeSchema();
            }
        });
    }

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
        return $query->where('type', 'todo')
            ->where(function ($q) {
                $q->whereRaw("(state::jsonb->>'status') IN ('open', 'in_progress', 'blocked')")
                    ->orWhere(function ($subq) {
                        $subq->whereNull('state')
                            ->orWhereRaw("(state::jsonb->>'status') IS NULL");
                    });
            });
    }

    public function scopeCompletedTodos($query)
    {
        return $query->where('type', 'todo')
            ->whereRaw("(state::jsonb->>'status') = 'complete'");
    }

    /**
     * Scope for overdue todos
     */
    public function scopeOverdueTodos($query)
    {
        return $query->where('type', 'todo')
            ->whereRaw("(state::jsonb->>'due_at')::timestamp < NOW()")
            ->whereRaw("(state::jsonb->>'status') != 'complete'");
    }

    /**
     * Scope for todos by priority
     */
    public function scopeTodosByPriority($query, string $priority)
    {
        return $query->where('type', 'todo')
            ->whereRaw("(state::jsonb->>'priority') = ?", [$priority]);
    }

    public function scopeTodosByStatus($query, string $status)
    {
        return $status === 'completed'
            ? $query->completedTodos()
            : $query->openTodos();
    }

    // Inbox scopes
    public function scopeInInbox($query)
    {
        return $query->where('inbox_status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('inbox_status', 'accepted');
    }

    public function scopeArchived($query)
    {
        return $query->where('inbox_status', 'archived');
    }

    public function scopeByInboxStatus($query, string $status)
    {
        return $query->where('inbox_status', $status);
    }

    public function scopeInboxSortDefault($query)
    {
        return $query->orderBy('inbox_at', 'desc')
            ->orderBy('type', 'asc')
            ->orderBy('created_at', 'desc');
    }

    public function scopeReviewedBy($query, $userId)
    {
        return $query->where('reviewed_by', $userId);
    }

    // Inbox action methods
    public function acceptInInbox($userId, array $edits = []): bool
    {
        // Apply any edits to the fragment
        if (!empty($edits)) {
            $this->fill($edits);
        }

        // Clear edited_message if it was used for temporary edits
        if (isset($edits['edited_message']) && $edits['edited_message'] === $this->edited_message) {
            $this->edited_message = null;
        }

        // Mark as accepted
        $this->inbox_status = 'accepted';
        $this->reviewed_at = now();
        $this->reviewed_by = $userId;

        return $this->save();
    }

    public function archiveInInbox($userId, string $reason = null): bool
    {
        $this->inbox_status = 'archived';
        $this->reviewed_at = now();
        $this->reviewed_by = $userId;
        
        if ($reason) {
            $this->inbox_reason = $reason;
        }

        return $this->save();
    }

    public function skipInInbox($userId, string $reason = null): bool
    {
        $this->inbox_status = 'skipped';
        $this->reviewed_at = now();
        $this->reviewed_by = $userId;
        
        if ($reason) {
            $this->inbox_reason = $reason;
        }

        return $this->save();
    }

    public function reopenInInbox(): bool
    {
        $this->inbox_status = 'pending';
        $this->reviewed_at = null;
        $this->reviewed_by = null;

        return $this->save();
    }

    public function newEloquentBuilder($query): JsonCastingBuilder
    {
        /** @var QueryBuilder $query */
        return new JsonCastingBuilder($query);
    }

    private static function ensureArray(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        return (array) $value;
    }

    private function formatTitleWithTypePrefix(string $title, bool $hasExplicitHeading = false): string
    {
        if (array_key_exists('title', $this->attributes) && $this->attributes['title'] === $title) {
            return $title;
        }

        $rawType = strtolower((string) ($this->attributes['type'] ?? ''));

        if ($rawType === '') {
            $typeValue = $this->getAttributeValue('type');
            if (is_string($typeValue) && $typeValue !== '') {
                $rawType = strtolower($typeValue);
            }
        }

        if ($rawType === '') {
            $message = strtolower((string) $this->message);
            $tagSet = array_map('strtolower', $this->tags ?? []);

            if (str_contains($message, 'reminder') || str_contains($message, 'task') || in_array('urgent', $tagSet, true)) {
                $rawType = 'task';
            } else {
                return $title;
            }
        }

        if ($rawType === 'note' && $hasExplicitHeading) {
            return $title;
        }

        $normalized = Str::title($rawType);

        if (Str::startsWith(Str::lower($title), Str::lower($normalized))) {
            return $title;
        }

        return $normalized.': '.$title;
    }

    public function getTitleAttribute($value): string
    {
        $hasExplicitHeading = str_contains((string) $this->message, "\n");

        if (is_string($value) && $value !== '') {
            return $this->formatTitleWithTypePrefix($value, $hasExplicitHeading);
        }

        // Try to extract a title from the message
        $lines = explode("\n", (string) $this->message);
        $firstLine = trim($lines[0] ?? '');

        // If first line looks like a title (short, no periods at end)
        if ($firstLine !== '' && strlen($firstLine) <= 50 && ! str_ends_with($firstLine, '.')) {
            return $this->formatTitleWithTypePrefix($firstLine, $hasExplicitHeading);
        }

        // Otherwise extract first sentence
        $sentences = explode('.', (string) $this->message);
        $firstSentence = trim($sentences[0] ?? '');

        if ($firstSentence !== '' && strlen($firstSentence) <= 60) {
            return $this->formatTitleWithTypePrefix($firstSentence, $hasExplicitHeading);
        }

        // Fallback to truncated message
        $message = (string) $this->message;

        return $this->formatTitleWithTypePrefix(substr($message, 0, 50).(strlen($message) > 50 ? '...' : ''), $hasExplicitHeading);
    }

    public function getPreviewAttribute(): string
    {
        return substr($this->message, 0, 120).(strlen($this->message) > 120 ? '...' : '');
    }

    public function getBodyAttribute(): string
    {
        return $this->edited_message ?? $this->message ?? '';
    }

    /**
     * Validate fragment state against type schema
     */
    protected function validateTypeSchema(): void
    {
        // Skip validation if disabled or no type/state
        if (!config('fragments.types.validation.enabled') || !$this->type || !$this->state) {
            return;
        }

        try {
            $validator = app(\App\Services\TypeSystem\TypePackValidator::class);
            $this->state = $validator->validateFragmentState($this->state, $this->type);
        } catch (\Exception $e) {
            // In strict mode, throw the exception
            if (config('fragments.types.validation.strict_mode')) {
                throw $e;
            }
            
            // Otherwise, log the error and continue
            \Log::warning('Fragment type validation failed', [
                'fragment_id' => $this->id,
                'type' => $this->type,
                'error' => $e->getMessage(),
                'state' => $this->state,
            ]);
        }
    }

    /**
     * Check if fragment state is valid for its type
     */
    public function hasValidState(): bool
    {
        if (!$this->type || !$this->state) {
            return true; // No validation needed
        }

        try {
            $validator = app(\App\Services\TypeSystem\TypePackValidator::class);
            return $validator->isValidState($this->state, $this->type);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get validation errors for current state
     */
    public function getStateValidationErrors(): array
    {
        if (!$this->type || !$this->state) {
            return [];
        }

        try {
            $validator = app(\App\Services\TypeSystem\TypePackValidator::class);
            return $validator->getValidationErrors($this->state, $this->type);
        } catch (\Exception $e) {
            return ['general' => [$e->getMessage()]];
        }
    }
}
