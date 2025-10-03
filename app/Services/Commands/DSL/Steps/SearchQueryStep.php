<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Fragment;

class SearchQueryStep extends Step
{
    public function getType(): string
    {
        return 'search.query';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $query = $config['query'] ?? '';
        $limit = $config['limit'] ?? 10;
        $type = $config['type'] ?? null;
        $tags = $config['tags'] ?? [];

        if (!$query) {
            throw new \InvalidArgumentException('Search query step requires a query');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'query' => $query,
                'limit' => $limit,
                'would_search' => true,
            ];
        }

        try {
            $fragmentsQuery = Fragment::query();

            // Apply fulltext search
            $fragmentsQuery->fulltextSearch($query);

            // Filter by type if specified
            if ($type) {
                $fragmentsQuery->where('type', $type);
            }

            // Filter by tags if specified
            if (!empty($tags)) {
                $fragmentsQuery->withAllTags($tags);
            }

            $fragments = $fragmentsQuery
                ->select(['id', 'type', 'title', 'message', 'created_at', 'tags'])
                ->limit($limit)
                ->get();

            return [
                'results' => $fragments->map(function ($fragment) {
                    return [
                        'id' => $fragment->id,
                        'type' => $fragment->type,
                        'title' => $fragment->title,
                        'preview' => substr($fragment->message, 0, 200),
                        'created_at' => $fragment->created_at->toISOString(),
                        'tags' => $fragment->tags,
                    ];
                })->toArray(),
                'count' => $fragments->count(),
                'query' => $query,
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException("Search failed: {$e->getMessage()}");
        }
    }

    public function validate(array $config): bool
    {
        return isset($config['query']);
    }
}