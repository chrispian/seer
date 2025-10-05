<?php

namespace App\Services\AI;

use App\Models\AICredential;
use App\Models\Project;
use App\Models\Provider;
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

    protected array $aiParameters;

    protected array $operations;

    public function __construct()
    {
        $config = config('fragments.models');

        $this->providers = $config['providers'] ?? [];
        $this->selectionStrategy = $config['selection_strategy'] ?? [];
        $this->defaultProvider = $config['default_provider'] ?? 'openai';
        $this->defaultTextModel = $config['default_text_model'] ?? 'gpt-4o-mini';
        $this->fallbackProvider = $config['fallback_provider'] ?? 'ollama';
        $this->fallbackTextModel = $config['fallback_text_model'] ?? 'llama3:latest';
        $this->aiParameters = $config['parameters'] ?? [];
        $this->operations = $config['operations'] ?? [];
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

        // Check for operation-specific configuration first
        $operationSelection = $this->selectModelForOperation($context);
        if ($operationSelection) {
            // Add AI parameters based on operation context
            $operationSelection['parameters'] = $this->getAIParameters($context);

            return $operationSelection;
        }

        // Fall back to standard model selection
        $selection = $this->selectModelByType($context, 'embedding_models');

        // Add AI parameters for embedding operations
        $selection['parameters'] = $this->getAIParameters($context);

        return $selection;
    }

    /**
     * Select model specifically for text generation/inference
     */
    public function selectTextModel(array $context = []): array
    {
        $context['operation_type'] = 'text';

        // Check for operation-specific configuration first
        $operationSelection = $this->selectModelForOperation($context);
        if ($operationSelection) {
            // Add AI parameters based on operation context
            $operationSelection['parameters'] = $this->getAIParameters($context);

            return $operationSelection;
        }

        // Fall back to standard model selection
        $selection = $this->selectModelByType($context, 'text_models');

        // Add AI parameters based on operation context
        $selection['parameters'] = $this->getAIParameters($context);

        return $selection;
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

        if (($context['command'] ?? null) === 'type_inference') {
            $selections['fallback']['priority'] = max($selections['fallback']['priority'], 110);
        }

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
        // Check if provider exists in config
        if (! isset($this->providers[$provider])) {
            return false;
        }

        // Check if provider is enabled in database
        $providerConfig = Provider::where('provider', $provider)->first();
        if (! $providerConfig || ! $providerConfig->enabled) {
            return false;
        }

        // Check if provider has valid credentials
        $credential = AICredential::getActiveEnabledCredential($provider);
        if (! $credential) {
            // For Ollama, check if base URL is configured instead of credentials
            if ($provider === 'ollama') {
                if (empty(config('services.ollama.base'))) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // Check if model exists in provider's model list
        $capabilities = $providerConfig->getCapabilities();
        $allModels = array_merge(
            array_keys($capabilities['text_models'] ?? []),
            array_keys($capabilities['embedding_models'] ?? [])
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
     * Select model for specific operation if configured
     */
    protected function selectModelForOperation(array $context): ?array
    {
        $command = $context['command'] ?? null;
        $operationType = $context['operation_type'] ?? 'text';

        // Map commands to operation types
        $operation = $this->mapCommandToOperation($command, $operationType);

        // Check if operation is enabled
        if (! $this->isOperationEnabled($operation)) {
            throw new \Exception("AI operation '{$operation}' is disabled");
        }

        // Get operation-specific configuration
        $operationConfig = $this->operations[$operation] ?? [];

        // Check if operation has specific provider/model configured
        $provider = $operationConfig['provider'] ?? null;
        $model = $operationConfig['model'] ?? null;

        if (! $provider && ! $model) {
            return null; // No operation-specific config, use standard selection
        }

        // Use operation-specific provider, or fall back to default
        $finalProvider = $provider ?: $this->defaultProvider;

        // Use operation-specific model, or get default for the provider based on operation type
        $finalModel = $model ?: $this->getDefaultModelForProviderAndOperation($finalProvider, $operation);

        // Validate the selected model is available
        if (! $this->isModelAvailable($finalProvider, $finalModel)) {
            Log::warning('Operation-specific model not available, falling back to standard selection', [
                'operation' => $operation,
                'provider' => $finalProvider,
                'model' => $finalModel,
                'context' => $context,
            ]);

            return null;
        }

        Log::info('Using operation-specific model configuration', [
            'operation' => $operation,
            'provider' => $finalProvider,
            'model' => $finalModel,
            'context' => $context,
        ]);

        return [
            'provider' => $finalProvider,
            'model' => $finalModel,
            'source' => 'operation_specific',
        ];
    }

    /**
     * Map command to operation type
     */
    protected function mapCommandToOperation(?string $command, string $operationType): string
    {
        return match ($command) {
            'enrich_fragment' => 'enrichment',
            'type_inference' => 'classification',
            'suggest_tags' => 'tagging',
            'generate_title' => 'title_generation',
            'embed_text' => 'embedding',
            default => match ($operationType) {
                'embedding' => 'embedding',
                'classification' => 'classification',
                'text' => 'enrichment', // Default text operations
                default => 'enrichment',
            }
        };
    }

    /**
     * Check if operation is enabled
     */
    protected function isOperationEnabled(string $operation): bool
    {
        return $this->operations[$operation]['enabled'] ?? true;
    }

    /**
     * Get default model for a provider
     */
    protected function getDefaultModelForProvider(string $provider): string
    {
        if ($provider === $this->defaultProvider) {
            return $this->defaultTextModel;
        }

        if ($provider === $this->fallbackProvider) {
            return $this->fallbackTextModel;
        }

        // Get first available text model for the provider
        $providerConfig = $this->providers[$provider] ?? [];
        $textModels = $providerConfig['text_models'] ?? [];

        if (empty($textModels)) {
            throw new \Exception("Provider '{$provider}' has no text models configured");
        }

        return array_key_first($textModels);
    }

    /**
     * Get default model for provider based on operation type
     */
    protected function getDefaultModelForProviderAndOperation(string $provider, string $operation): string
    {
        // For embedding operations, prioritize embedding models
        if ($operation === 'embedding') {
            return $this->getDefaultEmbeddingModelForProvider($provider);
        }

        // For text operations, use text models
        return $this->getDefaultModelForProvider($provider);
    }

    /**
     * Get default embedding model for provider
     */
    protected function getDefaultEmbeddingModelForProvider(string $provider): string
    {
        $providerConfig = $this->providers[$provider] ?? [];
        $embeddingModels = $providerConfig['embedding_models'] ?? [];

        if (empty($embeddingModels)) {
            // Fall back to text model if no embedding models configured
            return $this->getDefaultModelForProvider($provider);
        }

        return array_key_first($embeddingModels);
    }

    /**
     * Get AI parameters based on operation context
     */
    public function getAIParameters(array $context): array
    {
        $command = $context['command'] ?? null;
        $operationType = $context['operation_type'] ?? 'text';

        // Map specific commands to parameter types
        $parameterType = match ($command) {
            'type_inference' => 'classification',
            'enrich_fragment' => 'enrichment',
            'suggest_tags' => 'tagging',
            'generate_title' => 'title_generation',
            'embed_text' => 'embedding',
            default => match ($operationType) {
                'embedding' => 'embedding',
                'text' => 'enrichment', // Default text operations use enrichment params
                default => 'enrichment',
            }
        };

        // Get base parameters for the operation type
        $parameters = $this->aiParameters[$parameterType] ?? [];

        // Allow context overrides
        if (isset($context['temperature'])) {
            $parameters['temperature'] = $context['temperature'];
        }
        if (isset($context['top_p'])) {
            $parameters['top_p'] = $context['top_p'];
        }
        if (isset($context['max_tokens'])) {
            $parameters['max_tokens'] = $context['max_tokens'];
        }

        return $parameters;
    }

    /**
     * Get available AI parameter types
     */
    public function getAvailableParameterTypes(): array
    {
        return array_keys($this->aiParameters);
    }

    /**
     * Get AI parameters for a specific type
     */
    public function getParametersForType(string $type): array
    {
        return $this->aiParameters[$type] ?? [];
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
