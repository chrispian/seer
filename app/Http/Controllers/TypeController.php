<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeController extends Controller
{

    /**
     * Get all available types with metadata
     */
    public function index(): JsonResponse
    {
        try {
            $types = Type::all()->map(function ($type) {
                return [
                    'slug' => $type->slug,
                    'name' => $type->display_name,
                    'description' => $type->description ?? '',
                    'capabilities' => $type->capabilities ?? [],
                    'ui' => [
                        'icon' => $type->icon ?? 'file-text',
                        'color' => $type->color ?? '#6B7280',
                        'display_name' => $type->display_name,
                        'plural_name' => $type->plural_name,
                    ],
                    'hot_fields' => $type->hot_fields ?? [],
                    'storage_type' => $type->storage_type,
                    'is_enabled' => $type->is_enabled,
                    'updated_at' => $type->updated_at,
                ];
            });

            return response()->json([
                'data' => $types,
                'total' => $types->count(),
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
            $type = Type::findBySlug($slug);
            if (! $type) {
                return response()->json([
                    'error' => 'Type not found',
                    'message' => "Type '{$slug}' does not exist",
                ], 404);
            }

            return response()->json([
                'slug' => $slug,
                'display_name' => $type->display_name,
                'plural_name' => $type->plural_name,
                'description' => $type->description,
                'icon' => $type->icon,
                'color' => $type->color,
                'storage_type' => $type->storage_type,
                'model_class' => $type->model_class,
                'schema' => $type->schema,
                'capabilities' => $type->capabilities,
                'hot_fields' => $type->hot_fields,
                'is_enabled' => $type->is_enabled,
                'is_system' => $type->is_system,
                'updated_at' => $type->updated_at,
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
            $type = Type::findBySlug($slug);
            if (! $type || ! $type->schema) {
                return response()->json([
                    'error' => 'Schema not found',
                    'message' => "No schema available for type '{$slug}'",
                ], 404);
            }

            // Basic validation (would use a proper JSON Schema validator in production)
            $schema = $type->schema;
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
            $types = Type::all();
            $typeStats = [];

            foreach ($types as $type) {
                $fragmentCount = $type->isFragmentBacked() 
                    ? \App\Models\Fragment::where('type', $type->slug)->count() 
                    : 0;
                $pendingCount = $type->isFragmentBacked()
                    ? \App\Models\Fragment::where('type', $type->slug)->inInbox()->count()
                    : 0;

                $typeStats[] = [
                    'slug' => $type->slug,
                    'fragments_count' => $fragmentCount,
                    'pending_count' => $pendingCount,
                    'capabilities' => $type->capabilities ?? [],
                    'storage_type' => $type->storage_type,
                    'is_enabled' => $type->is_enabled,
                    'updated_at' => $type->updated_at,
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
            $types = Type::orderBy('is_system', 'desc')
                ->orderBy('slug')
                ->get()
                ->map(function ($type) {
                    $fragmentCount = $type->isFragmentBacked()
                        ? \App\Models\Fragment::where('type', $type->slug)->count()
                        : 0;

                    return [
                        'slug' => $type->slug,
                        'display_name' => $type->display_name,
                        'plural_name' => $type->plural_name,
                        'description' => $type->description,
                        'icon' => $type->icon,
                        'color' => $type->color,
                        'is_enabled' => $type->is_enabled,
                        'is_system' => $type->is_system,
                        'storage_type' => $type->storage_type,
                        'model_class' => $type->model_class,
                        'can_disable' => $type->canBeDisabled(),
                        'can_delete' => $type->canBeDeleted(),
                        'fragments_count' => $fragmentCount,
                        'capabilities' => $type->capabilities,
                        'hot_fields' => $type->hot_fields,
                        'schema' => $type->schema,
                        'updated_at' => $type->updated_at,
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
            $type = Type::findBySlug($slug);

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
            $type = Type::findBySlug($slug);

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
                'schema' => 'sometimes|array|nullable',
                'capabilities' => 'sometimes|array|nullable',
                'hot_fields' => 'sometimes|array|nullable',
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

    /**
     * Create a new type
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|regex:/^[a-z0-9_-]+$/|unique:types_registry,slug',
            'display_name' => 'required|string|max:255',
            'plural_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'storage_type' => 'required|string|in:fragment,model',
            'model_class' => 'nullable|string|required_if:storage_type,model',
            'schema' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'hot_fields' => 'nullable|array',
        ]);

        try {
            $type = Type::create($validated);

            return response()->json([
                'data' => $type,
                'message' => 'Type created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create type',
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    /**
     * Delete a type
     */
    public function destroy(string $slug, Request $request): JsonResponse
    {
        try {
            $type = Type::findBySlug($slug);
            
            if (!$type) {
                return response()->json([
                    'error' => 'Type not found',
                    'message' => "Type '{$slug}' does not exist",
                ], 404);
            }

            if (!$type->canBeDeleted()) {
                return response()->json([
                    'error' => 'Cannot delete type',
                    'message' => 'System types or types with existing fragments cannot be deleted',
                ], 403);
            }

            $deleteFragments = $request->boolean('delete_fragments', false);
            $deletedFragments = 0;

            if ($deleteFragments && $type->isFragmentBacked()) {
                $deletedFragments = $type->fragments()->delete();
            }

            $type->delete();

            return response()->json([
                'message' => 'Type deleted successfully',
                'deleted_fragments' => $deletedFragments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete type',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate type schema with sample data
     */
    public function validateSchema(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'sample_data' => 'required|array',
        ]);

        try {
            $type = Type::findBySlug($slug);
            
            if (!$type || !$type->schema) {
                return response()->json([
                    'error' => 'Schema not found',
                    'message' => "No schema available for type '{$slug}'",
                ], 404);
            }

            // Simple validation - in production would use JSON Schema validator
            $valid = true;
            $errors = [];
            
            if (isset($type->schema['required'])) {
                foreach ($type->schema['required'] as $field) {
                    if (!isset($request->input('sample_data')[$field])) {
                        $valid = false;
                        $errors[] = "Missing required field: {$field}";
                    }
                }
            }

            return response()->json([
                'valid' => $valid,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh type cache
     */
    public function refreshCache(string $slug): JsonResponse
    {
        // Since we're no longer using the old cache system, this is a no-op
        return response()->json([
            'message' => 'Cache refreshed successfully',
        ]);
    }

    /**
     * Get fragments using this type
     */
    public function fragments(string $slug): JsonResponse
    {
        try {
            $type = Type::findBySlug($slug);
            
            if (!$type) {
                return response()->json([
                    'error' => 'Type not found',
                    'message' => "Type '{$slug}' does not exist",
                ], 404);
            }

            if (!$type->isFragmentBacked()) {
                return response()->json([
                    'data' => [],
                    'message' => 'This is a model-backed type',
                ]);
            }

            $fragments = $type->fragments()->paginate(20);

            return response()->json($fragments);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load fragments',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available templates
     */
    public function templates(): JsonResponse
    {
        // Return empty for now since the old template system is deprecated
        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * Create type from template
     */
    public function createFromTemplate(Request $request): JsonResponse
    {
        // This functionality is deprecated with the old type pack system
        return response()->json([
            'error' => 'Feature deprecated',
            'message' => 'Type templates are no longer supported. Use the store endpoint instead.',
        ], 410);
    }
}
