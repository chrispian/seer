<?php

namespace App\Console\Commands\Commands;

use App\Services\Commands\CommandPackLoader;
use Illuminate\Console\Command;

class CacheCommandPacksCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'frag:command:cache {slug? : Specific command pack to cache}';

    /**
     * The console command description.
     */
    protected $description = 'Rebuild command pack registry cache from files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');
        $loader = app(CommandPackLoader::class);

        if ($slug) {
            return $this->cacheSpecificCommandPack($loader, $slug);
        }

        return $this->cacheAllCommandPacks($loader);
    }

    /**
     * Cache a specific command pack
     */
    protected function cacheSpecificCommandPack(CommandPackLoader $loader, string $slug): int
    {
        $this->info("Refreshing cache for command pack '{$slug}'...");

        try {
            $loader->refreshCache($slug);
            $commandPack = $loader->loadCommandPack($slug);

            if (! $commandPack) {
                $this->error("Command pack '{$slug}' not found.");

                return self::FAILURE;
            }

            $this->info("✅ Command pack '{$slug}' cached successfully.");
            $this->displayCommandPackInfo($commandPack);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cache command pack '{$slug}': {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Cache all command packs
     */
    protected function cacheAllCommandPacks(CommandPackLoader $loader): int
    {
        $this->info('Rebuilding command pack registry cache...');
        $startTime = microtime(true);

        try {
            // Clear all caches first
            $clearStartTime = microtime(true);
            $loader->clearAllCaches();
            $clearDuration = round((microtime(true) - $clearStartTime) * 1000, 2);

            // Load all command packs (this will cache them)
            $loadStartTime = microtime(true);
            $commandPacks = $loader->getAllCommandPacks();
            $loadDuration = round((microtime(true) - $loadStartTime) * 1000, 2);

            if (empty($commandPacks)) {
                $this->warn('No command packs found.');

                return self::SUCCESS;
            }

            $totalDuration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info('✅ Cached '.count($commandPacks).' command pack(s):');

            foreach ($commandPacks as $slug => $commandPack) {
                $version = $commandPack['manifest']['version'] ?? '1.0.0';
                $name = $commandPack['manifest']['name'] ?? $slug;
                $slash = $commandPack['manifest']['triggers']['slash'] ?? "/{$slug}";
                $this->line("  • {$name} (v{$version}) - {$slash}");
            }

            $this->newLine();
            $this->info("🚀 Performance: {$totalDuration}ms total (clear: {$clearDuration}ms, load: {$loadDuration}ms)");

            if (count($commandPacks) > 0) {
                $avgPerCommand = round($totalDuration / count($commandPacks), 2);
                $this->line("   Average per command: {$avgPerCommand}ms");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cache command packs: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Display information about a command pack
     */
    protected function displayCommandPackInfo(array $commandPack): void
    {
        $manifest = $commandPack['manifest'] ?? [];

        $this->newLine();
        $this->line('<info>📦 Command Pack Details:</info>');
        $this->line('  Name: '.($manifest['name'] ?? 'Unknown'));
        $this->line('  Version: '.($manifest['version'] ?? '1.0.0'));
        $this->line('  Slash Command: '.($manifest['triggers']['slash'] ?? 'Unknown'));
        $this->line('  Source: '.($commandPack['source_path'] ?? 'Unknown'));

        if (isset($manifest['requires']['capabilities'])) {
            $this->line('  Capabilities: '.implode(', ', $manifest['requires']['capabilities']));
        }

        if (isset($manifest['steps'])) {
            $stepTypes = array_column($manifest['steps'], 'type');
            $this->line('  Steps: '.implode(' → ', $stepTypes));
        }
    }
}
