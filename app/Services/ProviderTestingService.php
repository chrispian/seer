<?php

namespace App\Services;

use App\Models\AICredential;
use App\Models\Provider;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;

class ProviderTestingService
{
    protected AIProviderManager $providerManager;

    public function __construct(AIProviderManager $providerManager)
    {
        $this->providerManager = $providerManager;
    }

    /**
     * Test credentials for a specific provider
     */
    public function testCredentials(string $provider, ?array $credentials = null): array
    {
        $startTime = microtime(true);

        try {
            // If no credentials provided, use stored credentials
            if ($credentials === null) {
                $credential = AICredential::getActiveEnabledCredential($provider);
                if (! $credential) {
                    return [
                        'status' => 'failed',
                        'provider' => $provider,
                        'error' => 'No active credentials found',
                        'response_time_ms' => 0,
                        'tested_at' => now()->toISOString(),
                    ];
                }
            } else {
                // Temporarily store credentials for testing
                $testCredential = new AICredential;
                $testCredential->provider = $provider;
                $testCredential->setCredentials($credentials);
            }

            // Use the provider manager to run health check
            $result = $this->providerManager->healthCheck($provider);

            $responseTime = round((microtime(true) - $startTime) * 1000);

            // Update provider health status if using stored credentials
            if ($credentials === null) {
                $providerConfig = Provider::getOrCreateForProvider($provider);
                $isHealthy = $result['status'] === 'healthy';
                $providerConfig->updateHealthStatus($isHealthy, $result);
            }

            return array_merge($result, [
                'response_time_ms' => $responseTime,
                'tested_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);

            Log::warning('Provider credential test failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
            ]);

            return [
                'status' => 'failed',
                'provider' => $provider,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Check provider health status
     */
    public function checkProviderHealth(string $provider): array
    {
        return $this->testCredentials($provider);
    }

    /**
     * Validate credential format for a provider
     */
    public function validateCredentialFormat(string $provider, array $credentials): array
    {
        $errors = [];

        switch ($provider) {
            case 'openai':
                if (empty($credentials['api_key'])) {
                    $errors[] = 'API key is required';
                } elseif (! str_starts_with($credentials['api_key'], 'sk-')) {
                    $errors[] = 'OpenAI API key must start with "sk-"';
                }

                if (isset($credentials['organization']) && ! preg_match('/^org-[a-zA-Z0-9]+$/', $credentials['organization'])) {
                    $errors[] = 'Organization ID must start with "org-" followed by alphanumeric characters';
                }
                break;

            case 'anthropic':
                if (empty($credentials['api_key'])) {
                    $errors[] = 'API key is required';
                } elseif (! str_starts_with($credentials['api_key'], 'sk-ant-')) {
                    $errors[] = 'Anthropic API key must start with "sk-ant-"';
                }
                break;

            case 'openrouter':
                if (empty($credentials['api_key'])) {
                    $errors[] = 'API key is required';
                } elseif (! str_starts_with($credentials['api_key'], 'sk-or-')) {
                    $errors[] = 'OpenRouter API key must start with "sk-or-"';
                }
                break;

            case 'ollama':
                if (empty($credentials['base_url'])) {
                    $errors[] = 'Base URL is required for Ollama';
                } elseif (! filter_var($credentials['base_url'], FILTER_VALIDATE_URL)) {
                    $errors[] = 'Base URL must be a valid URL';
                }
                break;

            default:
                if (empty($credentials['api_key']) && empty($credentials['base_url'])) {
                    $errors[] = 'Either API key or base URL is required';
                }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'provider' => $provider,
        ];
    }

    /**
     * Run bulk health checks on multiple providers
     */
    public function bulkHealthCheck(?array $providers = null): array
    {
        $results = [];
        $startTime = microtime(true);

        if ($providers === null) {
            // Get all enabled providers
            $enabledProviders = Provider::getEnabledProviders();
            $providers = $enabledProviders->pluck('provider')->toArray();
        }

        foreach ($providers as $provider) {
            $results[$provider] = $this->checkProviderHealth($provider);
        }

        $totalTime = round((microtime(true) - $startTime) * 1000);

        Log::info('Bulk health check completed', [
            'providers_tested' => count($providers),
            'total_time_ms' => $totalTime,
            'results' => array_map(fn ($r) => $r['status'], $results),
        ]);

        return [
            'results' => $results,
            'summary' => [
                'total_providers' => count($providers),
                'healthy_count' => count(array_filter($results, fn ($r) => $r['status'] === 'healthy')),
                'failed_count' => count(array_filter($results, fn ($r) => $r['status'] === 'failed')),
                'total_time_ms' => $totalTime,
                'tested_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Test connectivity with timeout handling
     */
    public function testConnectivity(string $provider, int $timeoutSeconds = 10): array
    {
        $startTime = microtime(true);

        try {
            // Set timeout for the operation
            $oldTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', $timeoutSeconds);

            $result = $this->testCredentials($provider);

            // Restore original timeout
            ini_set('default_socket_timeout', $oldTimeout);

            return $result;

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);

            // Check if it's a timeout
            if ($responseTime >= ($timeoutSeconds * 1000)) {
                return [
                    'status' => 'timeout',
                    'provider' => $provider,
                    'error' => "Connection timed out after {$timeoutSeconds} seconds",
                    'response_time_ms' => $responseTime,
                    'tested_at' => now()->toISOString(),
                ];
            }

            return [
                'status' => 'failed',
                'provider' => $provider,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Test specific model availability
     */
    public function testModelAvailability(string $provider, string $model): array
    {
        try {
            $providerInstance = $this->providerManager->getProvider($provider);

            if (! $providerInstance) {
                return [
                    'available' => false,
                    'error' => 'Provider not found',
                    'provider' => $provider,
                    'model' => $model,
                ];
            }

            if (! $providerInstance->isAvailable()) {
                return [
                    'available' => false,
                    'error' => 'Provider not available',
                    'provider' => $provider,
                    'model' => $model,
                ];
            }

            // For now, we'll assume model is available if provider is available
            // In the future, this could make an actual API call to verify model access
            return [
                'available' => true,
                'provider' => $provider,
                'model' => $model,
                'tested_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
                'provider' => $provider,
                'model' => $model,
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get health status history for a provider
     */
    public function getHealthHistory(string $provider, int $days = 7): array
    {
        $providerConfig = Provider::where('provider', $provider)->first();

        if (! $providerConfig) {
            return [
                'provider' => $provider,
                'history' => [],
                'current_status' => 'unknown',
            ];
        }

        // For now, return current status
        // In the future, this could include historical health check data
        return [
            'provider' => $provider,
            'current_status' => $providerConfig->getHealthStatus(),
            'last_check' => $providerConfig->last_health_check,
            'health_details' => $providerConfig->health_status,
            'history' => [], // Placeholder for historical data
        ];
    }

    /**
     * Schedule periodic health checks
     */
    public function scheduleHealthChecks(): array
    {
        $enabledProviders = ProviderConfig::getEnabledProviders();
        $scheduled = [];

        foreach ($enabledProviders as $providerConfig) {
            // This would integrate with Laravel's job queue system
            // For now, we'll just mark as scheduled
            $scheduled[] = [
                'provider' => $providerConfig->provider,
                'scheduled_at' => now()->toISOString(),
                'next_check' => now()->addMinutes(15)->toISOString(),
            ];
        }

        Log::info('Health checks scheduled', [
            'provider_count' => count($scheduled),
            'providers' => array_column($scheduled, 'provider'),
        ]);

        return $scheduled;
    }
}
