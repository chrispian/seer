<?php

namespace App\Actions;

use App\Models\Fragment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
            ->get();

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
            ->sortByDesc('search_score')
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
        $query = Fragment::query();

        // Apply FULLTEXT search if we have search terms
        if (! empty($parsedQuery['search_terms'])) {
            $searchTerms = $parsedQuery['search_terms'];

            // Use FULLTEXT search with relevance score
            $query->whereRaw(
                'MATCH(title, message) AGAINST(? IN NATURAL LANGUAGE MODE)',
                [$searchTerms]
            )->selectRaw(
                '*, MATCH(title, message) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance',
                [$searchTerms]
            );
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
        $query->with(['category', 'project']);

        return $query;
    }
}
