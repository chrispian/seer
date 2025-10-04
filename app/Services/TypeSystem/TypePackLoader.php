<?php

namespace App\Services\TypeSystem;

use App\Models\FragmentTypeRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class TypePackLoader
{
    protected array $searchPaths = [
        'storage/app/fragments/types',
        'fragments/types',
        'modules/*/fragments/types',
    ];

    /**
     * Load a type pack by slug with caching
     */
    public function loadTypePack(string $slug): ?array
    {
        return Cache::remember("type_pack.{$slug}", 3600, function () use ($slug) {
            return $this->loadTypePackFromFiles($slug);
        });
    }

    /**
     * Load type pack from file system with precedence
     */
    protected function loadTypePackFromFiles(string $slug): ?array
    {
        foreach ($this->getSearchPaths() as $basePath) {
            $typePackPath = base_path("{$basePath}/{$slug}");

            if (! File::isDirectory($typePackPath)) {
                continue;
            }

            $typePack = $this->loadTypePackFromDirectory($typePackPath, $slug);
            if ($typePack) {
                // Update registry cache
                $this->updateRegistryCache($slug, $typePack, $typePackPath);

                return $typePack;
            }
        }

        return null;
    }

    /**
     * Load type pack from a specific directory
     */
    protected function loadTypePackFromDirectory(string $path, string $slug): ?array
    {
        $manifestPath = "{$path}/type.yaml";
        if (! File::exists($manifestPath)) {
            return null;
        }

        try {
            $manifest = Yaml::parseFile($manifestPath);
            $typePack = [
                'slug' => $slug,
                'manifest' => $manifest,
                'source_path' => $path,
            ];

            // Load schema if exists
            $schemaPath = "{$path}/state.schema.json";
            if (File::exists($schemaPath)) {
                $typePack['schema'] = json_decode(File::get($schemaPath), true);
            }

            // Load indexes configuration if exists
            $indexesPath = "{$path}/indexes.yaml";
            if (File::exists($indexesPath)) {
                $typePack['indexes'] = Yaml::parseFile($indexesPath);
            }

            return $typePack;
        } catch (\Exception $e) {
            \Log::error("Failed to load type pack: {$slug}", [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update registry cache entry
     */
    protected function updateRegistryCache(string $slug, array $typePack, string $sourcePath): void
    {
        $schemaHash = $this->calculateSchemaHash($typePack);
        $hotFields = $this->extractHotFields($typePack);
        $capabilities = $this->extractCapabilities($typePack);

        FragmentTypeRegistry::updateOrCreateEntry($slug, [
            'version' => $typePack['manifest']['version'] ?? '1.0.0',
            'source_path' => $sourcePath,
            'schema_hash' => $schemaHash,
            'hot_fields' => $hotFields,
            'capabilities' => $capabilities,
        ]);
    }

    /**
     * Calculate hash of schema for change detection
     */
    protected function calculateSchemaHash(array $typePack): string
    {
        $schemaContent = json_encode($typePack['schema'] ?? []);

        return hash('sha256', $schemaContent);
    }

    /**
     * Extract hot fields for performance optimization
     */
    protected function extractHotFields(array $typePack): array
    {
        $hotFields = [];

        if (isset($typePack['indexes']['hot_fields'])) {
            foreach ($typePack['indexes']['hot_fields'] as $field => $config) {
                $hotFields[$field] = [
                    'type' => $config['type'] ?? 'string',
                    'path' => $config['path'] ?? $field,
                    'indexed' => $config['indexed'] ?? true,
                ];
            }
        }

        return $hotFields;
    }

    /**
     * Extract type capabilities
     */
    protected function extractCapabilities(array $typePack): array
    {
        return $typePack['manifest']['capabilities'] ?? [];
    }

    /**
     * Get all search paths in precedence order
     */
    protected function getSearchPaths(): array
    {
        $paths = [];

        foreach ($this->searchPaths as $path) {
            if (str_contains($path, '*')) {
                // Handle wildcard paths (like modules/*/fragments/types)
                $pattern = base_path($path);
                $paths = array_merge($paths, glob($pattern, GLOB_ONLYDIR));
            } else {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Refresh cache for a specific type pack
     */
    public function refreshCache(string $slug): void
    {
        Cache::forget("type_pack.{$slug}");
        $this->loadTypePack($slug);
    }

    /**
     * Clear all type pack caches
     */
    public function clearAllCaches(): void
    {
        $registryEntries = FragmentTypeRegistry::all();
        foreach ($registryEntries as $entry) {
            Cache::forget("type_pack.{$entry->slug}");
        }
    }

    /**
     * Get all available type packs
     */
    public function getAllTypePacks(): array
    {
        $typePacks = [];

        foreach ($this->getSearchPaths() as $basePath) {
            if (! File::isDirectory(base_path($basePath))) {
                continue;
            }

            $directories = File::directories(base_path($basePath));
            foreach ($directories as $dir) {
                $slug = basename($dir);
                if (! isset($typePacks[$slug])) {
                    $typePack = $this->loadTypePack($slug);
                    if ($typePack) {
                        $typePacks[$slug] = $typePack;
                    }
                }
            }
        }

        return $typePacks;
    }
}
