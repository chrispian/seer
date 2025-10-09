<?php

namespace App\Services\AI;

use App\Models\AICredential;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Vault;
use App\Services\Telemetry\LLMTelemetry;
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
        $startTime = microtime(true);
        $selections = $this->gatherSelections($context);
        $selectionTime = microtime(true) - $startTime;

        $selectedModel = $this->applySelectionStrategy($selections);

        // Validate the selected model is available (skip for command overrides)
        $availabilityStart = microtime(true);
        $isAvailable = $selectedModel['source'] === 'command_override' ||
                      $this->isModelAvailable($selectedModel['provider'], $selectedModel['model']);
        $availabilityCheckTime = microtime(true) - $availabilityStart;

        if (! $isAvailable) {
            Log::warning('Selected model not available, falling back', [
                'selected' => $selectedModel,
                'context' => $context,
            ]);

            $selectedModel = $this->getFallbackModel($context);
        }

        $totalTime = microtime(true) - $startTime;

        // Enhanced telemetry logging
        LLMTelemetry::logModelSelection([
            'operation_type' => $context['operation_type'] ?? 'text',
            'selected_provider' => $selectedModel['provider'],
            'selected_model' => $selectedModel['model'],
            'selection_source' => $selectedModel['source'],
            'available_selections_count' => count($selections),
            'model_available' => $isAvailable,
            'used_fallback' => ! $isAvailable,
            'selection_time_ms' => round($selectionTime * 1000, 2),
            'availability_check_time_ms' => round($availabilityCheckTime * 1000, 2),
            'total_time_ms' => round($totalTime * 1000, 2),
            'context_keys' => array_keys($context),
            'selection_criteria' => $this->summarizeSelectionCriteria($selections),
        ]);

        // Legacy logging for backward compatibility
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
        $startTime = microtime(true);
        $context['operation_type'] = 'embedding';

        // Check for operation-specific configuration first
        $operationSelection = $this->selectModelForOperation($context);
        if ($operationSelection) {
            // Add AI parameters based on operation context
            $operationSelection['parameters'] = $this->getAIParameters($context);

            $totalTime = microtime(true) - $startTime;
            LLMTelemetry::logModelSelection([
                'operation_type' => 'embedding',
                'selected_provider' => $operationSelection['provider'],
                'selected_model' => $operationSelection['model'],
                'source' => $operationSelection['source'],
                'selection_method' => 'operation_specific',
                'total_time_ms' => round($totalTime * 1000, 2),
                'context_keys' => array_keys($context),
            ]);

            return $operationSelection;
        }

        // Fall back to standard model selection
        $selection = $this->selectModelByType($context, 'embedding_models');

        // Add AI parameters for embedding operations
        $selection['parameters'] = $this->getAIParameters($context);

        $totalTime = microtime(true) - $startTime;
        LLMTelemetry::logModelSelection([
            'operation_type' => 'embedding',
            'selected_provider' => $selection['provider'],
            'selected_model' => $selection['model'],
            'source' => $selection['source'],
            'selection_method' => 'type_specific',
            'total_time_ms' => round($totalTime * 1000, 2),
            'context_keys' => array_keys($context),
        ]);

        return $selection;
    }

    /**
     * Select model specifically for text generation/inference
     */
    public function selectTextModel(array $context = []): array
    {
        $startTime = microtime(true);
        $context['operation_type'] = 'text';

        // Check for operation-specific configuration first
        $operationSelection = $this->selectModelForOperation($context);
        if ($operationSelection) {
            // Add AI parameters based on operation context
            $operationSelection['parameters'] = $this->getAIParameters($context);

            $totalTime = microtime(true) - $startTime;
            LLMTelemetry::logModelSelection([
                'operation_type' => 'text',
                'selected_provider' => $operationSelection['provider'],
                'selected_model' => $operationSelection['model'],
                'source' => $operationSelection['source'],
                'selection_method' => 'operation_specific',
                'total_time_ms' => round($totalTime * 1000, 2),
                'context_keys' => array_keys($context),
            ]);

            return $operationSelection;
        }

        // Fall back to standard model selection
        $selection = $this->selectModelByType($context, 'text_models');

        // Add AI parameters based on operation context
        $selection['parameters'] = $this->getAIParameters($context);

        $totalTime = microtime(true) - $startTime;
        LLMTelemetry::logModelSelection([
            'operation_type' => 'text',
            'selected_provider' => $selection['provider'],
            'selected_model' => $selection['model'],
            'source' => $selection['source'],
            'selection_method' => 'type_specific',
            'total_time_ms' => round($totalTime * 1000, 2),
            'context_keys' => array_keys($context),
        ]);

        return $selection;
    }

    /**
     * Select model by specific type (text_models or embedding_models)
     */
    protected function selectModelByType(array $context, string $modelType): array
    {
        $startTime = microtime(true);
        $selections = $this->gatherSelections($context);

        // Filter selections to only include providers that support the model type
        $filteredSelections = [];
        $filteredOutSelections = [];
        foreach ($selections as $key => $selection) {
            if ($this->providerSupportsModelType($selection['provider'], $modelType)) {
                $filteredSelections[$key] = $selection;
            } else {
                $filteredOutSelections[$key] = $selection;
            }
        }

        if (empty($filteredSelections)) {
            // No provider supports this model type, use fallback
            $fallbackSelection = $this->getFallbackForModelType($modelType);

            $totalTime = microtime(true) - $startTime;
            LLMTelemetry::logModelSelection([
                'operation_type' => $context['operation_type'] ?? 'unknown',
                'model_type_requested' => $modelType,
                'selected_provider' => $fallbackSelection['provider'],
                'selected_model' => $fallbackSelection['model'],
                'source' => $fallbackSelection['source'],
                'selection_method' => 'type_specific_fallback',
                'total_candidates' => count($selections),
                'filtered_candidates' => 0,
                'filtered_out_count' => count($filteredOutSelections),
                'fallback_reason' => 'no_providers_support_model_type',
                'total_time_ms' => round($totalTime * 1000, 2),
                'context_keys' => array_keys($context),
            ]);

            return $fallbackSelection;
        }

        $selectedModel = $this->applySelectionStrategy($filteredSelections);

        // Ensure the selected model is of the correct type
        $modelTypeCorrected = false;
        if (! $this->isModelOfType($selectedModel['provider'], $selectedModel['model'], $modelType)) {
            $selectedModel['model'] = $this->getDefaultModelForType($selectedModel['provider'], $modelType);
            $modelTypeCorrected = true;
        }

        $totalTime = microtime(true) - $startTime;
        LLMTelemetry::logModelSelection([
            'operation_type' => $context['operation_type'] ?? 'unknown',
            'model_type_requested' => $modelType,
            'selected_provider' => $selectedModel['provider'],
            'selected_model' => $selectedModel['model'],
            'source' => $selectedModel['source'],
            'selection_method' => 'type_specific',
            'total_candidates' => count($selections),
            'filtered_candidates' => count($filteredSelections),
            'filtered_out_count' => count($filteredOutSelections),
            'model_type_corrected' => $modelTypeCorrected,
            'total_time_ms' => round($totalTime * 1000, 2),
            'context_keys' => array_keys($context),
        ]);

        return $selectedModel;
    }

    /**
     * Gather potential selections from various sources
     */
    protected function gatherSelections(array $context): array
    {
        $startTime = microtime(true);
        $selections = [];
        $gatheringStats = [
            'command_override_attempted' => false,
            'command_override_success' => false,
            'project_preference_attempted' => false,
            'project_preference_success' => false,
            'vault_preference_attempted' => false,
            'vault_preference_success' => false,
            'type_inference_boost' => false,
        ];

        // Command override (highest priority)
        if (! empty($context['command_model_override'])) {
            $gatheringStats['command_override_attempted'] = true;
            $override = $this->parseModelOverride($context['command_model_override']);
            if ($override) {
                $gatheringStats['command_override_success'] = true;
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
            $gatheringStats['project_preference_attempted'] = true;
            $projectModel = $this->getProjectModelPreference($context['project_id']);
            if ($projectModel) {
                $gatheringStats['project_preference_success'] = true;
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
            $gatheringStats['vault_preference_attempted'] = true;
            $vaultModel = $this->getVaultModelPreference($context['vault']);
            if ($vaultModel) {
                $gatheringStats['vault_preference_success'] = true;
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
            $gatheringStats['type_inference_boost'] = true;
            $selections['fallback']['priority'] = max($selections['fallback']['priority'], 110);
        }

        $gatheringTime = microtime(true) - $startTime;

        // Log selection gathering process
        LLMTelemetry::logLLMCall([
            'event_type' => 'selection_gathering',
            'operation_type' => $context['operation_type'] ?? 'unknown',
            'total_selections_gathered' => count($selections),
            'gathering_time_ms' => round($gatheringTime * 1000, 2),
            'gathering_stats' => $gatheringStats,
            'selection_sources' => array_keys($selections),
            'context_keys' => array_keys($context),
        ]);

        return $selections;
    }

    /**
     * Apply selection strategy to choose the best model
     */
    protected function applySelectionStrategy(array $selections): array
    {
        $startTime = microtime(true);

        if (empty($selections)) {
            $fallback = $this->getFallbackModel();
            $this->logSelectionStrategy('empty_selections', $selections, $fallback, $startTime);

            return $fallback;
        }

        // Sort by priority (highest first)
        uasort($selections, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        $attemptedSelections = [];
        $selectedModel = null;

        // Return the highest priority available model
        foreach ($selections as $key => $selection) {
            $attemptedSelections[] = [
                'key' => $key,
                'provider' => $selection['provider'],
                'model' => $selection['model'],
                'priority' => $selection['priority'],
                'source' => $selection['source'],
            ];

            // Command overrides bypass availability checks
            if ($selection['source'] === 'command_override' ||
                $this->isModelAvailable($selection['provider'], $selection['model'])) {
                $selectedModel = [
                    'provider' => $selection['provider'],
                    'model' => $selection['model'],
                    'source' => $selection['source'],
                ];
                break;
            }
        }

        // If no selection is available, use fallback
        if (! $selectedModel) {
            $selectedModel = $this->getFallbackModel();
            $selectedModel['source'] = 'fallback_after_failures';
        }

        $this->logSelectionStrategy('strategy_applied', $selections, $selectedModel, $startTime, $attemptedSelections);

        return $selectedModel;
    }

    /**
     * Log selection strategy execution
     */
    protected function logSelectionStrategy(string $strategy, array $selections, array $result, float $startTime, array $attemptedSelections = []): void
    {
        $executionTime = microtime(true) - $startTime;

        LLMTelemetry::logLLMCall([
            'event_type' => 'model_selection_strategy',
            'strategy_type' => $strategy,
            'total_candidates' => count($selections),
            'attempted_selections' => $attemptedSelections,
            'selected_provider' => $result['provider'],
            'selected_model' => $result['model'],
            'selection_source' => $result['source'],
            'execution_time_ms' => round($executionTime * 1000, 2),
            'strategy_success' => $result['source'] !== 'fallback_after_failures',
        ]);
    }

    /**
     * Check if a model is available (provider is configured)
     */
    protected function isModelAvailable(string $provider, string $model): bool
    {
        $startTime = microtime(true);
        $availabilityChecks = [];

        // Check if provider exists in config
        $configExists = isset($this->providers[$provider]);
        $availabilityChecks['config_exists'] = $configExists;

        if (! $configExists) {
            $this->logAvailabilityCheck($provider, $model, false, $availabilityChecks, $startTime);

            return false;
        }

        // Check if provider is enabled in database
        $providerConfig = Provider::where('provider', $provider)->first();
        $dbEnabled = $providerConfig && $providerConfig->enabled;
        $availabilityChecks['db_enabled'] = $dbEnabled;

        if (! $dbEnabled) {
            $this->logAvailabilityCheck($provider, $model, false, $availabilityChecks, $startTime);

            return false;
        }

        // Check if provider has valid credentials
        $credential = AICredential::getActiveEnabledCredential($provider);
        $hasCredentials = false;

        if ($credential) {
            $hasCredentials = true;
        } elseif ($provider === 'ollama') {
            // For Ollama, check if base URL is configured instead of credentials
            $hasCredentials = ! empty(config('services.ollama.base'));
        }

        $availabilityChecks['has_credentials'] = $hasCredentials;

        if (! $hasCredentials) {
            $this->logAvailabilityCheck($provider, $model, false, $availabilityChecks, $startTime);

            return false;
        }

        // Check if model exists in provider's model list
        $capabilities = $providerConfig->getCapabilities();
        $allModels = array_merge(
            array_keys($capabilities['text_models'] ?? []),
            array_keys($capabilities['embedding_models'] ?? [])
        );

        $modelExists = in_array($model, $allModels);
        $availabilityChecks['model_exists'] = $modelExists;

        $isAvailable = $modelExists;
        $this->logAvailabilityCheck($provider, $model, $isAvailable, $availabilityChecks, $startTime);

        return $isAvailable;
    }

    /**
     * Log model availability check results
     */
    protected function logAvailabilityCheck(string $provider, string $model, bool $isAvailable, array $checks, float $startTime): void
    {
        $checkTime = microtime(true) - $startTime;

        LLMTelemetry::logLLMCall([
            'event_type' => 'model_availability_check',
            'provider' => $provider,
            'model' => $model,
            'available' => $isAvailable,
            'check_time_ms' => round($checkTime * 1000, 2),
            'checks_performed' => $checks,
            'failure_reason' => $isAvailable ? null : $this->determineFailureReason($checks),
        ]);
    }

    /**
     * Determine the reason for availability check failure
     */
    protected function determineFailureReason(array $checks): string
    {
        if (! $checks['config_exists']) {
            return 'provider_not_configured';
        }
        if (! $checks['db_enabled']) {
            return 'provider_disabled';
        }
        if (! $checks['has_credentials']) {
            return 'no_credentials';
        }
        if (! $checks['model_exists']) {
            return 'model_not_found';
        }

        return 'unknown';
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
        $startTime = microtime(true);
        $command = $context['command'] ?? null;
        $operationType = $context['operation_type'] ?? 'text';

        // Map commands to operation types
        $operation = $this->mapCommandToOperation($command, $operationType);

        // Check if operation is enabled
        if (! $this->isOperationEnabled($operation)) {
            $totalTime = microtime(true) - $startTime;
            LLMTelemetry::logLLMCall([
                'event_type' => 'operation_selection',
                'operation' => $operation,
                'command' => $command,
                'operation_type' => $operationType,
                'operation_enabled' => false,
                'selection_result' => 'operation_disabled',
                'total_time_ms' => round($totalTime * 1000, 2),
            ]);

            throw new \Exception("AI operation '{$operation}' is disabled");
        }

        // Get operation-specific configuration
        $operationConfig = $this->operations[$operation] ?? [];

        // Check if operation has specific provider/model configured
        $provider = $operationConfig['provider'] ?? null;
        $model = $operationConfig['model'] ?? null;

        if (! $provider && ! $model) {
            $totalTime = microtime(true) - $startTime;
            LLMTelemetry::logLLMCall([
                'event_type' => 'operation_selection',
                'operation' => $operation,
                'command' => $command,
                'operation_type' => $operationType,
                'operation_enabled' => true,
                'has_operation_config' => false,
                'selection_result' => 'no_config_fallback_to_standard',
                'total_time_ms' => round($totalTime * 1000, 2),
            ]);

            return null; // No operation-specific config, use standard selection
        }

        // Use operation-specific provider, or fall back to default
        $finalProvider = $provider ?: $this->defaultProvider;

        // Use operation-specific model, or get default for the provider based on operation type
        $finalModel = $model ?: $this->getDefaultModelForProviderAndOperation($finalProvider, $operation);

        // Validate the selected model is available
        $isAvailable = $this->isModelAvailable($finalProvider, $finalModel);
        if (! $isAvailable) {
            $totalTime = microtime(true) - $startTime;
            LLMTelemetry::logLLMCall([
                'event_type' => 'operation_selection',
                'operation' => $operation,
                'command' => $command,
                'operation_type' => $operationType,
                'operation_enabled' => true,
                'has_operation_config' => true,
                'configured_provider' => $provider,
                'configured_model' => $model,
                'final_provider' => $finalProvider,
                'final_model' => $finalModel,
                'model_available' => false,
                'selection_result' => 'model_unavailable_fallback_to_standard',
                'total_time_ms' => round($totalTime * 1000, 2),
            ]);

            Log::warning('Operation-specific model not available, falling back to standard selection', [
                'operation' => $operation,
                'provider' => $finalProvider,
                'model' => $finalModel,
                'context' => $context,
            ]);

            return null;
        }

        $totalTime = microtime(true) - $startTime;
        LLMTelemetry::logLLMCall([
            'event_type' => 'operation_selection',
            'operation' => $operation,
            'command' => $command,
            'operation_type' => $operationType,
            'operation_enabled' => true,
            'has_operation_config' => true,
            'configured_provider' => $provider,
            'configured_model' => $model,
            'final_provider' => $finalProvider,
            'final_model' => $finalModel,
            'model_available' => true,
            'selection_result' => 'operation_specific_model_selected',
            'total_time_ms' => round($totalTime * 1000, 2),
        ]);

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

    /**
     * Summarize selection criteria for telemetry
     */
    protected function summarizeSelectionCriteria(array $selections): array
    {
        $summary = [];

        foreach ($selections as $key => $selection) {
            $summary[$key] = [
                'priority' => $selection['priority'],
                'source' => $selection['source'],
                'provider' => $selection['provider'],
                'model' => $selection['model'],
            ];
        }

        return $summary;
    }
}
