<?php

namespace App\Console\Commands\AI;

use App\Services\CredentialStorageManager;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MigrateCredentialStorage extends Command
{
    protected $signature = 'ai:credentials:migrate
                            {--from= : Source storage backend}
                            {--to= : Target storage backend}
                            {--provider= : Migrate specific provider only}
                            {--dry-run : Show what would be migrated without performing migration}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Migrate credentials between storage backends';

    public function handle(CredentialStorageManager $storageManager): int
    {
        $fromType = $this->option('from');
        $toType = $this->option('to');
        $provider = $this->option('provider');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        try {
            // Get available storage types
            $availableTypes = $storageManager->getAvailableStorageTypes();
            $typeNames = array_column($availableTypes, 'type');

            if (empty($typeNames)) {
                $this->error('No storage backends are available for migration');

                return self::FAILURE;
            }

            // Get source backend
            if (! $fromType) {
                $fromType = select(
                    'Select source storage backend:',
                    $typeNames
                );
            }

            if (! in_array($fromType, $typeNames)) {
                $this->error("Source backend '{$fromType}' is not available");
                $this->info('Available backends: '.implode(', ', $typeNames));

                return self::FAILURE;
            }

            // Get target backend
            if (! $toType) {
                $toType = select(
                    'Select target storage backend:',
                    array_filter($typeNames, fn ($type) => $type !== $fromType)
                );
            }

            if (! in_array($toType, $typeNames)) {
                $this->error("Target backend '{$toType}' is not available");
                $this->info('Available backends: '.implode(', ', $typeNames));

                return self::FAILURE;
            }

            if ($fromType === $toType) {
                $this->error('Source and target backends cannot be the same');

                return self::FAILURE;
            }

            // Get provider filter if not specified
            if (! $provider && ! $force) {
                $shouldFilterProvider = confirm('Migrate credentials for a specific provider only?');
                if ($shouldFilterProvider) {
                    $provider = text('Enter provider name (e.g., openai, anthropic):');
                }
            }

            // Show migration plan
            $this->showMigrationPlan($storageManager, $fromType, $toType, $provider, $dryRun);

            // Confirm migration unless force flag is used
            if (! $force && ! $dryRun) {
                if (! confirm('Proceed with credential migration?')) {
                    $this->info('Migration cancelled');

                    return self::SUCCESS;
                }
            }

            // Perform migration
            return $this->performMigration($storageManager, $fromType, $toType, $provider, $dryRun);

        } catch (\Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function showMigrationPlan(
        CredentialStorageManager $storageManager,
        string $fromType,
        string $toType,
        ?string $provider,
        bool $dryRun
    ): void {
        $this->info('Migration Plan');
        $this->newLine();

        $this->line("Source Backend: <comment>{$fromType}</comment>");
        $this->line("Target Backend: <comment>{$toType}</comment>");
        $this->line('Provider Filter: <comment>'.($provider ?: 'All providers').'</comment>');
        $this->line('Mode: <comment>'.($dryRun ? 'Dry Run (no changes)' : 'Live Migration').'</comment>');

        $this->newLine();

        // Get credentials to migrate
        try {
            $fromStorage = $storageManager->getStorage($fromType);
            $credentials = $fromStorage->list($provider);

            if (empty($credentials)) {
                $this->warn('No credentials found to migrate');

                return;
            }

            $this->line('<comment>Credentials to migrate:</comment> '.count($credentials));

            // Group by provider
            $byProvider = [];
            foreach ($credentials as $credential) {
                $byProvider[$credential['provider']][] = $credential;
            }

            foreach ($byProvider as $providerName => $providerCredentials) {
                $this->line("  {$providerName}: ".count($providerCredentials).' credential(s)');
            }

            // Show warnings and considerations
            $this->newLine();
            $this->line('<comment>Migration Considerations:</comment>');

            $fromCapabilities = $fromStorage->getCapabilities();
            $toStorage = $storageManager->getStorage($toType);
            $toCapabilities = $toStorage->getCapabilities();

            if ($fromCapabilities['soft_delete'] && ! $toCapabilities['soft_delete']) {
                $this->warn('  • Target backend does not support soft delete');
            }

            if ($fromCapabilities['metadata'] && ! $toCapabilities['metadata']) {
                $this->warn('  • Target backend has limited metadata support');
            }

            if ($fromCapabilities['hardware_backed'] && ! $toCapabilities['hardware_backed']) {
                $this->warn('  • Target backend does not provide hardware-backed security');
            }

            if (! $fromCapabilities['hardware_backed'] && $toCapabilities['hardware_backed']) {
                $this->info('  • Target backend provides enhanced hardware-backed security');
            }

            if (! $fromCapabilities['biometric_auth'] && $toCapabilities['biometric_auth']) {
                $this->info('  • Target backend supports biometric authentication');
            }

        } catch (\Exception $e) {
            $this->error("Failed to analyze migration plan: {$e->getMessage()}");
        }
    }

    protected function performMigration(
        CredentialStorageManager $storageManager,
        string $fromType,
        string $toType,
        ?string $provider,
        bool $dryRun
    ): int {
        $this->newLine();

        if ($dryRun) {
            $this->info('🔍 Performing dry run...');
        } else {
            $this->info('🔄 Starting credential migration...');
        }

        $progressBar = null;

        try {
            // Perform migration
            $results = $storageManager->migrate($fromType, $toType, [
                'provider' => $provider,
                'dry_run' => $dryRun,
            ]);

            // Show results
            $this->newLine();

            if ($dryRun) {
                $this->info('✅ Dry run completed');
            } else {
                $this->info('✅ Migration completed');
            }

            $this->newLine();
            $this->line("Total credentials: <comment>{$results['total']}</comment>");
            $this->line("Successfully migrated: <info>{$results['success']}</info>");

            if ($results['failed'] > 0) {
                $this->line("Failed: <error>{$results['failed']}</error>");

                if (! empty($results['errors'])) {
                    $this->newLine();
                    $this->error('Migration errors:');
                    foreach ($results['errors'] as $error) {
                        $this->line("  • {$error}");
                    }
                }
            }

            // Success/failure determination
            if ($results['failed'] === 0) {
                if (! $dryRun) {
                    $this->newLine();
                    $this->info('🎉 All credentials migrated successfully!');

                    // Suggest updating default backend
                    if (confirm("Update default storage backend to {$toType}?")) {
                        $this->info("Consider updating CREDENTIAL_STORAGE_DEFAULT={$toType} in your .env file");
                    }
                }

                return self::SUCCESS;
            } else {
                $successRate = $results['total'] > 0 ?
                    round(($results['success'] / $results['total']) * 100, 1) : 0;

                if ($successRate >= 50) {
                    $this->warn("Migration partially successful ({$successRate}% success rate)");

                    return self::SUCCESS;
                } else {
                    $this->error("Migration mostly failed ({$successRate}% success rate)");

                    return self::FAILURE;
                }
            }

        } catch (\Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function createProgressBar(int $total): \Symfony\Component\Console\Helper\ProgressBar
    {
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        return $progressBar;
    }
}
