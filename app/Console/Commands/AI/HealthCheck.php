<?php

namespace App\Console\Commands\AI;

use App\Services\AI\AIProviderManager;
use Illuminate\Console\Command;

class HealthCheck extends Command
{
    protected $signature = 'ai:health
                            {provider? : Specific provider to check}
                            {--detailed : Show detailed output}';

    protected $description = 'Check health status of AI providers';

    public function handle(AIProviderManager $providerManager): int
    {
        $provider = $this->argument('provider');
        $verbose = $this->option('detailed');

        if ($provider) {
            return $this->checkSingleProvider($providerManager, $provider, $verbose);
        }

        return $this->checkAllProviders($providerManager, $verbose);
    }

    protected function checkSingleProvider(AIProviderManager $providerManager, string $providerName, bool $verbose): int
    {
        $this->info("Checking health of {$providerName}...");

        try {
            $result = $providerManager->healthCheck($providerName);
            $this->displayHealthResult($providerName, $result, $verbose);

            return $result['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;

        } catch (\Exception $e) {
            $this->error("Health check failed for {$providerName}: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function checkAllProviders(AIProviderManager $providerManager, bool $verbose): int
    {
        $this->info('Checking health of all AI providers...');
        $this->newLine();

        $results = $providerManager->healthCheckAll();
        $overallStatus = self::SUCCESS;

        $headers = ['Provider', 'Status', 'Response Time', 'Message'];
        $rows = [];

        foreach ($results as $providerName => $result) {
            $status = $result['status'] === 'healthy' ? '<fg=green>Healthy</>' : '<fg=red>Failed</>';
            $responseTime = isset($result['response_time_ms']) ? "{$result['response_time_ms']}ms" : 'N/A';
            $message = $result['message'] ?? $result['error'] ?? 'Unknown';

            // Truncate long messages for table display
            if (! $verbose && strlen($message) > 50) {
                $message = substr($message, 0, 47).'...';
            }

            $rows[] = [$providerName, $status, $responseTime, $message];

            if ($result['status'] !== 'healthy') {
                $overallStatus = self::FAILURE;
            }
        }

        $this->table($headers, $rows);

        // Summary
        $this->newLine();
        $healthyCount = collect($results)->where('status', 'healthy')->count();
        $totalCount = count($results);

        if ($healthyCount === $totalCount) {
            $this->info("✅ All {$totalCount} providers are healthy");
        } else {
            $failedCount = $totalCount - $healthyCount;
            $this->warn("⚠️  {$healthyCount}/{$totalCount} providers healthy, {$failedCount} failed");
        }

        // Show verbose details if requested
        if ($verbose) {
            $this->newLine();
            $this->info('Detailed Results:');
            foreach ($results as $providerName => $result) {
                $this->displayHealthResult($providerName, $result, true);
                $this->newLine();
            }
        }

        return $overallStatus;
    }

    protected function displayHealthResult(string $providerName, array $result, bool $verbose): void
    {
        $status = $result['status'];
        $responseTime = $result['response_time_ms'] ?? 0;

        if ($status === 'healthy') {
            $this->info("✅ {$providerName}: Healthy ({$responseTime}ms)");
        } else {
            $this->error("❌ {$providerName}: Failed ({$responseTime}ms)");
        }

        if ($verbose) {
            if (isset($result['message'])) {
                $this->line("   Message: {$result['message']}");
            }
            if (isset($result['error'])) {
                $this->line("   Error: {$result['error']}");
            }
            if (isset($result['version'])) {
                $this->line("   Version: {$result['version']}");
            }
        }
    }
}
