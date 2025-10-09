<?php

namespace App\Http\Controllers;

use App\Models\FragmentTypeRegistry;
use App\Services\TypeSystem\TypePackLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function __construct(
        protected TypePackLoader $typePackLoader
    ) {}

    /**
     * Get all available types with metadata
     */
    public function index(): JsonResponse
    {
        try {
            $registryEntries = FragmentTypeRegistry::all();
            $types = [];

            foreach ($registryEntries as $entry) {
                $typePack = $this->typePackLoader->loadTypePack($entry->slug);

                if ($typePack) {
                    $types[] = [
                        'slug' => $entry->slug,
                        'name' => $typePack['manifest']['name'] ?? $entry->slug,
                        'description' => $typePack['manifest']['description'] ?? '',
                        'version' => $entry->version,
                        'capabilities' => $entry->getCapabilities(),
                        'ui' => $typePack['manifest']['ui'] ?? [
                            'icon' => 'file-text',
                            'color' => '#6B7280',
                            'display_name' => ucfirst($entry->slug),
                            'plural_name' => ucfirst($entry->slug).'s',
                        ],
                        'hot_fields' => $entry->getHotFields(),
                        'schema_hash' => $entry->schema_hash,
                        'updated_at' => $entry->updated_at,
                    ];
                }
            }

            return response()->json([
                'data' => $types,
                'total' => count($types),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load types',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific type
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $registryEntry = FragmentTypeRegistry::findBySlug($slug);
            if (! $registryEntry) {
                return response()->json([
                    'error' => 'Type not found',
                    'message' => "Type '{$slug}' does not exist",
                ], 404);
            }

            $typePack = $this->typePackLoader->loadTypePack($slug);
            if (! $typePack) {
                return response()->json([
                    'error' => 'Type pack not loaded',
                    'message' => "Failed to load type pack for '{$slug}'",
                ], 500);
            }

            return response()->json([
                'slug' => $slug,
                'manifest' => $typePack['manifest'],
                'schema' => $typePack['schema'] ?? null,
                'indexes' => $typePack['indexes'] ?? null,
                'registry' => [
                    'version' => $registryEntry->version,
                    'source_path' => $registryEntry->source_path,
                    'schema_hash' => $registryEntry->schema_hash,
                    'hot_fields' => $registryEntry->getHotFields(),
                    'capabilities' => $registryEntry->getCapabilities(),
                    'updated_at' => $registryEntry->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load type details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate fragment state against type schema
     */
    public function validate(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'state' => 'required|array',
        ]);

        try {
            $typePack = $this->typePackLoader->loadTypePack($slug);
            if (! $typePack || ! isset($typePack['schema'])) {
                return response()->json([
                    'error' => 'Schema not found',
                    'message' => "No schema available for type '{$slug}'",
                ], 404);
            }

            // Basic validation (would use a proper JSON Schema validator in production)
            $schema = $typePack['schema'];
            $state = $validated['state'];
            $errors = [];

            // Check required fields
            if (isset($schema['required'])) {
                foreach ($schema['required'] as $requiredField) {
                    if (! isset($state[$requiredField])) {
                        $errors[] = "Missing required field: {$requiredField}";
                    }
                }
            }

            // Check enum values
            if (isset($schema['properties'])) {
                foreach ($schema['properties'] as $field => $fieldSchema) {
                    if (isset($state[$field]) && isset($fieldSchema['enum'])) {
                        if (! in_array($state[$field], $fieldSchema['enum'])) {
                            $errors[] = "Invalid value for {$field}: must be one of ".implode(', ', $fieldSchema['enum']);
                        }
                    }
                }
            }

            return response()->json([
                'valid' => empty($errors),
                'errors' => $errors,
                'state' => $state,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get type statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $registryEntries = FragmentTypeRegistry::all();
            $typeStats = [];

            foreach ($registryEntries as $entry) {
                $fragmentCount = \App\Models\Fragment::where('type', $entry->slug)->count();
                $pendingCount = \App\Models\Fragment::where('type', $entry->slug)->inInbox()->count();

                $typeStats[] = [
                    'slug' => $entry->slug,
                    'fragments_count' => $fragmentCount,
                    'pending_count' => $pendingCount,
                    'capabilities' => $entry->getCapabilities(),
                    'version' => $entry->version,
                    'updated_at' => $entry->updated_at,
                ];
            }

            return response()->json([
                'data' => $typeStats,
                'total_types' => count($typeStats),
                'total_fragments' => array_sum(array_column($typeStats, 'fragments_count')),
                'total_pending' => array_sum(array_column($typeStats, 'pending_count')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load type statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function admin(): JsonResponse
    {
        try {
            $types = FragmentTypeRegistry::orderBy('is_system', 'desc')
                ->orderBy('slug')
                ->get()
                ->map(function ($type) {
                    $fragmentCount = \App\Models\Fragment::where('type', $type->slug)->count();

                    return [
                        'slug' => $type->slug,
                        'display_name' => $type->display_name,
                        'plural_name' => $type->plural_name,
                        'description' => $type->description,
                        'icon' => $type->icon,
                        'color' => $type->color,
                        'is_enabled' => $type->is_enabled,
                        'is_system' => $type->is_system,
                        'hide_from_admin' => $type->hide_from_admin,
                        'can_disable' => $type->canBeDisabled(),
                        'can_delete' => $type->canBeDeleted(),
                        'fragments_count' => $fragmentCount,
                        'version' => $type->version,
                        'pagination_default' => $type->pagination_default,
                        'list_columns' => $type->list_columns,
                        'filters' => $type->filters,
                        'actions' => $type->actions,
                        'default_sort' => $type->default_sort,
                        'container_component' => $type->container_component,
                        'row_display_mode' => $type->row_display_mode,
                        'detail_component' => $type->detail_component,
                        'detail_fields' => $type->detail_fields,
                    ];
                });

            return response()->json([
                'data' => $types,
                'total' => $types->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load admin types',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggle(string $slug): JsonResponse
    {
        try {
            $type = FragmentTypeRegistry::findBySlug($slug);

            if (! $type) {
                return response()->json([
                    'error' => 'Type not found',
                    'message' => "Type '{$slug}' does not exist",
                ], 404);
            }

            if (! $type->canBeDisabled()) {
                return response()->json([
                    'error' => 'Cannot disable type',
                    'message' => 'System types cannot be disabled',
                ], 403);
            }

            $type->is_enabled = ! $type->is_enabled;
            $type->save();

            return response()->json([
                'slug' => $type->slug,
                'is_enabled' => $type->is_enabled,
                'message' => $type->is_enabled ? 'Type enabled' : 'Type disabled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to toggle type',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        try {
            $type = FragmentTypeRegistry::findBySlug($slug);

            if (! $type) {
                return response()->json([
                    'error' => 'Type not found',
                    'message' => "Type '{$slug}' does not exist",
                ], 404);
            }

            $validated = $request->validate([
                'display_name' => 'sometimes|string|max:255',
                'plural_name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string|nullable',
                'icon' => 'sometimes|string|nullable',
                'color' => 'sometimes|string|nullable',
                'pagination_default' => 'sometimes|integer|min:10|max:500',
                'container_component' => 'sometimes|string|in:DataManagementModal,Dialog,Drawer',
                'row_display_mode' => 'sometimes|string|in:list,grid,card',
                'detail_component' => 'sometimes|string|nullable|in:UnifiedDetailModal,Dialog,Drawer',
                'detail_fields' => 'sometimes|array|nullable',
            ]);

            $type->update($validated);

            return response()->json([
                'slug' => $type->slug,
                'message' => 'Type updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update type',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
