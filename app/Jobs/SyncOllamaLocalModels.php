<?php

namespace App\Jobs;

use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncOllamaLocalModels implements ShouldQueue
{
    use Queueable;

    private const OLLAMA_LOCAL_API_URL = 'http://localhost:11434/api/tags';

    private const PROVIDER_ID = 'ollama-local';

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
        Log::info('Starting sync of local Ollama models');

        try {
            $this->ensureOllamaProvider();
            $this->syncLocalModels();
            Log::info('Successfully completed sync of local Ollama models');
        } catch (\Exception $e) {
            Log::error('Failed to sync local Ollama models', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Ensure the Ollama provider exists
     */
    private function ensureOllamaProvider(): void
    {
        AiProvider::updateOrCreate(
            ['provider' => self::PROVIDER_ID],
            [
                'name' => 'Ollama (Local)',
                'description' => 'Local Ollama installation models',
                'logo_url' => 'https://ollama.com/public/icon-32x32.png',
                'metadata' => [
                    'type' => 'local',
                    'api_endpoint' => self::OLLAMA_LOCAL_API_URL,
                    'doc' => 'https://ollama.com/docs',
                ],
                'synced_at' => now(),
            ]
        );
    }

    /**
     * Sync models from local Ollama API
     */
    private function syncLocalModels(): void
    {
        $response = Http::timeout(30)->get(self::OLLAMA_LOCAL_API_URL);

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch data from local Ollama API: '.$response->status());
        }

        $data = $response->json();
        $models = $data['models'] ?? [];

        $provider = AiProvider::where('provider', self::PROVIDER_ID)->first();

        if (! $provider) {
            throw new \Exception('Ollama provider not found');
        }

        foreach ($models as $modelData) {
            $modelId = $modelData['name'] ?? $modelData['model'] ?? '';

            if (empty($modelId)) {
                Log::warning('Skipping model with no ID', ['data' => $modelData]);

                continue;
            }

            // Build update data, excluding description to preserve custom values
            $updateData = [
                'name' => $this->getModelDisplayName($modelId),
                'capabilities' => $this->parseCapabilities($modelData),
                'pricing' => [], // Local models have no pricing
                'limits' => $this->parseLimits($modelData),
                'metadata' => [
                    'size' => $modelData['size'] ?? null,
                    'digest' => $modelData['digest'] ?? null,
                    'modified_at' => $modelData['modified_at'] ?? null,
                    'format' => $modelData['details']['format'] ?? null,
                    'family' => $modelData['details']['family'] ?? null,
                    'families' => $modelData['details']['families'] ?? [],
                    'parameter_size' => $modelData['details']['parameter_size'] ?? null,
                    'quantization_level' => $modelData['details']['quantization_level'] ?? null,
                    'parent_model' => $modelData['details']['parent_model'] ?? null,
                ],
                'synced_at' => now(),
            ];

            // Only set description for new models
            $existingModel = AiModel::where('provider_id', $provider->id)
                ->where('model_id', $modelId)
                ->first();

            if (! $existingModel) {
                $updateData['description'] = $this->getModelDescription($modelData);
            }

            $model = AiModel::updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'model_id' => $modelId,
                ],
                $updateData
            );

            Log::debug('Synced Ollama model', [
                'model' => $model->model_id,
                'provider' => $provider->provider,
            ]);
        }
    }

    /**
     * Get display name for model
     */
    private function getModelDisplayName(string $modelId): string
    {
        // Remove :latest suffix for cleaner display
        $name = str_replace(':latest', '', $modelId);

        // Capitalize and format common model names
        $name = ucfirst($name);
        $name = str_replace('-', ' ', $name);

        return $name;
    }

    /**
     * Get model description based on family/name
     */
    private function getModelDescription(array $modelData): ?string
    {
        $family = $modelData['details']['family'] ?? '';
        $parameterSize = $modelData['details']['parameter_size'] ?? '';

        $descriptions = [
            'llama' => 'Meta\'s Llama language model',
            'qwen' => 'Alibaba\'s Qwen language model',
            'gemma' => 'Google\'s Gemma language model',
            'mistral' => 'Mistral AI language model',
            'phi' => 'Microsoft\'s Phi language model',
            'deepseek' => 'DeepSeek language model',
            'codellama' => 'Meta\'s Code Llama model for code generation',
        ];

        $baseDescription = $descriptions[$family] ?? 'Language model';

        if ($parameterSize) {
            $baseDescription .= " ({$parameterSize})";
        }

        return $baseDescription;
    }

    /**
     * Parse model capabilities from API data
     */
    private function parseCapabilities(array $modelData): array
    {
        $capabilities = ['text']; // All Ollama models support text

        $family = strtolower($modelData['details']['family'] ?? '');
        $modelId = strtolower($modelData['name'] ?? $modelData['model'] ?? '');

        // Vision models
        if (str_contains($modelId, 'vision') || str_contains($modelId, 'llava') || str_contains($modelId, 'moondream')) {
            $capabilities[] = 'vision';
        }

        // Code models
        if (str_contains($modelId, 'code') || str_contains($modelId, 'coder') || $family === 'codellama') {
            $capabilities[] = 'code';
        }

        // Embedding models
        if (str_contains($modelId, 'embed') || str_contains($modelId, 'embedding')) {
            $capabilities[] = 'embedding';
        }

        // Tool use (common in newer models)
        if (str_contains($modelId, 'tool') || in_array($family, ['qwen', 'llama', 'mistral'])) {
            $capabilities[] = 'function_calling';
        }

        return array_unique($capabilities);
    }

    /**
     * Parse limits from API data
     */
    private function parseLimits(array $modelData): array
    {
        return [
            'context_length' => null, // Not provided by Ollama API
            'max_output' => null, // Not provided by Ollama API
        ];
    }
}
