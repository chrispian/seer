<?php

namespace App\Console\Commands;

use App\Services\Embeddings\EmbeddingStoreManager;
use Illuminate\Console\Command;

class VectorStoreStatus extends Command
{
    protected $signature = 'vector:status {--driver= : Specific driver to check (sqlite, postgresql)}';
    protected $description = 'Check the status of vector store drivers';

    public function handle(EmbeddingStoreManager $manager): int
    {
        $this->info('Vector Store Status');
        $this->line('==================');

        if ($driver = $this->option('driver')) {
            $this->checkSpecificDriver($manager, $driver);
        } else {
            $this->checkAllDrivers($manager);
        }

        return 0;
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
        $status = $info['available'] ? '<fg=green>Available</fg=green>' : '<fg=red>Not Available</fg=red>';
        $this->line("  Extension: {$info['extension']}");
        $this->line("  Status: {$status}");
        
        if (isset($info['version']) && $info['version']) {
            $this->line("  Version: {$info['version']}");
        }

        if ($isAutoDetected) {
            $this->line("  <fg=cyan>This driver will be used automatically</fg=cyan>");
        }
    }
}