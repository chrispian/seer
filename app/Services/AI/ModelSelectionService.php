<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\Vault;
use Illuminate\Support\Facades\Log;

class ModelSelectionService
{
    protected array $providers;

    protected array $selectionStrategy;

    protected string $defaultProvider;

    protected string $defaultTextModel;

    protected string $fallbackProvider;

    protected string $fallbackTextModel;

    public function __construct()
    {
        $config = config('fragments.models');

        $this->providers = $config['providers'] ?? [];
        $this->selectionStrategy = $config['selection_strategy'] ?? [];
        $this->defaultProvider = $config['default_provider'] ?? 'openai';
        $this->defaultTextModel = $config['default_text_model'] ?? 'gpt-4o-mini';
        $this->fallbackProvider = $config['fallback_provider'] ?? 'ollama';
        $this->fallbackTextModel = $config['fallback_text_model'] ?? 'llama3:latest';
    }

    /**
     * Select the appropriate model for a given context
     */
    public function selectModel(array $context = []): array
    {
        $selections = $this->gatherSelections($context);
        $selectedModel = $this->applySelectionStrategy($selections);

        // Validate the selected model is available
        if (! $this->isModelAvailable($selectedModel['provider'], $selectedModel['model'])) {
            Log::warning('Selected model not available, falling back', [
                'selected' => $selectedModel,
                'context' => $context,
            ]);

            $selectedModel = $this->getFallbackModel($context);
        }

        Log::info('Model selected for AI operation', [
            'provider' => $selectedModel['provider'],
            'model' => $selectedModel['model'],
            'context' => $context,
            'selections' => $selections,
        ]);

        return $selectedModel;
    }

    /**
     * Select model specifically for embeddings
     */
    public function selectEmbeddingModel(array $context = []): array
    {
        $context['operation_type'] = 'embedding';

        return $this->selectModelByType($context, 'embedding_models');
    }

    /**
     * Select model specifically for text generation/inference
     */
    public function selectTextModel(array $context = []): array
    {
        $context['operation_type'] = 'text';

        return $this->selectModelByType($context, 'text_models');
    }

    /**
     * Select model by specific type (text_models or embedding_models)
     */
    protected function selectModelByType(array $context, string $modelType): array
    {
        $selections = $this->gatherSelections($context);

        // Filter selections to only include providers that support the model type
        $filteredSelections = [];
        foreach ($selections as $key => $selection) {
            if ($this->providerSupportsModelType($selection['provider'], $modelType)) {
                $filteredSelections[$key] = $selection;
            }
        }

        if (empty($filteredSelections)) {
            // No provider supports this model type, use fallback
            $fallbackSelection = $this->getFallbackForModelType($modelType);

            return $fallbackSelection;
        }

        $selectedModel = $this->applySelectionStrategy($filteredSelections);

        // Ensure the selected model is of the correct type
        if (! $this->isModelOfType($selectedModel['provider'], $selectedModel['model'], $modelType)) {
            $selectedModel['model'] = $this->getDefaultModelForType($selectedModel['provider'], $modelType);
        }

        return $selectedModel;
    }

    /**
     * Gather potential selections from various sources
     */
    protected function gatherSelections(array $context): array
    {
        $selections = [];

        // Command override (highest priority)
        if (! empty($context['command_model_override'])) {
            $override = $this->parseModelOverride($context['command_model_override']);
            if ($override) {
                $selections['command_override'] = [
                    'provider' => $override['provider'],
                    'model' => $override['model'],
                    'priority' => $this->selectionStrategy['command_override'] ?? 100,
                    'source' => 'command_override',
                ];
            }
        }

        // Project preference
        if (! empty($context['project_id'])) {
            $projectModel = $this->getProjectModelPreference($context['project_id']);
            if ($projectModel) {
                $selections['project_preference'] = [
                    'provider' => $projectModel['provider'],
                    'model' => $projectModel['model'],
                    'priority' => $this->selectionStrategy['project_preference'] ?? 80,
                    'source' => 'project_preference',
                ];
            }
        }

        // Vault preference
        if (! empty($context['vault'])) {
            $vaultModel = $this->getVaultModelPreference($context['vault']);
            if ($vaultModel) {
                $selections['vault_preference'] = [
                    'provider' => $vaultModel['provider'],
                    'model' => $vaultModel['model'],
                    'priority' => $this->selectionStrategy['vault_preference'] ?? 60,
                    'source' => 'vault_preference',
                ];
            }
        }

        // Global default
        $selections['global_default'] = [
            'provider' => $this->defaultProvider,
            'model' => $this->defaultTextModel,
            'priority' => $this->selectionStrategy['global_default'] ?? 40,
            'source' => 'global_default',
        ];

        // Fallback
        $selections['fallback'] = [
            'provider' => $this->fallbackProvider,
            'model' => $this->fallbackTextModel,
            'priority' => $this->selectionStrategy['fallback'] ?? 20,
            'source' => 'fallback',
        ];

        return $selections;
    }

