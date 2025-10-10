<?php

declare(strict_types=1);

namespace App\Services\TypeSystem;

use App\Models\FragmentTypeRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * Type Pack Manager - CRUD operations for fragment type packs.
 *
 * Handles creation, updating, and deletion of type packs including:
 * - Directory structure management
 * - Manifest (type.yaml) editing
 * - Schema (state.schema.json) management
 * - Index metadata (indexes.yaml) management
 * - Registry synchronization
 * - Cache invalidation
 *
 * Type packs are stored in: fragments/types/{slug}/
 *
 * @see TypePackLoader For reading/loading type packs
 * @see TypePackValidator For schema validation
 */
class TypePackManager
{
    public function __construct(
        protected TypePackLoader $loader,
        protected TypePackValidator $validator
    ) {}

    /**
     * Create a new type pack.
     *
     * @param array{
     *     slug: string,
     *     name: string,
     *     description?: string,
     *     version?: string,
     *     schema?: array,
     *     capabilities?: array,
     *     ui?: array,
     *     indexes?: array
     * } $data Type pack data
     * @return array{success: bool, type_pack?: array, error?: string}
     */
    public function createTypePack(array $data): array
    {
        $slug = $data['slug'];
        $typePackPath = base_path("fragments/types/{$slug}");

        // Check if type pack already exists
        if (File::isDirectory($typePackPath)) {
            return [
                'success' => false,
                'error' => "Type pack already exists: {$slug}",
            ];
        }

        try {
            // Create directory
            File::makeDirectory($typePackPath, 0755, true);

            // Create manifest (type.yaml)
            $manifest = $this->buildManifest($data);
            File::put(
                "{$typePackPath}/type.yaml",
                Yaml::dump($manifest, 4, 2)
            );

            // Create schema if provided
            if (isset($data['schema'])) {
                File::put(
                    "{$typePackPath}/state.schema.json",
                    json_encode($data['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );
            }

            // Create indexes configuration if provided
            if (isset($data['indexes'])) {
                File::put(
                    "{$typePackPath}/indexes.yaml",
                    Yaml::dump($data['indexes'], 4, 2)
                );
            }

            // Load and register the new type pack
            $typePack = $this->loader->loadTypePack($slug);

            return [
                'success' => true,
                'type_pack' => $typePack,
            ];
        } catch (\Exception $e) {
            // Cleanup on failure
            if (File::isDirectory($typePackPath)) {
                File::deleteDirectory($typePackPath);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing type pack.
     *
     * @param string $slug Type pack slug
     * @param array $data Updated type pack data
     * @return array{success: bool, type_pack?: array, error?: string}
     */
    public function updateTypePack(string $slug, array $data): array
    {
        $typePackPath = $this->getTypePackPath($slug);

        if (! $typePackPath) {
            return [
                'success' => false,
                'error' => "Type pack not found: {$slug}",
            ];
        }

        try {
            // Update manifest if provided
            if (isset($data['name']) || isset($data['description']) || isset($data['version']) || isset($data['capabilities']) || isset($data['ui'])) {
                $this->updateManifest($typePackPath, $data);
            }

            // Update schema if provided
            if (array_key_exists('schema', $data)) {
                if ($data['schema'] === null) {
                    // Delete schema file
                    File::delete("{$typePackPath}/state.schema.json");
                } else {
                    File::put(
                        "{$typePackPath}/state.schema.json",
                        json_encode($data['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    );
                }
            }

            // Update indexes if provided
            if (array_key_exists('indexes', $data)) {
                if ($data['indexes'] === null) {
                    // Delete indexes file
                    File::delete("{$typePackPath}/indexes.yaml");
                } else {
                    File::put(
                        "{$typePackPath}/indexes.yaml",
                        Yaml::dump($data['indexes'], 4, 2)
                    );
                }
            }

            // Refresh cache and reload
            $this->loader->refreshCache($slug);
            $typePack = $this->loader->loadTypePack($slug);

            return [
                'success' => true,
                'type_pack' => $typePack,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a type pack.
     *
     * @param string $slug Type pack slug
     * @param bool $deleteFragments Whether to delete all fragments of this type
     * @return array{success: bool, error?: string, deleted_fragments?: int}
     */
    public function deleteTypePack(string $slug, bool $deleteFragments = false): array
    {
        $typePackPath = $this->getTypePackPath($slug);

        if (! $typePackPath) {
            return [
                'success' => false,
                'error' => "Type pack not found: {$slug}",
            ];
        }

        try {
            $deletedFragments = 0;

            // Optionally delete all fragments of this type
            if ($deleteFragments) {
                $deletedFragments = \App\Models\Fragment::where('type', $slug)->count();
                \App\Models\Fragment::where('type', $slug)->delete();
            }

            // Delete type pack directory
            File::deleteDirectory($typePackPath);

            // Remove from registry
            FragmentTypeRegistry::where('slug', $slug)->delete();

            // Clear cache
            $this->loader->refreshCache($slug);

            return [
                'success' => true,
                'deleted_fragments' => $deletedFragments,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate a type pack's schema against sample data.
     *
     * @param string $slug Type pack slug
     * @param array $sampleData Sample data to validate
     * @return array{valid: bool, errors?: array}
     */
    public function validateTypePack(string $slug, array $sampleData): array
    {
        try {
            $this->validator->validateFragmentState($sampleData, $slug);

            return ['valid' => true];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => $this->validator->getValidationErrors($sampleData, $slug),
            ];
        }
    }

    /**
     * Get all fragments using a specific type pack.
     *
     * @param string $slug Type pack slug
     * @return array{total: int, fragments: array}
     */
    public function getFragmentsByType(string $slug): array
    {
        $fragments = \App\Models\Fragment::where('type', $slug)
            ->select('id', 'title', 'body', 'created_at', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(100)
            ->get()
            ->toArray();

        return [
            'total' => \App\Models\Fragment::where('type', $slug)->count(),
            'fragments' => $fragments,
        ];
    }

    /**
     * Build manifest array from input data.
     */
    protected function buildManifest(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'version' => $data['version'] ?? '1.0.0',
            'capabilities' => $data['capabilities'] ?? [],
            'ui' => array_merge([
                'icon' => 'file-text',
                'color' => '#6B7280',
                'display_name' => $data['name'],
                'plural_name' => Str::plural($data['name']),
            ], $data['ui'] ?? []),
        ];
    }

    /**
     * Update manifest file with new data.
     */
    protected function updateManifest(string $typePackPath, array $data): void
    {
        $manifestPath = "{$typePackPath}/type.yaml";
        $manifest = Yaml::parseFile($manifestPath);

        // Update fields if provided
        if (isset($data['name'])) {
            $manifest['name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $manifest['description'] = $data['description'];
        }
        if (isset($data['version'])) {
            $manifest['version'] = $data['version'];
        }
        if (isset($data['capabilities'])) {
            $manifest['capabilities'] = $data['capabilities'];
        }
        if (isset($data['ui'])) {
            $manifest['ui'] = array_merge($manifest['ui'] ?? [], $data['ui']);
        }

        File::put($manifestPath, Yaml::dump($manifest, 4, 2));
    }

    /**
     * Get the filesystem path for a type pack.
     */
    protected function getTypePackPath(string $slug): ?string
    {
        $searchPaths = [
            base_path("fragments/types/{$slug}"),
            base_path("storage/app/fragments/types/{$slug}"),
        ];

        foreach ($searchPaths as $path) {
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Create a type pack from a template.
     *
     * @param string $templateName Template name (e.g., 'basic', 'task', 'note')
     * @param string $slug New type pack slug
     * @param string $name New type pack name
     * @return array{success: bool, type_pack?: array, error?: string}
     */
    public function createFromTemplate(string $templateName, string $slug, string $name): array
    {
        $templates = $this->getTemplates();

        if (! isset($templates[$templateName])) {
            return [
                'success' => false,
                'error' => "Template not found: {$templateName}",
            ];
        }

        $template = $templates[$templateName];

        return $this->createTypePack([
            'slug' => $slug,
            'name' => $name,
            'description' => $template['description'],
            'schema' => $template['schema'],
            'capabilities' => $template['capabilities'] ?? [],
            'ui' => $template['ui'] ?? [],
        ]);
    }

    /**
     * Get available type pack templates.
     */
    public function getTemplates(): array
    {
        return [
            'basic' => [
                'name' => 'Basic Type',
                'description' => 'Simple type with title and content',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'content' => ['type' => 'string'],
                    ],
                    'required' => ['title'],
                ],
            ],
            'task' => [
                'name' => 'Task Type',
                'description' => 'Task with status, priority, and due date',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['pending', 'in_progress', 'completed', 'cancelled'],
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['low', 'medium', 'high', 'critical'],
                        ],
                        'due_date' => ['type' => 'string', 'format' => 'date'],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => ['title', 'status'],
                ],
                'capabilities' => ['tagging', 'due_dates', 'priority'],
            ],
            'note' => [
                'name' => 'Note Type',
                'description' => 'Note with title, content, and tags',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'content' => ['type' => 'string'],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'category' => ['type' => 'string'],
                    ],
                    'required' => ['title', 'content'],
                ],
                'capabilities' => ['tagging', 'categories'],
            ],
        ];
    }
}
