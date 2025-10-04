<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Fragment;

class FragmentQueryStep extends Step
{
    public function getType(): string
    {
        return 'fragment.query';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_query' => true,
                'filters' => $config['with'] ?? [],
            ];
        }

        // Build query
        $query = Fragment::query();

        // Apply basic filters
        $with = $config['with'] ?? [];
        
        if (isset($with['type'])) {
            $query->where('type', $with['type']);
        }

        // Apply JSON state filters
        if (isset($with['filters']) && is_array($with['filters'])) {
            foreach ($with['filters'] as $key => $value) {
                if (str_contains($key, '.')) {
                    // Handle nested JSON paths like 'state.status'
                    $query->whereJsonPath($key, '=', $value);
                } else {
                    // Handle regular column filters
                    $query->where($key, $value);
                }
            }
        }

        // Apply search filter
        if (isset($with['search']) && !empty($with['search'])) {
            $searchTerm = $with['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('message', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('title', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply tag filters
        if (isset($with['tags']) && is_array($with['tags'])) {
            foreach ($with['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Load relationships
        if (isset($with['with_relations']) && is_array($with['with_relations'])) {
            $query->with($with['with_relations']);
        } else {
            // Default relationship loading
            $query->with('type');
        }

        // Apply ordering
        $order = $with['order'] ?? 'latest';
        switch ($order) {
            case 'latest':
                $query->latest();
                break;
            case 'oldest':
                $query->oldest();
                break;
            case 'updated':
                $query->latest('updated_at');
                break;
            default:
                $query->latest();
        }

        // Apply limit
        $limit = (int) ($with['limit'] ?? 25);
        if ($limit > 0) {
            $query->limit($limit);
        }

        // Execute query
        $results = $query->get();

        // Format results
        $formattedResults = $results->map(function (Fragment $fragment) {
            return [
                'id' => $fragment->id,
                'message' => $fragment->message,
                'title' => $fragment->title,
                'type' => [
                    'name' => $fragment->type?->label ?? ucfirst($fragment->type?->value ?? 'fragment'),
                    'value' => $fragment->type?->value ?? 'fragment',
                ],
                'tags' => $fragment->tags ?? [],
                'state' => $fragment->state ?? [],
                'created_at' => $fragment->created_at,
                'updated_at' => $fragment->updated_at,
                'snippet' => $this->createSnippet($fragment->message),
            ];
        })->all();

        return [
            'results' => $formattedResults,
            'count' => $results->count(),
            'total_count' => $results->count(), // Could be enhanced with separate count query
            'filters_applied' => [
                'type' => $with['type'] ?? null,
                'search' => $with['search'] ?? null,
                'tags' => $with['tags'] ?? [],
                'limit' => $limit,
                'order' => $order,
            ],
        ];
    }

    public function validate(array $config): bool
    {
        // Basic validation - 'with' parameter should be present
        return isset($config['with']) && is_array($config['with']);
    }

    private function createSnippet(?string $message): string
    {
        if (!$message) {
            return '';
        }

        $cleaned = strip_tags($message);
        return strlen($cleaned) > 150 ? substr($cleaned, 0, 150) . '...' : $cleaned;
    }
}