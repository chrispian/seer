<?php

namespace App\Console\Commands\AI;

use App\Services\CredentialStorageManager;
use Illuminate\Console\Command;

class CredentialStorageStatus extends Command
{
    protected $signature = 'ai:credentials:storage-status
                            {--backend= : Show status for specific backend only}
                            {--json : Output as JSON}';

    protected $description = 'Show credential storage backend status and capabilities';

    public function handle(CredentialStorageManager $storageManager): int
    {
        $specificBackend = $this->option('backend');
        $jsonOutput = $this->option('json');

        try {
            if ($specificBackend) {
                return $this->showSpecificBackendStatus($storageManager, $specificBackend, $jsonOutput);
            }

            return $this->showAllBackendsStatus($storageManager, $jsonOutput);
        } catch (\Exception $e) {
            $this->error("Failed to get storage status: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function showAllBackendsStatus(CredentialStorageManager $storageManager, bool $jsonOutput): int
    {
        $status = $storageManager->getStorageStatus();
        $availableTypes = $storageManager->getAvailableStorageTypes();
        $defaultType = $storageManager->getDefaultStorageType();
        $validation = $storageManager->validateConfiguration();

        if ($jsonOutput) {
            $output = [
                'default_backend' => $defaultType,
                'available_backends' => $availableTypes,
                'configuration_valid' => $validation['valid'],
                'configuration_issues' => $validation['issues'],
                'backend_status' => $status,
            ];

            $this->line(json_encode($output, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        // Human-readable output
        $this->info('Credential Storage Status Report');
        $this->newLine();

        // Configuration overview
        $this->line('<comment>Configuration Overview:</comment>');
        $this->line("Default Backend: <info>{$defaultType}</info>");
        $this->line('Available Backends: '.count($availableTypes));

        if (! $validation['valid']) {
            $this->newLine();
            $this->error('Configuration Issues:');
            foreach ($validation['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }

        $this->newLine();

        // Backend status table
        $headers = ['Backend', 'Status', 'Available', 'Capabilities', 'Statistics'];
        $rows = [];

        foreach ($status as $type => $backendStatus) {
            $statusColor = match ($backendStatus['status']) {
                'healthy' => 'green',
                'degraded' => 'yellow',
                'unhealthy' => 'red',
                default => 'white',
            };

            $availableText = $backendStatus['available'] ? '✓' : '✗';
            $capabilitiesText = $this->formatCapabilities($backendStatus['capabilities'] ?? []);
            $statisticsText = $this->formatStatistics($backendStatus['statistics'] ?? []);

            $rows[] = [
                $type === $defaultType ? "<info>{$type}</info> (default)" : $type,
                "<fg={$statusColor}>{$backendStatus['status']}</>",
                $availableText,
                $capabilitiesText,
                $statisticsText,
            ];
        }

        $this->table($headers, $rows);

        // Show errors if any
        foreach ($status as $type => $backendStatus) {
            if (isset($backendStatus['error'])) {
                $this->newLine();
                $this->error("Error in {$type}: {$backendStatus['error']}");
            }
        }

        return self::SUCCESS;
    }

    protected function showSpecificBackendStatus(CredentialStorageManager $storageManager, string $backend, bool $jsonOutput): int
    {
        try {
            $storage = $storageManager->getStorage($backend);
            $status = $storage->getHealthStatus();
            $capabilities = $storage->getCapabilities();

            if ($jsonOutput) {
                $output = [
                    'backend' => $backend,
                    'status' => $status,
                    'capabilities' => $capabilities,
                ];

                $this->line(json_encode($output, JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            // Human-readable output for specific backend
            $this->info("Storage Backend: {$backend}");
            $this->newLine();

            $statusColor = match ($status['status']) {
                'healthy' => 'green',
                'degraded' => 'yellow',
                'unhealthy' => 'red',
                default => 'white',
            };

            $this->line("Status: <fg={$statusColor}>{$status['status']}</>");
            $this->line('Available: '.($status['available'] ? '✓ Yes' : '✗ No'));
            $this->line("Last Checked: {$status['last_checked']}");

            if (isset($status['error'])) {
                $this->newLine();
                $this->error("Error: {$status['error']}");
            }

            $this->newLine();
            $this->line('<comment>Capabilities:</comment>');
            foreach ($capabilities as $capability => $value) {
                $valueText = is_bool($value) ? ($value ? '✓' : '✗') : $value;
                $this->line("  {$capability}: {$valueText}");
            }

            if (isset($status['statistics'])) {
                $this->newLine();
                $this->line('<comment>Statistics:</comment>');
                foreach ($status['statistics'] as $stat => $value) {
                    $this->line("  {$stat}: {$value}");
                }
            }

            return self::SUCCESS;

        } catch (\InvalidArgumentException $e) {
            $this->error("Unknown backend: {$backend}");

            $availableTypes = $storageManager->getAvailableStorageTypes();
            $this->info('Available backends: '.implode(', ', array_map(fn ($type) => $type['type'], $availableTypes)));

            return self::FAILURE;
        }
    }

    protected function formatCapabilities(array $capabilities): string
    {
        $enabled = [];
        foreach ($capabilities as $capability => $value) {
            if ($value === true) {
                $enabled[] = $capability;
            }
        }

        if (empty($enabled)) {
            return 'None';
        }

        return implode(', ', array_slice($enabled, 0, 3)).
               (count($enabled) > 3 ? '...' : '');
    }

    protected function formatStatistics(array $statistics): string
    {
        if (empty($statistics)) {
            return 'N/A';
        }

        $parts = [];
        if (isset($statistics['active_credentials'])) {
            $parts[] = "Active: {$statistics['active_credentials']}";
        }
        if (isset($statistics['expired_credentials']) && $statistics['expired_credentials'] > 0) {
            $parts[] = "Expired: {$statistics['expired_credentials']}";
        }

        return implode(', ', $parts) ?: 'N/A';
    }
}
