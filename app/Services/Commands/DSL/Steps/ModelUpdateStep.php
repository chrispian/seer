<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Bookmark;
use App\Models\ChatSession;
use App\Models\Fragment;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ModelUpdateStep extends Step
{
    protected array $modelMap = [
        'bookmark' => Bookmark::class,
        'chat_session' => ChatSession::class,
        'fragment' => Fragment::class,
    ];

    protected array $fillableFields = [
        'bookmark' => ['name', 'fragment_ids', 'vault_id', 'project_id'],
        'chat_session' => [
            'title', 'custom_name', 'summary', 'messages', 'metadata',
            'is_active', 'is_pinned', 'sort_order', 'model_provider', 'model_name',
        ],
        'fragment' => [
            'message', 'title', 'type', 'tags', 'metadata', 'state',
            'importance', 'confidence', 'pinned', 'inbox_status',
        ],
    ];

    public function getType(): string
    {
        return 'model.update';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];
        $model = $with['model'] ?? throw new InvalidArgumentException('Model is required');
        $data = $with['data'] ?? throw new InvalidArgumentException('Data is required');

        // Either ID or conditions are required
        $id = $with['id'] ?? null;
        $conditions = $with['conditions'] ?? [];

        if (! $id && empty($conditions)) {
            throw new InvalidArgumentException('Either id or conditions must be provided');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_update' => $model,
                'id' => $id,
                'conditions' => $conditions,
                'data' => $data,
                'validation' => $this->validateData($model, $data),
            ];
        }

        // Validate data
        $validation = $this->validateData($model, $data);
        if (! $validation['valid']) {
            throw new InvalidArgumentException('Validation failed: '.implode(', ', $validation['errors']));
        }

        $modelClass = $this->getModelClass($model);
        $query = $modelClass::query();

        // Apply search criteria
        if ($id) {
            $query->where('id', $id);
        } else {
            $this->applyConditions($query, $conditions);
        }

        // Find records to update
        $records = $query->get();

        if ($records->isEmpty()) {
            throw new InvalidArgumentException("No records found to update for model: {$model}");
        }

        // Filter data to only include fillable fields
        $filteredData = $this->filterFillableData($model, $data);

        $updatedRecords = [];
        $originalData = [];

        foreach ($records as $record) {
            // Store original data
            $originalData[$record->id] = $record->only(array_keys($filteredData));

            // Update the record
            $record->update($filteredData);

            // Reload and format the record
            $record->refresh();
            $updatedRecords[] = $this->formatRecord($record, $model);
        }

        return [
            'success' => true,
            'model' => $model,
            'updated_count' => count($updatedRecords),
            'records' => $updatedRecords,
            'original_data' => $originalData,
            'updated_data' => $filteredData,
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
                    if (strtoupper($operator) === 'IN') {
                        $query->whereJsonContains($field, $value);
                    } else {
                        $query->whereJsonDoesntContain($field, $value);
                    }
                } else {
                    // Standard column IN queries
                    if (strtoupper($operator) === 'IN') {
                        $query->whereIn($field, $value);
                    } else {
                        $query->whereNotIn($field, $value);
                    }
                }
            } else {
                // Handle other operators with standard where clauses
                if (str_contains($field, '.')) {
                    $query->whereJsonPath($field, $operator, $value);
                } else {
                    $query->where($field, $operator, $value);
                }
            }
        }
    }

    protected function validateData(string $model, array $data): array
    {
        $errors = [];

        // Model-specific validation
        switch ($model) {
            case 'fragment':
                if (isset($data['type']) && ! $this->isValidFragmentType($data['type'])) {
                    $errors[] = "Invalid fragment type: {$data['type']}";
                }
                if (isset($data['importance']) && (! is_numeric($data['importance']) || $data['importance'] < 1 || $data['importance'] > 5)) {
                    $errors[] = 'Importance must be between 1 and 5';
                }
                if (isset($data['confidence']) && (! is_numeric($data['confidence']) || $data['confidence'] < 1 || $data['confidence'] > 5)) {
                    $errors[] = 'Confidence must be between 1 and 5';
                }
                if (isset($data['inbox_status']) && ! in_array($data['inbox_status'], ['pending', 'accepted', 'archived', 'skipped'])) {
                    $errors[] = "Invalid inbox_status: {$data['inbox_status']}";
                }
                break;
            case 'chat_session':
                if (isset($data['is_active']) && ! is_bool($data['is_active'])) {
                    $errors[] = 'is_active must be boolean';
                }
                if (isset($data['is_pinned']) && ! is_bool($data['is_pinned'])) {
                    $errors[] = 'is_pinned must be boolean';
                }
                if (isset($data['sort_order']) && ! is_numeric($data['sort_order'])) {
                    $errors[] = 'sort_order must be numeric';
                }
                break;
            case 'bookmark':
                if (isset($data['fragment_ids']) && ! is_array($data['fragment_ids'])) {
                    $errors[] = 'fragment_ids must be an array';
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    protected function filterFillableData(string $model, array $data): array
    {
        $fillableFields = $this->fillableFields[$model] ?? [];

        return array_intersect_key($data, array_flip($fillableFields));
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

    protected function isValidFragmentType(string $type): bool
    {
        $validTypes = ['note', 'todo', 'log', 'meeting', 'contact', 'link', 'file', 'calendar_event'];

        return in_array($type, $validTypes);
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];

        if (! isset($with['model']) || ! isset($this->modelMap[$with['model']])) {
            return false;
        }

        if (! isset($with['data']) || ! is_array($with['data'])) {
            return false;
        }

        $id = $with['id'] ?? null;
        $conditions = $with['conditions'] ?? [];

        return $id !== null || ! empty($conditions);
    }
}
