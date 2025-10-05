<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\AIModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncProvidersAndModels implements ShouldQueue
{
    use Queueable;

    private const MODELS_API_URL = 'https://models.dev/api';

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting sync of providers and models from models.dev');

        try {
            $this->syncProviders();
            $this->syncModels();
            Log::info('Successfully completed sync of providers and models');
        } catch (\Exception $e) {
            Log::error('Failed to sync providers and models', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync providers from models.dev API
     */
    private function syncProviders(): void
    {
        $response = Http::timeout(60)->get(self::MODELS_API_URL . '.json');
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch data from models.dev API');
        }

        $allData = $response->json();
        
        foreach ($allData as $providerId => $providerData) {
            $provider = Provider::updateOrCreate(
                ['provider' => $providerId],
                [
                    'name' => $providerData['name'] ?? ucfirst($providerId),
                    'description' => null, // Not in API
                    'logo_url' => $this->getProviderLogoUrl($providerId),
                    'metadata' => [
                        'env' => $providerData['env'] ?? [],
                        'npm' => $providerData['npm'] ?? null,
                        'api' => $providerData['api'] ?? null,
                        'doc' => $providerData['doc'] ?? null,
                    ],
                    'synced_at' => now(),
                ]
            );

            Log::info('Synced provider', ['provider' => $provider->provider]);
        }
    }

    /**
     * Sync models from models.dev API
     */
    private function syncModels(): void
    {
        $response = Http::timeout(60)->get(self::MODELS_API_URL . '.json');
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch data from models.dev API');
        }

        $allData = $response->json();
        
        foreach ($allData as $providerId => $providerData) {
            $provider = Provider::where('provider', $providerId)->first();
            
            if (!$provider) {
                Log::warning('Provider not found for models', [
                    'provider' => $providerId
                ]);
                continue;
            }

            $models = $providerData['models'] ?? [];
            
            foreach ($models as $modelId => $modelData) {
                $model = AIModel::updateOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'model_id' => $modelId
                    ],
                    [
                        'name' => $modelData['name'] ?? $modelId,
                        'description' => null, // Not in API
                        'capabilities' => $this->parseCapabilities($modelData),
                        'pricing' => $this->parsePricing($modelData),
                        'limits' => $this->parseLimits($modelData),
                        'metadata' => [
                            'attachment' => $modelData['attachment'] ?? false,
                            'reasoning' => $modelData['reasoning'] ?? false,
                            'temperature' => $modelData['temperature'] ?? false,
                            'tool_call' => $modelData['tool_call'] ?? false,
                            'knowledge' => $modelData['knowledge'] ?? null,
                            'release_date' => $modelData['release_date'] ?? null,
                            'last_updated' => $modelData['last_updated'] ?? null,
                            'modalities' => $modelData['modalities'] ?? [],
                            'open_weights' => $modelData['open_weights'] ?? false,
                        ],
                        'synced_at' => now(),
                    ]
                );

                Log::debug('Synced model', [
                    'model' => $model->model_id,
                    'provider' => $provider->provider
                ]);
            }
        }
    }

    /**
     * Get provider logo URL
     */
    private function getProviderLogoUrl(string $providerId): string
    {
        return "https://models.dev/logos/{$providerId}.svg";
    }

    /**
     * Parse model capabilities from API data
     */
    private function parseCapabilities(array $modelData): array
    {
        $capabilities = [];

        // Check modalities for capability detection
        $modalities = $modelData['modalities'] ?? [];
        $inputModalities = $modalities['input'] ?? [];
        $outputModalities = $modalities['output'] ?? [];

        // Vision capability
        if (in_array('image', $inputModalities) || in_array('video', $inputModalities)) {
            $capabilities[] = 'vision';
        }

        // Audio capability
        if (in_array('audio', $inputModalities) || in_array('audio', $outputModalities)) {
            $capabilities[] = 'audio';
        }

        // Function calling
        if ($modelData['tool_call'] ?? false) {
            $capabilities[] = 'function_calling';
        }

        // Reasoning
        if ($modelData['reasoning'] ?? false) {
            $capabilities[] = 'reasoning';
        }

        // Text generation (most models support this)
        if (in_array('text', $outputModalities)) {
            $capabilities[] = 'text';
        }

        // Image generation
        if (in_array('image', $outputModalities)) {
            $capabilities[] = 'image_generation';
        }

        // Determine if it's an embedding model based on model ID
        $modelId = strtolower($modelData['id'] ?? '');
        if (str_contains($modelId, 'embed') || str_contains($modelId, 'embedding')) {
            $capabilities[] = 'embedding';
        }

        return array_unique($capabilities);
    }

    /**
     * Parse pricing information from API data
     */
    private function parsePricing(array $modelData): array
    {
        $cost = $modelData['cost'] ?? [];
        
        return [
            'input_cost_per_million' => $cost['input'] ?? null,
            'output_cost_per_million' => $cost['output'] ?? null,
            'cache_read_cost_per_million' => $cost['cache_read'] ?? null,
            'cache_write_cost_per_million' => $cost['cache_write'] ?? null,
            'reasoning_cost_per_million' => $cost['reasoning'] ?? null,
            'currency' => 'USD',
        ];
    }

    /**
     * Parse limits from API data
     */
    private function parseLimits(array $modelData): array
    {
        $limit = $modelData['limit'] ?? [];
        
        return [
            'context_length' => $limit['context'] ?? null,
            'max_output' => $limit['output'] ?? null,
        ];
    }
}
