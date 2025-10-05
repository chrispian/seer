<?php

namespace App\Services\Commands\DSL\Steps;

class DatabaseUpdateStep extends Step
{
    public function getType(): string
    {
        return 'database.update';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $with = $config['with'] ?? [];

        $model = $with['model'] ?? null;
        $id = $with['id'] ?? null;
        $data = $with['data'] ?? [];
        $conditions = $with['conditions'] ?? [];

        if (! $model) {
            throw new \InvalidArgumentException('Database update requires a model');
        }

        if (! $id && empty($conditions)) {
            throw new \InvalidArgumentException('Database update requires either id or conditions');
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'would_update' => true,
                'model' => $model,
                'id' => $id,
                'conditions' => $conditions,
                'data' => $data,
            ];
        }

        // Get the model class
        $modelClass = $this->getModelClass($model);
        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown model: {$model}");
        }

        $query = $modelClass::query();

        // Apply conditions
        if ($id) {
            $query->where('id', $id);
        } else {
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }
        }

        // Find the record
        $record = $query->first();
        if (! $record) {
            throw new \InvalidArgumentException("Record not found for model: {$model}");
        }

        // Store original data
        $originalData = $record->only(array_keys($data));

        // Update the record
        $record->update($data);

        // Reload the record
        $record->refresh();

        return [
            'success' => true,
            'model' => $model,
            'id' => $record->id,
            'original_data' => $originalData,
            'updated_data' => $record->only(array_keys($data)),
            'record' => $record->toArray(),
        ];
    }

    protected function getModelClass(string $model): ?string
    {
        // Map model names to actual model classes
        $modelMap = [
            'chat_session' => \App\Models\ChatSession::class,
            'fragment' => \App\Models\Fragment::class,
            'bookmark' => \App\Models\Bookmark::class,
            'user' => \App\Models\User::class,
        ];

        return $modelMap[$model] ?? null;
    }

    public function validate(array $config): bool
    {
        $with = $config['with'] ?? [];

        return isset($with['model']) && isset($with['data']) &&
               (isset($with['id']) || isset($with['conditions']));
    }
}
