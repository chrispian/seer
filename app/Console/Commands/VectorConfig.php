<?php

namespace App\Console\Commands;

use App\Services\VectorCapabilityDetector;
use Illuminate\Console\Command;

class VectorConfig extends Command
{
    protected $signature = 'vector:config 
                           {action? : Action to perform (detect, show, clear-cache, validate)}
                           {--force : Force re-detection without cache}
                           {--json : Output as JSON}';

    protected $description = 'Manage vector store configuration and capability detection';

    public function handle()
    {
        $action = $this->argument('action') ?? 'show';
        $detector = app(VectorCapabilityDetector::class);

        switch ($action) {
            case 'detect':
                return $this->detectCapabilities($detector);
            case 'show':
                return $this->showConfiguration($detector);
            case 'clear-cache':
                return $this->clearCache($detector);
            case 'validate':
                return $this->validateConfiguration($detector);
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: detect, show, clear-cache, validate');

                return Command::FAILURE;
        }
    }

    protected function detectCapabilities(VectorCapabilityDetector $detector): int
    {
        $this->info('Detecting vector capabilities...');

        $useCache = ! $this->option('force');
        $capabilities = $detector->detectCapabilities($useCache);

        if ($this->option('json')) {
            $this->line(json_encode($capabilities, JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $this->displayCapabilities($capabilities);

        return Command::SUCCESS;
    }

    protected function showConfiguration(VectorCapabilityDetector $detector): int
    {
        $this->info('Vector Store Configuration');
        $this->info('==========================');

        // Show basic configuration
        $this->displayBasicConfig();

        // Show detected capabilities
        $this->newLine();
        $this->info('Detected Capabilities:');

        $useCache = ! $this->option('force');
        $capabilities = $detector->detectCapabilities($useCache);

        if ($this->option('json')) {
            $this->line(json_encode([
                'configuration' => $this->getBasicConfig(),
                'capabilities' => $capabilities,
            ], JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $this->displayCapabilities($capabilities);

        return Command::SUCCESS;
    }

    protected function clearCache(VectorCapabilityDetector $detector): int
    {
        $detector->clearCache();
        $this->info('Vector capability cache cleared successfully');

        return Command::SUCCESS;
    }

    protected function validateConfiguration(VectorCapabilityDetector $detector): int
    {
        $this->info('Validating vector store configuration...');

        $capabilities = $detector->detectCapabilities();
        $issues = $this->findConfigurationIssues($capabilities);

        if (empty($issues)) {
            $this->info('✓ Configuration is valid and optimal');

            return Command::SUCCESS;
        }

        $this->warn('Configuration issues found:');
        foreach ($issues as $issue) {
            $this->line("  - {$issue}");
        }

        return Command::FAILURE;
    }

    protected function displayBasicConfig(): void
    {
        $config = $this->getBasicConfig();

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $this->line("<options=bold>{$key}:</>");
                foreach ($value as $subKey => $subValue) {
                    $displayValue = is_bool($subValue) ? ($subValue ? 'true' : 'false') : $subValue;
                    $this->line("  {$subKey}: {$displayValue}");
                }
            } else {
                $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                $this->line("<options=bold>{$key}:</> {$displayValue}");
            }
        }
    }

    protected function getBasicConfig(): array
    {
        return [
            'default_driver' => config('vectors.default'),
            'hybrid_search' => [
                'vector_weight' => config('vectors.hybrid_search.default_vector_weight'),
                'text_weight' => config('vectors.hybrid_search.default_text_weight'),
                'threshold' => config('vectors.hybrid_search.similarity_threshold'),
            ],
            'capabilities' => [
                'cache_enabled' => config('vectors.capabilities.cache_detection_results'),
                'cache_ttl' => config('vectors.capabilities.cache_ttl'),
            ],
            'performance' => [
                'batch_size' => config('vectors.performance.batch_size'),
                'concurrent_operations' => config('vectors.performance.enable_concurrent_operations'),
                'query_timeout' => config('vectors.performance.query_timeout'),
            ],
            'debug' => [
                'log_queries' => config('vectors.debug.log_queries'),
                'diagnostics_enabled' => config('vectors.debug.enable_diagnostics'),
            ],
        ];
    }

    protected function displayCapabilities(array $capabilities): void
    {
        // Database information
        $db = $capabilities['database'] ?? [];
        $this->line('<options=bold>Database:</>');
        $this->line('  Driver: '.($db['driver'] ?? 'unknown'));
        $this->line('  Version: '.($db['version'] ?? 'unknown'));

        // Vector support
        $vector = $capabilities['vector_support'] ?? [];
        $this->line('<options=bold>Vector Support:</>');
        $available = $vector['available'] ?? false;
        $this->line('  Available: '.($available ? '✓' : '✗'));

        if ($available) {
            $this->line('  Extension: '.($vector['extension'] ?? 'unknown'));
            $this->line('  Version: '.($vector['version'] ?? 'unknown'));
            if (isset($vector['index_types'])) {
                $this->line('  Index Types: '.implode(', ', $vector['index_types']));
            }
        } else {
            $this->line('  Reason: '.($vector['reason'] ?? 'unknown'));
        }

        // Text search
        $text = $capabilities['text_search'] ?? [];
        $this->line('<options=bold>Text Search:</>');
        $textAvailable = $text['available'] ?? false;
        $this->line('  Available: '.($textAvailable ? '✓' : '✗'));

        if ($textAvailable) {
            if (isset($text['type'])) {
                $this->line('  Type: '.$text['type']);
            }
            if (isset($text['fts5'])) {
                $fts5Available = $text['fts5']['available'] ?? false;
                $this->line('  FTS5: '.($fts5Available ? '✓' : '✗'));
            }
        }

        // Extensions
        $extensions = $capabilities['extensions'] ?? [];
        if (! empty($extensions)) {
            $this->line('<options=bold>Extensions:</>');
            foreach ($extensions as $name => $version) {
                $this->line("  {$name}: {$version}");
            }
        }

        // Detection metadata
        if (isset($capabilities['detected_at'])) {
            $this->line('<options=bold>Detection:</>');
            $this->line('  Detected at: '.$capabilities['detected_at']);
            $this->line('  Version: '.($capabilities['detection_version'] ?? 'unknown'));
            if (isset($capabilities['fallback']) && $capabilities['fallback']) {
                $this->warn('  Using fallback capabilities');
            }
        }
    }

    protected function findConfigurationIssues(array $capabilities): array
    {
        $issues = [];

        // Check if vector support is missing but configured
        $vectorConfig = config('vectors.default');
        $vectorAvailable = $capabilities['vector_support']['available'] ?? false;

        if (in_array($vectorConfig, ['auto', 'postgresql', 'sqlite']) && ! $vectorAvailable) {
            $issues[] = 'Vector support configured but not available';
        }

        // Check for suboptimal configuration
        $dbDriver = $capabilities['database']['driver'] ?? 'unknown';
        if ($dbDriver === 'pgsql' && $vectorConfig === 'sqlite') {
            $issues[] = 'PostgreSQL database detected but SQLite vector driver configured';
        }

        if ($dbDriver === 'sqlite' && $vectorConfig === 'postgresql') {
            $issues[] = 'SQLite database detected but PostgreSQL vector driver configured';
        }

        // Check hybrid search configuration
        $vectorWeight = config('vectors.hybrid_search.default_vector_weight', 0.7);
        $textWeight = config('vectors.hybrid_search.default_text_weight', 0.3);

        if (abs(($vectorWeight + $textWeight) - 1.0) > 0.01) {
            $issues[] = "Hybrid search weights do not sum to 1.0 (vector: {$vectorWeight}, text: {$textWeight})";
        }

        // Check for missing extensions
        if ($dbDriver === 'pgsql' && ! isset($capabilities['extensions']['vector'])) {
            $issues[] = 'PostgreSQL detected but pgvector extension not found';
        }

        return $issues;
    }
}
