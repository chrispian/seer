<?php

namespace App\Services\Commands;

use App\Models\CommandRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class CommandPackLoader
{
    protected array $searchPaths = [
        'storage/app/fragments/commands',
        'fragments/commands',
        'modules/*/fragments/commands',
    ];

    /**
     * Load a command pack by slug with caching
     */
    public function loadCommandPack(string $slug): ?array
    {
        return Cache::remember("command_pack.{$slug}", 3600, function () use ($slug) {
            return $this->loadCommandPackFromFiles($slug);
        });
    }

    /**
     * Load command pack from file system with precedence
     */
    protected function loadCommandPackFromFiles(string $slug): ?array
    {
        foreach ($this->getSearchPaths() as $basePath) {
            $commandPackPath = base_path("{$basePath}/{$slug}");

            if (! File::isDirectory($commandPackPath)) {
                continue;
            }

            $commandPack = $this->loadCommandPackFromDirectory($commandPackPath, $slug);
            if ($commandPack) {
                // Update registry cache
                $this->updateRegistryCache($slug, $commandPack, $commandPackPath);

                return $commandPack;
            }
        }

        return null;
    }

    /**
     * Load command pack from a specific directory
     */
    protected function loadCommandPackFromDirectory(string $path, string $slug): ?array
    {
        $manifestPath = "{$path}/command.yaml";
        if (! File::exists($manifestPath)) {
            return null;
        }

        try {
            $manifest = Yaml::parseFile($manifestPath);
            $commandPack = [
                'slug' => $slug,
                'manifest' => $manifest,
                'source_path' => $path,
            ];

            // Load prompts directory if exists
            $promptsPath = "{$path}/prompts";
            if (File::isDirectory($promptsPath)) {
                $commandPack['prompts'] = $this->loadPrompts($promptsPath);
            }

            // Load samples directory if exists
            $samplesPath = "{$path}/samples";
            if (File::isDirectory($samplesPath)) {
                $commandPack['samples'] = $this->loadSamples($samplesPath);
            }

            return $commandPack;
        } catch (\Exception $e) {
            \Log::error("Failed to load command pack: {$slug}", [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Load prompts from directory
     */
    protected function loadPrompts(string $promptsPath): array
    {
        $prompts = [];
        $files = File::files($promptsPath);

        foreach ($files as $file) {
            $name = $file->getFilenameWithoutExtension();
            $prompts[$name] = File::get($file->getPathname());
        }

        return $prompts;
    }

    /**
     * Load samples from directory
     */
    protected function loadSamples(string $samplesPath): array
    {
        $samples = [];
        $files = File::files($samplesPath);

        foreach ($files as $file) {
            $name = $file->getFilenameWithoutExtension();
            $content = File::get($file->getPathname());

            // Try to parse as JSON, fallback to text
            try {
                $samples[$name] = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $samples[$name] = $content;
            }
        }

        return $samples;
    }

    /**
     * Update registry cache entry
     */
    protected function updateRegistryCache(string $slug, array $commandPack, string $sourcePath): void
    {
        $stepsHash = $this->calculateStepsHash($commandPack);
        $capabilities = $this->extractCapabilities($commandPack);
        $requiresSecrets = $this->extractRequiredSecrets($commandPack);
        $reserved = $this->isReservedCommand($commandPack);

        CommandRegistry::updateOrCreateEntry($slug, [
            'version' => $commandPack['manifest']['version'] ?? '1.0.0',
            'source_path' => $sourcePath,
            'steps_hash' => $stepsHash,
            'capabilities' => $capabilities,
            'requires_secrets' => $requiresSecrets,
            'reserved' => $reserved,
        ]);
    }

    /**
     * Calculate hash of steps for change detection
     */
    protected function calculateStepsHash(array $commandPack): string
    {
        $steps = $commandPack['manifest']['steps'] ?? [];

        return hash('sha256', json_encode($steps));
    }

    /**
     * Extract capabilities from command pack
     */
    protected function extractCapabilities(array $commandPack): array
    {
        return $commandPack['manifest']['requires']['capabilities'] ?? [];
    }

    /**
     * Extract required secrets from command pack
     */
    protected function extractRequiredSecrets(array $commandPack): array
    {
        return $commandPack['manifest']['requires']['secrets'] ?? [];
    }

    /**
     * Check if command is reserved (built-in)
     */
    protected function isReservedCommand(array $commandPack): bool
    {
        return $commandPack['manifest']['reserved'] ?? false;
    }

    /**
     * Get all search paths in precedence order
     */
    protected function getSearchPaths(): array
    {
        $paths = [];

        foreach ($this->searchPaths as $path) {
            if (str_contains($path, '*')) {
                // Handle wildcard paths (like modules/*/fragments/commands)
                $pattern = base_path($path);
                $paths = array_merge($paths, glob($pattern, GLOB_ONLYDIR));
            } else {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Refresh cache for a specific command pack
     */
    public function refreshCache(string $slug): void
    {
        Cache::forget("command_pack.{$slug}");
        $this->loadCommandPack($slug);
    }

    /**
     * Clear all command pack caches
     */
    public function clearAllCaches(): void
    {
        $registryEntries = CommandRegistry::all();
        foreach ($registryEntries as $entry) {
            Cache::forget("command_pack.{$entry->slug}");
        }
    }

    /**
     * Get all available command packs
     */
    public function getAllCommandPacks(): array
    {
        $commandPacks = [];

        foreach ($this->getSearchPaths() as $basePath) {
            if (! File::isDirectory(base_path($basePath))) {
                continue;
            }

            $directories = File::directories(base_path($basePath));
            foreach ($directories as $dir) {
                $slug = basename($dir);
                if (! isset($commandPacks[$slug])) {
                    $commandPack = $this->loadCommandPack($slug);
                    if ($commandPack) {
                        $commandPacks[$slug] = $commandPack;
                    }
                }
            }
        }

        return $commandPacks;
    }
}
