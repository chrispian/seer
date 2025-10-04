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
}
