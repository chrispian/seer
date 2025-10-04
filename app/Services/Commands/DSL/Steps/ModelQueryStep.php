<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Bookmark;
use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\VaultRoutingRule;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ModelQueryStep extends Step
{
    protected array $modelMap = [
        'bookmark' => Bookmark::class,
        'chat_session' => ChatSession::class,
        'fragment' => Fragment::class,
        'vault_routing_rule' => VaultRoutingRule::class,
    ];

    public function getType(): string
    {
        return 'model.query';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];
        $model = $with['model'] ?? throw new InvalidArgumentException('Model is required');

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_query' => $model,
                'conditions' => $with['conditions'] ?? [],
                'relations' => $with['relations'] ?? [],
                'order' => $with['order'] ?? null,
                'limit' => $with['limit'] ?? null,
            ];
        }

        $modelClass = $this->getModelClass($model);
        $query = $modelClass::query();

        // Apply conditions safely
        $this->applyConditions($query, $with['conditions'] ?? []);

        // Apply search if provided
        if (! empty($with['search'])) {
            $this->applySearch($query, $with['search'], $model);
        }

        // Apply relationships
        if (! empty($with['relations'])) {
            $query->with($with['relations']);
        }

        // Apply ordering
        $this->applyOrdering($query, $with['order'] ?? null);

        // Apply pagination/limits
        $limit = $with['limit'] ?? 25;
        if ($limit > 0) {
            $query->limit($limit);
        }

        $offset = $with['offset'] ?? 0;
        if ($offset > 0) {
            $query->offset($offset);
        }

        // Execute query
        $results = $query->get();

        // Format results based on model type
        $formattedResults = $this->formatResults($results, $model);

        return [
            'results' => $formattedResults,
            'count' => $results->count(),
            'model' => $model,
            'filters_applied' => [
                'conditions' => $with['conditions'] ?? [],
                'search' => $with['search'] ?? null,
                'limit' => $limit,
                'offset' => $offset,
                'order' => $with['order'] ?? null,
            ],
        ];
    }

    protected function getModelClass(string $model): string
    {
        if (! isset($this->modelMap[$model])) {
            throw new InvalidArgumentException("Unknown model: {$model}");
        }

        return $this->modelMap[$model];
    }

    protected function applyConditions(Builder $query, array $conditions): void
    {
        foreach ($conditions as $condition) {
            if (! is_array($condition) || ! isset($condition['field'])) {
                continue;
            }

            $field = $condition['field'];
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            // Validate field name to prevent SQL injection
            if (! $this->isValidFieldName($field)) {
                throw new InvalidArgumentException("Invalid field name: {$field}");
            }

            // Validate operator
            if (! $this->isValidOperator($operator)) {
                throw new InvalidArgumentException("Invalid operator: {$operator}");
            }

            // Handle special operators that need specific Eloquent methods
            if (in_array(strtoupper($operator), ['IN', 'NOT IN'])) {
                if (!is_array($value)) {
                    throw new InvalidArgumentException("Value for {$operator} operator must be an array");
                }
                
                if (str_contains($field, '.')) {
                    // JSON path IN queries need special handling
                    // Convert field.path notation to field->path for Laravel's JSON syntax
                    $jsonField = str_replace('.', '->', $field);
                    if (strtoupper($operator) === 'IN') {
                        $query->whereJsonContains($jsonField, $value);
                    } else {
                        $query->whereJsonDoesntContain($jsonField, $value);
                    }
                } else {
                    // Standard column IN queries
                    if (strtoupper($operator) === 'IN') {
                        $query->whereIn($field, $value);
                    } else {
                        $query->whereNotIn($field, $value);
                    }
                }
            } elseif (in_array(strtoupper($operator), ['IS NULL', 'IS NOT NULL'])) {
                // Handle NULL checks with proper Eloquent methods
                if (str_contains($field, '.')) {
                    // JSON path NULL checks: Laravel doesn't have dedicated methods for this
                    // Convert field.path notation to field->path for Laravel's JSON syntax
                    $jsonField = str_replace('.', '->', $field);
                    if (strtoupper($operator) === 'IS NULL') {
                        $query->whereNull($jsonField);
                    } else {
                        $query->whereNotNull($jsonField);
                    }
                } else {
                    // Standard column NULL checks
                    if (strtoupper($operator) === 'IS NULL') {
                        $query->whereNull($field);
                    } else {
                        $query->whereNotNull($field);
                    }
                }
            } else {
                // Handle other operators with standard where clauses
                if (str_contains($field, '.')) {
                    // Convert field.path notation to field->path for Laravel's JSON syntax
                    $jsonField = str_replace('.', '->', $field);
                    $query->where($jsonField, $operator, $value);
                } else {
                    $query->where($field, $operator, $value);
                }
            }
        }
    }

    protected function applySearch(Builder $query, string $searchTerm, string $model): void
    {
        switch ($model) {
            case 'fragment':
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('message', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('title', 'LIKE', "%{$searchTerm}%");
                });
                break;
            case 'chat_session':
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('custom_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('short_code', 'LIKE', "%{$searchTerm}%");
                });
                break;
            case 'bookmark':
                $query->where('name', 'LIKE', "%{$searchTerm}%");
                break;
        }
    }

    protected function applyOrdering(Builder $query, mixed $order): void
    {
        if (! $order) {
            $query->latest();
            return;
        }

        // Handle string format like "last_activity_at DESC"
        if (is_string($order)) {
            $parts = explode(' ', trim($order));
            $field = $parts[0];
            $direction = strtolower($parts[1] ?? 'desc');
        } else {
            // Handle array format
            $field = $order['field'] ?? 'created_at';
            $direction = strtolower($order['direction'] ?? 'desc');
        }

        // Validate field name and direction
        if (! $this->isValidFieldName($field)) {
            throw new InvalidArgumentException("Invalid order field: {$field}");
        }

        if (! in_array($direction, ['asc', 'desc'])) {
            throw new InvalidArgumentException("Invalid order direction: {$direction}");
        }

        $query->orderBy($field, $direction);
    }

    protected function formatResults($results, string $model): array
    {
        return $results->map(function ($item) use ($model) {
            $formatted = $item->toArray();

            // Add model-specific formatting
            switch ($model) {
                case 'fragment':
                    $formatted['snippet'] = $this->createSnippet($item->message);
                    break;
                case 'chat_session':
                    $formatted['display_title'] = $item->display_title;
                    $formatted['last_message_preview'] = $item->last_message_preview;
                    break;
                case 'bookmark':
                    $formatted['fragment_count'] = count($item->fragment_ids ?? []);
                    break;
            }

            return $formatted;
        })->all();
    }

    protected function createSnippet(?string $text): string
    {
        if (! $text) {
            return '';
        }

        $cleaned = strip_tags($text);

        return strlen($cleaned) > 150 ? substr($cleaned, 0, 150).'...' : $cleaned;
    }

    protected function isValidFieldName(string $field): bool
    {
        // Allow alphanumeric, underscore, dot (for JSON paths), and common field names
        return preg_match('/^[a-zA-Z0-9_\.]+$/', $field);
    }

    protected function isValidOperator(string $operator): bool
    {
        $allowedOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'];

        return in_array(strtoupper($operator), array_map('strtoupper', $allowedOperators));
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];

        return isset($with['model']) && isset($this->modelMap[$with['model']]);
    }
}
