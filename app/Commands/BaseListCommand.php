<?php

namespace App\Commands;

use App\Models\FragmentTypeRegistry;

/**
 * Base List Command
 * 
 * Provides standardized list command functionality that reads
 * configuration from the fragment_type_registry database table.
 * 
 * Eliminates boilerplate by handling:
 * - Type registry lookup
 * - Component routing
 * - Data fetching with configurable pagination
 * - Standard response format
 */
abstract class BaseListCommand extends BaseCommand
{
    /**
     * Get the fragment type slug this command handles
     */
    abstract protected function getTypeSlug(): string;

    /**
     * Get the data for this type (override for custom queries)
     */
    protected function getData(FragmentTypeRegistry $typeConfig): array
    {
        $limit = $typeConfig->pagination_default ?? 50;
        
        $fragments = \App\Models\Fragment::where('type', $typeConfig->slug)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($fragment) {
                return [
                    'id' => $fragment->id,
                    'title' => $fragment->title,
                    'message' => $fragment->message,
                    'type' => $fragment->type,
                    'metadata' => $fragment->metadata,
                    'created_at' => $fragment->created_at?->toISOString(),
                    'updated_at' => $fragment->updated_at?->toISOString(),
                    'created_human' => $fragment->created_at?->diffForHumans(),
                    'preview' => \Illuminate\Support\Str::limit($fragment->message, 150),
                ];
            })
            ->all();

        return $fragments;
    }

    public function handle(): array
    {
        $typeConfig = FragmentTypeRegistry::findBySlug($this->getTypeSlug());

        if (!$typeConfig) {
            return [
                'type' => 'error',
                'component' => 'ErrorModal',
                'data' => [
                    'message' => "Type configuration not found: {$this->getTypeSlug()}",
                ],
            ];
        }

        if (!$typeConfig->is_enabled) {
            return [
                'type' => 'error',
                'component' => 'ErrorModal',
                'data' => [
                    'message' => "Type '{$typeConfig->display_name}' is currently disabled.",
                ],
            ];
        }

        $data = $this->getData($typeConfig);

        return [
            'type' => $typeConfig->slug,
            'component' => $this->getComponentName($typeConfig),
            'data' => [
                'items' => $data,
                'typeConfig' => [
                    'slug' => $typeConfig->slug,
                    'display_name' => $typeConfig->display_name,
                    'plural_name' => $typeConfig->plural_name,
                    'icon' => $typeConfig->icon,
                    'color' => $typeConfig->color,
                    'container_component' => $typeConfig->container_component,
                    'row_display_mode' => $typeConfig->row_display_mode,
                    'list_columns' => $typeConfig->list_columns,
                    'filters' => $typeConfig->filters,
                    'actions' => $typeConfig->actions,
                    'default_sort' => $typeConfig->default_sort,
                    'pagination_default' => $typeConfig->pagination_default,
                    'detail_component' => $typeConfig->detail_component,
                    'detail_fields' => $typeConfig->detail_fields,
                ],
            ],
        ];
    }

    /**
     * Get the component name based on type config
     * Override if you need custom component routing
     */
    protected function getComponentName(FragmentTypeRegistry $typeConfig): string
    {
        // For now, use UnifiedListModal for all types
        // Later we can make this dynamic based on container_component
        return 'UnifiedListModal';
    }
}
