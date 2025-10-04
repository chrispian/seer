<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Bookmark;
use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\VaultRoutingRule;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ModelDeleteStep extends Step
{
    protected array $modelMap = [
        'bookmark' => Bookmark::class,
        'chat_session' => ChatSession::class,
        'fragment' => Fragment::class,
        'vault_routing_rule' => VaultRoutingRule::class,
    ];

    public function getType(): string
    {
        return 'model.delete';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];
        $model = $with['model'] ?? throw new InvalidArgumentException('Model is required');

        // Either ID or conditions are required
        $id = $with['id'] ?? null;
        $conditions = $with['conditions'] ?? [];
        $softDelete = $with['soft_delete'] ?? true; // Default to soft delete for safety

        if (! $id && empty($conditions)) {
            throw new InvalidArgumentException('Either id or conditions must be provided');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_delete' => $model,
                'id' => $id,
                'conditions' => $conditions,
                'soft_delete' => $softDelete,
            ];
        }

        $modelClass = $this->getModelClass($model);
        $query = $modelClass::query();

        // Apply search criteria
        if ($id) {
            $query->where('id', $id);
        } else {
            $this->applyConditions($query, $conditions);
        }

        // Find records to delete
        $records = $query->get();

        if ($records->isEmpty()) {
            throw new InvalidArgumentException("No records found to delete for model: {$model}");
        }

        // Store records before deletion for response
        $deletedRecords = $records->map(function ($record) use ($model) {
            return $this->formatRecord($record, $model);
        })->all();

        $deleteCount = 0;

        foreach ($records as $record) {
            try {
                if ($softDelete && method_exists($record, 'delete')) {
                    // Use soft delete (which is the default behavior for models with SoftDeletes trait)
                    $record->delete();
                } else {
                    // Force delete or hard delete
                    if (method_exists($record, 'forceDelete')) {
                        $record->forceDelete();
                    } else {
                        $record->delete();
                    }
                }
                $deleteCount++;
            } catch (\Exception $e) {
                // Log error but continue with other records
                \Log::error("Failed to delete {$model} record {$record->id}: {$e->getMessage()}");
            }
        }

        return [
            'success' => true,
            'model' => $model,
            'deleted_count' => $deleteCount,
            'soft_delete' => $softDelete,
            'deleted_records' => $deletedRecords,
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

            // Handle special JSON path queries
            if (str_contains($field, '.')) {
                $query->whereJsonPath($field, $operator, $value);
            } else {
                $query->where($field, $operator, $value);
            }
        }
    }

    protected function formatRecord($record, string $model): array
    {
        $formatted = $record->toArray();

        // Add model-specific formatting
        switch ($model) {
            case 'fragment':
                $formatted['snippet'] = $this->createSnippet($record->message);
                break;
            case 'chat_session':
                $formatted['display_title'] = $record->display_title;
                $formatted['channel_display'] = $record->channel_display;
                break;
            case 'bookmark':
                $formatted['fragment_count'] = count($record->fragment_ids ?? []);
                break;
        }

        return $formatted;
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

        if (! isset($with['model']) || ! isset($this->modelMap[$with['model']])) {
            return false;
        }

        $id = $with['id'] ?? null;
        $conditions = $with['conditions'] ?? [];

        return $id !== null || ! empty($conditions);
    }
}
