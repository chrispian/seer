<?php

namespace App\Commands;

use App\Commands\Concerns\FormatsListData;
use App\Commands\Concerns\HandlesModelQueries;
use App\Models\Fragment;

/**
 * Base List Command
 *
 * Provides standardized list command functionality using the unified
 * Type + Command system.
 *
 * Handles both model-backed and fragment-backed types automatically.
 * Component routing comes from Command UI config.
 */
abstract class BaseListCommand extends BaseCommand
{
    use HandlesModelQueries, FormatsListData;

    /**
     * Get the data for this type
     * Override for custom query logic
     */
    protected function getData(): array
    {
        if (! $this->type) {
            throw new \RuntimeException('Type model not set. Ensure Command is injected.');
        }

        if ($this->type->storage_type === 'model') {
            return $this->getModelData();
        }

        return $this->getFragmentData();
    }

    /**
     * Get data for model-backed types
     */
    protected function getModelData(): array
    {
        $modelClass = $this->type->model_class;
        
        if (! class_exists($modelClass)) {
            throw new \RuntimeException("Model class not found: {$modelClass}");
        }

        $items = $this->queryModel($modelClass);

        return $items->map(fn ($item) => $this->formatListItem($item))->all();
    }

    /**
     * Get data for fragment-backed types
     */
    protected function getFragmentData(): array
    {
        $limit = $this->command?->pagination_default ?? 50;

        $fragments = Fragment::where('type', $this->type->slug)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $fragments->map(fn ($f) => $this->formatFragmentItem($f))->all();
    }

    public function handle(): array
    {
        if (! $this->type) {
            return $this->respond([
                'error' => 'Type configuration not available',
                'message' => 'Command not properly configured. Type model missing.',
            ]);
        }

        if (! $this->type->is_enabled) {
            return $this->respond([
                'error' => 'Type disabled',
                'message' => "Type '{$this->type->display_name}' is currently disabled.",
            ]);
        }

        $data = $this->getData();

        return $this->respond(['items' => $data]);
    }
}
