<?php

namespace App\Console\Commands\TypePacks;

use App\Services\TypeSystem\TypePackLoader;
use Illuminate\Console\Command;

class CacheTypePacksCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'frag:type:cache {slug? : Specific type pack to cache}';

    /**
     * The console command description.
     */
    protected $description = 'Rebuild type pack registry cache from files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');
        $loader = app(TypePackLoader::class);

        if ($slug) {
            return $this->cacheSpecificTypePack($loader, $slug);
        }

        return $this->cacheAllTypePacks($loader);
    }

    /**
     * Cache a specific type pack
     */
    protected function cacheSpecificTypePack(TypePackLoader $loader, string $slug): int
    {
        $this->info("Refreshing cache for type pack '{$slug}'...");

        try {
            $loader->refreshCache($slug);
            $typePack = $loader->loadTypePack($slug);

            if (!$typePack) {
                $this->error("Type pack '{$slug}' not found.");
                return self::FAILURE;
            }

            $this->info("✅ Type pack '{$slug}' cached successfully.");
            $this->displayTypePackInfo($typePack);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cache type pack '{$slug}': {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Cache all type packs
     */
    protected function cacheAllTypePacks(TypePackLoader $loader): int
    {
        $this->info('Rebuilding type pack registry cache...');

        try {
            // Clear all caches first
            $loader->clearAllCaches();

            // Load all type packs (this will cache them)
            $typePacks = $loader->getAllTypePacks();

            if (empty($typePacks)) {
                $this->warn('No type packs found.');
                return self::SUCCESS;
            }

            $this->info("✅ Cached " . count($typePacks) . " type pack(s):");

            foreach ($typePacks as $slug => $typePack) {
                $version = $typePack['manifest']['version'] ?? '1.0.0';
                $name = $typePack['manifest']['name'] ?? $slug;
                $this->line("  • {$name} (v{$version}) - {$slug}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cache type packs: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display information about a type pack
     */
    protected function displayTypePackInfo(array $typePack): void
    {
        $manifest = $typePack['manifest'] ?? [];
        
        $this->newLine();
        $this->line("<info>📦 Type Pack Details:</info>");
        $this->line("  Name: " . ($manifest['name'] ?? 'Unknown'));
        $this->line("  Version: " . ($manifest['version'] ?? '1.0.0'));
        $this->line("  Description: " . ($manifest['description'] ?? 'No description'));
        $this->line("  Source: " . ($typePack['source_path'] ?? 'Unknown'));
        
        if (isset($manifest['capabilities'])) {
            $this->line("  Capabilities: " . implode(', ', $manifest['capabilities']));
        }

        if (isset($typePack['schema'])) {
            $properties = array_keys($typePack['schema']['properties'] ?? []);
            $this->line("  Schema fields: " . implode(', ', $properties));
        }
    }
}
