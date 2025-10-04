<?php

namespace App\Services\Commands\DSL\Steps;

use App\Models\Bookmark;
use App\Models\ChatSession;
use App\Models\Fragment;
use App\Models\VaultRoutingRule;
use InvalidArgumentException;

class ModelCreateStep extends Step
{
    protected array $modelMap = [
        'bookmark' => Bookmark::class,
        'chat_session' => ChatSession::class,
        'fragment' => Fragment::class,
        'vault_routing_rule' => VaultRoutingRule::class,
    ];

    protected array $requiredFields = [
        'bookmark' => ['name'],
        'chat_session' => ['vault_id'],
        'fragment' => ['message'],
        'vault_routing_rule' => ['name', 'match_type', 'target_vault_id'],
    ];

    protected array $fillableFields = [
        'bookmark' => ['name', 'fragment_ids', 'vault_id', 'project_id'],
        'chat_session' => [
            'vault_id', 'project_id', 'title', 'custom_name', 'summary',
            'messages', 'metadata', 'is_active', 'is_pinned', 'model_provider', 'model_name',
        ],
        'fragment' => [
            'message', 'title', 'type', 'tags', 'metadata', 'state', 'vault',
            'project_id', 'importance', 'confidence', 'pinned', 'inbox_status',
        ],
        'vault_routing_rule' => [
            'name', 'match_type', 'match_value', 'conditions', 'target_vault_id',
            'target_project_id', 'scope_vault_id', 'scope_project_id', 'priority',
            'is_active', 'notes',
        ],
    ];

    public function getType(): string
    {
        return 'model.create';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];
        $model = $with['model'] ?? throw new InvalidArgumentException('Model is required');
        $data = $with['data'] ?? throw new InvalidArgumentException('Data is required');

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_create' => $model,
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

        // Filter data to only include fillable fields
        $filteredData = $this->filterFillableData($model, $data);

        // Apply default values based on model type
        $filteredData = $this->applyDefaults($model, $filteredData);

        // Create the record
        $record = $modelClass::create($filteredData);

        // Format the result
        $formattedRecord = $this->formatRecord($record, $model);

        return [
            'success' => true,
            'model' => $model,
            'id' => $record->id,
            'data' => $filteredData,
            'record' => $formattedRecord,
        ];
    }

    protected function getModelClass(string $model): string
    {
        if (! isset($this->modelMap[$model])) {
            throw new InvalidArgumentException("Unknown model: {$model}");
        }

        return $this->modelMap[$model];
    }

    protected function validateData(string $model, array $data): array
    {
        $errors = [];
        $requiredFields = $this->requiredFields[$model] ?? [];

        // Check required fields
        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

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
                break;
            case 'chat_session':
                if (isset($data['vault_id']) && ! is_numeric($data['vault_id'])) {
                    $errors[] = 'vault_id must be numeric';
                }
                if (isset($data['project_id']) && ! is_numeric($data['project_id'])) {
                    $errors[] = 'project_id must be numeric';
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

    protected function applyDefaults(string $model, array $data): array
    {
        switch ($model) {
            case 'fragment':
                if (! isset($data['type'])) {
                    $data['type'] = 'note';
                }
                if (! isset($data['inbox_status'])) {
                    $data['inbox_status'] = 'pending';
                }
                if (! isset($data['tags'])) {
                    $data['tags'] = [];
                }
                if (! isset($data['state'])) {
                    $data['state'] = [];
                }
                break;
            case 'chat_session':
                if (! isset($data['is_active'])) {
                    $data['is_active'] = true;
                }
                if (! isset($data['is_pinned'])) {
                    $data['is_pinned'] = false;
                }
                if (! isset($data['messages'])) {
                    $data['messages'] = [];
                }
                if (! isset($data['metadata'])) {
                    $data['metadata'] = [];
                }
                break;
            case 'bookmark':
                if (! isset($data['fragment_ids'])) {
                    $data['fragment_ids'] = [];
                }
                break;
        }

        return $data;
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

        return true;
    }
}