    /**
     * Apply selection strategy to choose the best model
     */
    protected function applySelectionStrategy(array $selections): array
    {
        if (empty($selections)) {
            return $this->getFallbackModel();
        }

        // Sort by priority (highest first)
        uasort($selections, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        // Return the highest priority available model
        foreach ($selections as $selection) {
            if ($this->isModelAvailable($selection['provider'], $selection['model'])) {
                return [
                    'provider' => $selection['provider'],
                    'model' => $selection['model'],
                    'source' => $selection['source'],
                ];
            }
        }

        // If no selection is available, return fallback
        return $this->getFallbackModel();
    }

    /**
     * Check if a model is available (provider is configured)
     */
    protected function isModelAvailable(string $provider, string $model): bool
    {
        if (! isset($this->providers[$provider])) {
            return false;
        }

        $providerConfig = $this->providers[$provider];

        // Check if required config keys are set
        foreach ($providerConfig['config_keys'] ?? [] as $configKey) {
            // For providers that don't need API keys (like Ollama), just check if base URL is configured
            if ($configKey === 'OLLAMA_BASE_URL') {
                if (empty(config("services.{$provider}.base"))) {
                    return false;
                }
            } else {
                // For other providers, check for API keys - need at least one valid configuration
                $hasConfigKey = ! empty(config("services.{$provider}.key"));
                $hasEnvKey = ! empty(env($configKey));
                if (! $hasConfigKey && ! $hasEnvKey) {
                    return false;
                }
            }
        }

        // Check if model exists in provider's model list
        $allModels = array_merge(
            array_keys($providerConfig['text_models'] ?? []),
            array_keys($providerConfig['embedding_models'] ?? [])
        );

        return in_array($model, $allModels);
    }

    /**
     * Check if provider supports a specific model type
     */
    protected function providerSupportsModelType(string $provider, string $modelType): bool
    {
        return ! empty($this->providers[$provider][$modelType] ?? []);
    }

    /**
     * Check if a specific model is of the given type
     */
    protected function isModelOfType(string $provider, string $model, string $modelType): bool
    {
        return isset($this->providers[$provider][$modelType][$model]);
    }

    /**
     * Get default model for a provider and type
     */
    protected function getDefaultModelForType(string $provider, string $modelType): string
    {
        $models = $this->providers[$provider][$modelType] ?? [];

        if (empty($models)) {
            return $modelType === 'text_models' ? $this->defaultTextModel : 'text-embedding-3-small';
        }

        return array_key_first($models);
    }

    /**
     * Get fallback model for specific model type
     */
    protected function getFallbackForModelType(string $modelType): array
    {
        if ($modelType === 'embedding_models') {
            return [
                'provider' => 'openai',
                'model' => 'text-embedding-3-small',
                'source' => 'fallback_embedding',
            ];
        }

        return [
            'provider' => $this->fallbackProvider,
            'model' => $this->fallbackTextModel,
            'source' => 'fallback_text',
        ];
    }

    /**
     * Get fallback model
     */
    protected function getFallbackModel(array $context = []): array
    {
        return [
            'provider' => $this->fallbackProvider,
            'model' => $this->fallbackTextModel,
            'source' => 'fallback',
        ];
    }

    /**
     * Parse command model override string (e.g., "openai:gpt-4o" or "ollama:llama3:latest")
     */
    protected function parseModelOverride(string $override): ?array
    {
        if (strpos($override, ':') === false) {
            return null;
        }

        $parts = explode(':', $override, 2);
        $provider = $parts[0];
        $model = $parts[1];

        if (isset($this->providers[$provider])) {
            return ['provider' => $provider, 'model' => $model];
        }

        return null;
    }

    /**
     * Get model preference for a project
     */
    protected function getProjectModelPreference(int $projectId): ?array
    {
        try {
            $project = Project::find($projectId);
            if ($project && ! empty($project->metadata['ai_model'])) {
                $modelData = $project->metadata['ai_model'];

                return [
                    'provider' => $modelData['provider'] ?? $this->defaultProvider,
                    'model' => $modelData['model'] ?? $this->defaultTextModel,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get project model preference', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get model preference for a vault
     */
    protected function getVaultModelPreference(string $vault): ?array
    {
        try {
            $vaultModel = Vault::where('name', $vault)->first();
            if ($vaultModel && ! empty($vaultModel->metadata['ai_model'])) {
                $modelData = $vaultModel->metadata['ai_model'];

                return [
                    'provider' => $modelData['provider'] ?? $this->defaultProvider,
                    'model' => $modelData['model'] ?? $this->defaultTextModel,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get vault model preference', [
                'vault' => $vault,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get models for a specific provider
     */
    public function getModelsForProvider(string $provider): array
    {
        return $this->providers[$provider] ?? [];
    }

    /**
     * Get model display information
     */
    public function getModelDisplayInfo(string $provider, string $model): array
    {
        $providerData = $this->providers[$provider] ?? [];

        // Check in text models first
        if (isset($providerData['text_models'][$model])) {
            return [
                'provider_name' => $providerData['name'] ?? $provider,
                'model_name' => $providerData['text_models'][$model]['name'] ?? $model,
                'type' => 'text',
            ];
        }

        // Check in embedding models
        if (isset($providerData['embedding_models'][$model])) {
            return [
                'provider_name' => $providerData['name'] ?? $provider,
                'model_name' => $providerData['embedding_models'][$model]['name'] ?? $model,
                'type' => 'embedding',
            ];
        }

        // Fallback
        return [
            'provider_name' => $providerData['name'] ?? $provider,
            'model_name' => $model,
            'type' => 'unknown',
        ];
    }
}
