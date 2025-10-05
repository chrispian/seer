<?php

namespace App\Console\Commands;

use App\Services\Embeddings\EmbeddingStoreManager;
use Illuminate\Console\Command;

class VectorStoreStatus extends Command
{
    protected $signature = 'vector:status {--driver= : Specific driver to check (sqlite, postgresql)} {--detailed : Show detailed capability analysis}';

    protected $description = 'Check the status of vector store drivers';

    public function handle(EmbeddingStoreManager $manager): int
    {
        $this->info('Vector Store Status');
        $this->line('==================');

        if ($this->option('detailed')) {
            $this->showDetailedCapabilityAnalysis();
            $this->newLine();
            $this->line('<options=bold>Legacy Driver Status:</>');
        }

        if ($driver = $this->option('driver')) {
            $this->checkSpecificDriver($manager, $driver);
        } else {
            $this->checkAllDrivers($manager);
        }

        return 0;
    }

    protected function showDetailedCapabilityAnalysis(): void
    {
        try {
            $detector = app(\App\Services\VectorCapabilityDetector::class);
            $capabilities = $detector->detectCapabilities();

            // Database info
            $db = $capabilities['database'] ?? [];
            $this->line('<options=bold>Database Environment:</>');
            $this->line('  Driver: '.($db['driver'] ?? 'unknown'));
            $this->line('  Connection: '.($db['connection'] ?? 'unknown'));
            if (isset($db['version'])) {
                $this->line('  Version: '.$db['version']);
            }

            // Vector capabilities
            $vector = $capabilities['vector_support'] ?? [];
            $this->newLine();
            $this->line('<options=bold>Vector Capabilities:</>');
            $vectorStatus = ($vector['available'] ?? false) ? '<fg=green>✓ Available</>' : '<fg=red>✗ Not Available</>';
            $this->line("  Status: {$vectorStatus}");

            if ($vector['available'] ?? false) {
                $this->line('  Extension: '.($vector['extension'] ?? 'unknown'));
                $this->line('  Version: '.($vector['version'] ?? 'unknown'));
                $this->line('  Operations Tested: '.(($vector['operations_tested'] ?? false) ? '✓' : '✗'));
                if (isset($vector['index_types'])) {
                    $this->line('  Supported Indexes: '.implode(', ', $vector['index_types']));
                }
                if (isset($vector['similarity_functions'])) {
                    $this->line('  Similarity Functions: '.implode(', ', $vector['similarity_functions']));
                }
            } else {
                $this->line('  Reason: '.($vector['reason'] ?? 'unknown'));
            }

            // Text search capabilities
            $text = $capabilities['text_search'] ?? [];
            $this->newLine();
            $this->line('<options=bold>Text Search Capabilities:</>');
            $textStatus = ($text['available'] ?? false) ? '<fg=green>✓ Available</>' : '<fg=red>✗ Not Available</>';
            $this->line("  Status: {$textStatus}");

            if ($text['available'] ?? false) {
                if (isset($text['type'])) {
                    $this->line('  Primary Type: '.$text['type']);
                }
                if (isset($text['fts5'])) {
                    $fts5Status = ($text['fts5']['available'] ?? false) ? '✓' : '✗';
                    $this->line("  FTS5 Support: {$fts5Status}");
                }
                if (isset($text['languages'])) {
                    $this->line('  Languages: '.implode(', ', array_slice($text['languages'], 0, 5)).
                               (count($text['languages']) > 5 ? '...' : ''));
                }
            }

            // Performance features
            $performance = $capabilities['performance'] ?? [];
            if (! empty($performance)) {
                $this->newLine();
                $this->line('<options=bold>Performance Features:</>');
                foreach ($performance as $feature => $enabled) {
                    $status = $enabled ? '✓' : '✗';
                    $featureName = ucwords(str_replace('_', ' ', $feature));
                    $this->line("  {$featureName}: {$status}");
                }
            }

            // Extensions summary
            $extensions = $capabilities['extensions'] ?? [];
            if (! empty($extensions)) {
                $this->newLine();
                $this->line('<options=bold>Detected Extensions:</>');
                $extensionList = array_map(fn ($name, $version) => "{$name} ({$version})",
                    array_keys($extensions), array_values($extensions));
                $this->line('  '.implode(', ', $extensionList));
            }

            // Overall assessment
            $this->newLine();
            $this->line('<options=bold>Overall Assessment:</>');
            $vectorOk = $vector['available'] ?? false;
            $textOk = $text['available'] ?? false;

            if ($vectorOk && $textOk) {
                $this->line('  <fg=green>✓ Full hybrid search capabilities available</>');
            } elseif ($textOk) {
                $this->line('  <fg=yellow>⚠ Text search only (vector operations unavailable)</>');
            } elseif ($vectorOk) {
                $this->line('  <fg=yellow>⚠ Vector search only (text search limited)</>');
            } else {
                $this->line('  <fg=red>✗ Limited capabilities (fallback mode)</>');
            }

        } catch (\Exception $e) {
            $this->error('Detailed analysis failed: '.$e->getMessage());
        }
    }

    protected function checkAllDrivers(EmbeddingStoreManager $manager): void
    {
        $drivers = $manager->getSupportedDrivers();

        foreach ($drivers as $driverName) {
            $this->checkSpecificDriver($manager, $driverName);
            $this->line('');
        }

        // Show auto-detected driver
        $this->line('Auto-detected driver:');
        $autoDriver = $manager->driver();
        $this->showDriverInfo($autoDriver->getDriverInfo(), true);
    }

    protected function checkSpecificDriver(EmbeddingStoreManager $manager, string $driverName): void
    {
        $this->line("Driver: <fg=yellow>{$driverName}</fg=yellow>");

        try {
            $store = $manager->driver($driverName);
            $info = $store->getDriverInfo();
            $this->showDriverInfo($info);

            // Additional diagnostics for SQLite
            if ($driverName === 'sqlite' && method_exists($store, 'diagnoseConnection')) {
                $diagnosis = $store->diagnoseConnection();
                $this->line('  Diagnostics:');
                foreach ($diagnosis as $key => $value) {
                    if ($key === 'error') {
                        $this->line("    <fg=red>{$key}:</fg=red> {$value}");
                    } else {
                        $displayValue = is_bool($value) ? ($value ? 'yes' : 'no') : ($value ?? 'N/A');
                        $this->line("    {$key}: {$displayValue}");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("  Error: {$e->getMessage()}");
        }
    }

    protected function showDriverInfo(array $info, bool $isAutoDetected = false): void
    {
        $available = $info['available'] ?? $info['status'] === 'available' ?? false;
        $status = $available ? '<fg=green>Available</fg=green>' : '<fg=red>Not Available</fg=red>';

        $extension = $info['extension'] ?? 'unknown';
        $this->line("  Extension: {$extension}");
        $this->line("  Status: {$status}");

        if (isset($info['version']) && $info['version']) {
            $this->line("  Version: {$info['version']}");
        }

        if ($isAutoDetected) {
            $this->line('  <fg=cyan>This driver will be used automatically</fg=cyan>');
        }

        // Show diagnostics if available
        if (isset($info['diagnostics'])) {
            $this->line('  Diagnostics:');
            foreach ($info['diagnostics'] as $key => $value) {
                $this->line("    {$key}: {$value}");
            }
        }

        // Show error if present
        if (isset($info['error'])) {
            $this->line("    <fg=red>error: {$info['error']}</fg=red>");
        }
    }
}
