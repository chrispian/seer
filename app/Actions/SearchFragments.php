<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchFragments
{
    private CalculateSearchRanking $rankingCalculator;

    public function __construct(CalculateSearchRanking $rankingCalculator)
    {
        $this->rankingCalculator = $rankingCalculator;
    }

    public function __invoke(
        string $query,
        ?string $vault = null,
        ?int $projectId = null,
        ?string $sessionId = null,
        int $limit = 20
    ): Collection {
        Log::debug('SearchFragments::invoke()', [
            'query' => $query,
            'vault' => $vault,
            'project_id' => $projectId,
            'session_id' => $sessionId,
        ]);

        // Parse query grammar
        $parsedQuery = $this->parseQueryGrammar($query);

        // Build the search query
        $fragments = $this->buildSearchQuery($parsedQuery, $vault, $projectId)
            ->limit($limit * 2) // Get extra for ranking
            ->get()
            ->map(function (Fragment $fragment) use ($parsedQuery) {
                $this->hydrateTypeRelation($fragment);

                if (! isset($fragment->relevance)) {
                    $fragment->relevance = $this->computeFallbackRelevance($fragment, $parsedQuery['search_terms']);
                }

                return $fragment;
            });

        // Calculate rankings and sort
        $rankedFragments = $fragments->map(function ($fragment) use ($parsedQuery, $sessionId) {
            $fragment->search_score = $this->rankingCalculator->__invoke(
                $fragment,
                $parsedQuery['search_terms'],
                $sessionId
            );

            return $fragment;
        });

        // Sort by score and limit
        return $rankedFragments
            ->sort(function (Fragment $a, Fragment $b) {
                $scoreComparison = $b->search_score <=> $a->search_score;

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                $aCreated = optional($a->created_at)->getTimestamp() ?? 0;
                $bCreated = optional($b->created_at)->getTimestamp() ?? 0;

                return $bCreated <=> $aCreated;
            })
            ->take($limit)
            ->values();
    }

    private function parseQueryGrammar(string $query): array
    {
        $parsed = [
            'search_terms' => '',
            'type' => null,
            'tags' => [],
            'mentions' => [],
            'has_link' => false,
            'has_code' => false,
            'in_session' => null,
            'before_date' => null,
            'after_date' => null,
        ];

        // Extract type:value
        if (preg_match('/type:(\w+)/', $query, $matches)) {
            $parsed['type'] = $matches[1];
            $query = str_replace($matches[0], '', $query);
        }

        // Extract #tags
        if (preg_match_all('/#([\w-]+)/', $query, $matches)) {
            $parsed['tags'] = $matches[1];
            foreach ($matches[0] as $match) {
                $query = str_replace($match, '', $query);
            }
        }

        // Extract @mentions
        if (preg_match_all('/@([\w\-\.]+)/', $query, $matches)) {
            $parsed['mentions'] = $matches[1];
            foreach ($matches[0] as $match) {
                $query = str_replace($match, '', $query);
            }
        }

        // Extract has:link
        if (str_contains($query, 'has:link')) {
            $parsed['has_link'] = true;
            $query = str_replace('has:link', '', $query);
        }

        // Extract has:code
        if (str_contains($query, 'has:code')) {
            $parsed['has_code'] = true;
            $query = str_replace('has:code', '', $query);
        }

        // Extract in:session
        if (preg_match('/in:session\(([^)]+)\)/', $query, $matches)) {
            $parsed['in_session'] = $matches[1];
            $query = str_replace($matches[0], '', $query);
        }

        // Extract before:date
        if (preg_match('/before:(\d{4}-\d{2}-\d{2})/', $query, $matches)) {
            $parsed['before_date'] = $matches[1];
            $query = str_replace($matches[0], '', $query);
        }

        // Extract after:date
        if (preg_match('/after:(\d{4}-\d{2}-\d{2})/', $query, $matches)) {
            $parsed['after_date'] = $matches[1];
            $query = str_replace($matches[0], '', $query);
        }

        // Remaining text is search terms
        $parsed['search_terms'] = trim($query);

        return $parsed;
    }

    private function buildSearchQuery(array $parsedQuery, ?string $vault, ?int $projectId): Builder
    {
        $query = Fragment::query()->select('fragments.*');

        // Apply FULLTEXT search if we have search terms
        if (! empty($parsedQuery['search_terms'])) {
            $searchTerms = $parsedQuery['search_terms'];

            if ($this->usingSqlite()) {
                $this->applySqliteSearch($query, $searchTerms);
            } else {
                $this->applyPostgresSearch($query, $searchTerms);
            }
        } else {
            // When no search terms, add a placeholder relevance score for ranking
            $query->selectRaw('1.0 as relevance');
        }

        // Apply type filter
        if ($parsedQuery['type']) {
            $query->where('type', $parsedQuery['type']);
        }

        // Apply tag filters
        foreach ($parsedQuery['tags'] as $tag) {
            $query->whereJsonContains('tags', $tag);
        }

        // Apply mention filters
        foreach ($parsedQuery['mentions'] as $mention) {
            $query->where(function ($q) use ($mention) {
                $q->whereJsonContains('metadata->people', $mention)
                    ->orWhereJsonContains('parsed_entities->people', $mention);
            });
        }

        // Apply has:link filter
        if ($parsedQuery['has_link']) {
            $query->where(function ($q) {
                $q->whereJsonLength('metadata->urls', '>', 0)
                    ->orWhereJsonLength('parsed_entities->urls', '>', 0)
                    ->orWhereJsonLength('metadata->links', '>', 0)
                    ->orWhereJsonLength('parsed_entities->references', '>', 0);
            });
        }

        // Apply has:code filter
        if ($parsedQuery['has_code']) {
            $query->whereJsonLength('parsed_entities->code_snippets', '>', 0);
        }

        // Apply session filter
        if ($parsedQuery['in_session']) {
            $query->whereJsonContains('metadata->session_id', $parsedQuery['in_session']);
        }

        // Apply date filters
        if ($parsedQuery['before_date']) {
            $query->whereDate('created_at', '<', $parsedQuery['before_date']);
        }

        if ($parsedQuery['after_date']) {
            $query->whereDate('created_at', '>', $parsedQuery['after_date']);
        }

        // Apply vault/project filters
        if ($vault) {
            $query->where('vault', $vault);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        // Include relationships
        $query->with(['category', 'project', 'type']);

        // Add default ordering for non-FULLTEXT queries
        if (empty($parsedQuery['search_terms'])) {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    private function applyPostgresSearch(Builder $query, string $searchTerms): void
    {
        $query->whereRaw(
            "to_tsvector('english', coalesce(title, '') || ' ' || coalesce(message, '')) @@ plainto_tsquery('english', ?)",
            [$searchTerms]
        )->selectRaw(
            "ts_rank(to_tsvector('english', coalesce(title, '') || ' ' || coalesce(message, '')), plainto_tsquery('english', ?)) as relevance",
            [$searchTerms]
        );
    }

    private function applySqliteSearch(Builder $query, string $searchTerms): void
    {
        $terms = collect(preg_split('/\s+/', $searchTerms, -1, PREG_SPLIT_NO_EMPTY));

        $query->where(function (Builder $builder) use ($terms) {
            foreach ($terms as $term) {
                $like = '%'.$this->escapeLike($term).'%';
                $builder->where(function (Builder $subQuery) use ($like) {
                    $subQuery->where('title', 'LIKE', $like)
                        ->orWhere('message', 'LIKE', $like);
                });
            }
        })->selectRaw('1.0 as relevance');
    }

    private function escapeLike(string $term): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $term);
    }

    private function usingSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function computeFallbackRelevance(Fragment $fragment, string $searchTerms): float
    {
        if (empty($searchTerms)) {
            return 1.0;
        }

        $terms = collect(preg_split('/\s+/', strtolower($searchTerms), -1, PREG_SPLIT_NO_EMPTY));
        if ($terms->isEmpty()) {
            return 1.0;
        }

        $haystacks = [
            strtolower((string) $fragment->title),
            strtolower((string) $fragment->message),
        ];

        $matches = 0;
        foreach ($terms as $term) {
            foreach ($haystacks as $text) {
                if ($term !== '' && str_contains($text, $term)) {
                    $matches++;
                    break;
                }
            }
        }

        return max(0.1, min(1.0, $matches / max(1, $terms->count())));
    }

    private function hydrateTypeRelation(Fragment $fragment): void
    {
        if ($fragment->relationLoaded('type') && $fragment->getRelationValue('type')) {
            return;
        }

        $rawType = $fragment->getRawOriginal('type');

        if (! is_string($rawType) || $rawType === '') {
            return;
        }

        $typeModel = Type::findByValue($rawType) ?? new Type(['value' => $rawType]);
        $fragment->setRelation('type', $typeModel);
    }
}
