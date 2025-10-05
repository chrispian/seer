<?php

namespace App\Console\Commands;

use App\Services\VectorCapabilityDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareDesktopDeployment extends Command
{
    protected $signature = 'desktop:prepare 
                           {--check-only : Only check deployment readiness}
                           {--force : Force preparation even if issues exist}';

    protected $description = 'Prepare application for NativePHP desktop deployment with vector capabilities';

    public function handle()
    {
        $this->info('Desktop Deployment Preparation');
        $this->info('=============================');

        if ($this->option('check-only')) {
            return $this->checkDeploymentReadiness();
        }

        return $this->prepareDeployment();
    }

    protected function checkDeploymentReadiness(): int
    {
        $this->info('Checking deployment readiness...');

        $issues = [];
        $warnings = [];

        // Check SQLite configuration
        if (config('database.default') !== 'sqlite') {
            $issues[] = 'Database is not configured for SQLite (required for desktop deployment)';
        }

        // Check vector configuration
        $vectorConfig = config('vectors.default');
        if (! in_array($vectorConfig, ['auto', 'sqlite'])) {
            $warnings[] = "Vector driver is set to '{$vectorConfig}' - consider 'auto' for desktop deployment";
        }

        // Check extension packaging
        $extensionsDir = storage_path('extensions');
        if (! File::exists($extensionsDir)) {
            $warnings[] = 'Vector extensions directory not found - run packaging script first';
        }

        // Check capability detection
        try {
            $detector = app(VectorCapabilityDetector::class);
            $capabilities = $detector->detectCapabilities();

            if (! ($capabilities['text_search']['available'] ?? false)) {
                $warnings[] = 'Text search capabilities not fully available';
            }
        } catch (\Exception $e) {
            $issues[] = 'Capability detection failed: '.$e->getMessage();
        }

        // Check NativePHP configuration template
        $envTemplate = storage_path('nativephp.env');
        if (! File::exists($envTemplate)) {
            $warnings[] = 'NativePHP environment template not found - run packaging script first';
        }

        // Report results
        if (empty($issues) && empty($warnings)) {
            $this->info('✓ Deployment readiness check passed - ready for desktop deployment');

            return Command::SUCCESS;
        }

        if (! empty($issues)) {
            $this->error('Deployment readiness issues found:');
            foreach ($issues as $issue) {
                $this->line("  ✗ {$issue}");
            }
        }

        if (! empty($warnings)) {
            $this->warn('Deployment warnings:');
            foreach ($warnings as $warning) {
                $this->line("  ⚠ {$warning}");
            }
        }

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }

    protected function prepareDeployment(): int
    {
        $this->info('Preparing desktop deployment...');

        // Check readiness first
        $checkResult = $this->checkDeploymentReadiness();
        if ($checkResult !== Command::SUCCESS && ! $this->option('force')) {
            $this->error('Deployment readiness check failed. Use --force to proceed anyway.');

            return Command::FAILURE;
        }

        $steps = [
            'Configuring SQLite database' => [$this, 'configureSQLiteDatabase'],
            'Setting up vector configuration' => [$this, 'setupVectorConfiguration'],
            'Preparing extension packaging' => [$this, 'prepareExtensionPackaging'],
            'Creating desktop environment' => [$this, 'createDesktopEnvironment'],
            'Running database migrations' => [$this, 'runDatabaseMigrations'],
            'Validating deployment' => [$this, 'validateDeployment'],
        ];

        foreach ($steps as $description => $callback) {
            $this->info("→ {$description}...");

            try {
                $result = call_user_func($callback);
                if ($result) {
                    $this->line("  ✓ {$description}");
                } else {
                    $this->warn("  ⚠ {$description} - completed with warnings");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ {$description} failed: ".$e->getMessage());

                if (! $this->option('force')) {
                    return Command::FAILURE;
                }
            }
        }

        $this->newLine();
        $this->info('✓ Desktop deployment preparation complete!');
        $this->info('Next steps:');
        $this->line('  1. Build your NativePHP application');
        $this->line('  2. Copy storage/nativephp.env to .env in deployment');
        $this->line('  3. Test with: php artisan vector:status --detailed');

        return Command::SUCCESS;
    }

    protected function configureSQLiteDatabase(): bool
    {
        $databasePath = database_path('fragments.sqlite');

        if (! File::exists($databasePath)) {
            // Create SQLite database file
            File::put($databasePath, '');
            $this->line("    Created SQLite database: {$databasePath}");
        }

        return true;
    }

    protected function setupVectorConfiguration(): bool
    {
        // Create optimized configuration for desktop
        $desktopConfig = [
            'VECTOR_STORE_DRIVER' => 'auto',
            'SQLITE_VEC_AUTO_LOAD' => 'true',
            'SQLITE_VEC_EXTENSION_PATH' => 'auto-detect',
            'HYBRID_SEARCH_MAX_RESULTS' => '25',
            'VECTOR_BATCH_SIZE' => '50',
            'VECTOR_ENABLE_CONCURRENT' => 'false',
            'VECTOR_ENABLE_QUERY_CACHE' => 'true',
        ];

        $envPath = base_path('.env');
        $envContent = File::exists($envPath) ? File::get($envPath) : '';

        foreach ($desktopConfig as $key => $value) {
            if (! str_contains($envContent, $key)) {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);

        return true;
    }

    protected function prepareExtensionPackaging(): bool
    {
        $scriptPath = base_path('scripts/package-vector-extensions.sh');

        if (! File::exists($scriptPath)) {
            $this->warn('    Extension packaging script not found');

            return false;
        }

        // Run the packaging script
        $this->call('exec', ['command' => $scriptPath]);

        return true;
    }

    protected function createDesktopEnvironment(): bool
    {
        $templatePath = storage_path('nativephp.env');

        if (! File::exists($templatePath)) {
            $this->warn('    NativePHP environment template not found');

            return false;
        }

        // Copy template for reference
        $deploymentEnvPath = base_path('.env.desktop');
        File::copy($templatePath, $deploymentEnvPath);

        $this->line('    Created desktop environment template: .env.desktop');

        return true;
    }

    protected function runDatabaseMigrations(): bool
    {
        try {
            $this->call('migrate', ['--force' => true]);

            return true;
        } catch (\Exception $e) {
            $this->warn('    Migration failed: '.$e->getMessage());

            return false;
        }
    }

    protected function validateDeployment(): bool
    {
        try {
            // Test vector capabilities
            $this->call('vector:status');

            // Test configuration
            $this->call('vector:config', ['action' => 'validate']);

            return true;
        } catch (\Exception $e) {
            $this->warn('    Validation failed: '.$e->getMessage());

            return false;
        }
    }
}
