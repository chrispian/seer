<?php

namespace App\Services\AI;

use App\Contracts\AIProviderInterface;
use App\Services\AI\Providers\AnthropicProvider;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Providers\OpenAIProvider;
use App\Services\AI\Providers\OpenRouterProvider;
use App\Services\AI\Providers\PrismProviderAdapter;
use App\Services\Telemetry\CorrelationContext;
use Illuminate\Support\Facades\Log;

class AIProviderManager
{
    protected array $providers = [];

    protected array $config;

    protected ModelSelectionService $modelSelection;

    public function __construct(ModelSelectionService $modelSelection)
    {
        $this->modelSelection = $modelSelection;
        $this->config = config('fragments.models.providers', []);
        $this->initializeProviders();
    }

    /**
     * Initialize all configured providers
     */
    protected function initializeProviders(): void
    {
        foreach ($this->config as $name => $config) {
            try {
                $provider = $this->createProvider($name, $config);
                if ($provider) {
                    $this->providers[$name] = $provider;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to initialize AI provider: {$name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Create a provider instance based on name and config
     */
    protected function createProvider(string $name, array $config): ?AIProviderInterface
    {
        // Check if we should use Prism for this provider
        $usePrism = config('fragments.models.use_prism', false);
        
        if ($usePrism) {
            // Use Prism adapter for all supported providers
            // Prism v0.86+ supports: openai, anthropic, ollama, openrouter, mistral, groq, xai, gemini, deepseek, elevenlabs, voyageai
            $prismSupportedProviders = ['openai', 'anthropic', 'ollama', 'openrouter'];
            if (in_array($name, $prismSupportedProviders)) {
                return new PrismProviderAdapter($name, $config);
            }
        }
        
        // Use custom provider implementations (fallback when Prism disabled)
        return match ($name) {
            'openai' => new OpenAIProvider($config),
            'anthropic' => new AnthropicProvider($config),
            'ollama' => new OllamaProvider($config),
            'openrouter' => new OpenRouterProvider($config),
            default => null,
        };
    }

    /**
     * Get provider by name
     */
    public function getProvider(string $name): ?AIProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Get all available providers
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get providers that support a specific operation
     */
    public function getProvidersForOperation(string $operation): array
    {
        return array_filter($this->providers, function (AIProviderInterface $provider) use ($operation) {
            return $provider->supports($operation) && $provider->isAvailable();
        });
    }

    /**
     * Generate text using the best available provider
     */
    public function generateText(string $prompt, array $context = [], array $options = []): array
    {
        $selectedModel = $this->modelSelection->selectTextModel($context);
        $provider = $this->getProvider($selectedModel['provider']);

        if (! $provider || ! $provider->supports('text')) {
            throw new \RuntimeException('No suitable text generation provider available');
        }

        // Merge model selection with AI parameters
        $options = array_merge($selectedModel['parameters'] ?? [], $options);
        $options['model'] = $selectedModel['model'];

        // Add telemetry context for enhanced logging
        $telemetryContext = $this->buildTelemetryContext($context, 'text_generation');
        $options['_telemetry_context'] = $telemetryContext;

        Log::info('AI text generation request', [
            'provider' => $selectedModel['provider'],
            'model' => $selectedModel['model'],
            'parameters' => $selectedModel['parameters'] ?? [],
            'context' => $context,
        ]);

        return $provider->generateText($prompt, $options);
    }

    /**
     * Generate embeddings using the best available provider
     */
    public function generateEmbedding(string $text, array $context = [], array $options = []): array
    {
        $selectedModel = $this->modelSelection->selectEmbeddingModel($context);
        $provider = $this->getProvider($selectedModel['provider']);

        if (! $provider || ! $provider->supports('embedding')) {
            throw new \RuntimeException('No suitable embedding provider available');
        }

        // Merge model selection with AI parameters
        $options = array_merge($selectedModel['parameters'] ?? [], $options);
        $options['model'] = $selectedModel['model'];

        // Add telemetry context for enhanced logging
        $telemetryContext = $this->buildTelemetryContext($context, 'embedding_generation');
        $options['_telemetry_context'] = $telemetryContext;

        Log::info('AI embedding generation request', [
            'provider' => $selectedModel['provider'],
            'model' => $selectedModel['model'],
            'parameters' => $selectedModel['parameters'] ?? [],
            'context' => $context,
        ]);

        return $provider->generateEmbedding($text, $options);
    }

    /**
     * Run health checks on all providers
     */
    public function healthCheckAll(): array
    {
        $results = [];

        foreach ($this->providers as $name => $provider) {
            try {
                $results[$name] = $provider->healthCheck();
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'failed',
                    'provider' => $name,
                    'error' => $e->getMessage(),
                    'response_time_ms' => 0,
                ];
            }
        }

        return $results;
    }

    /**
     * Run health check on a specific provider
     */
    public function healthCheck(string $providerName): array
    {
        $provider = $this->getProvider($providerName);

        if (! $provider) {
            return [
                'status' => 'failed',
                'provider' => $providerName,
                'error' => 'Provider not found',
                'response_time_ms' => 0,
            ];
        }

        return $provider->healthCheck();
    }

    /**
     * Get provider availability status
     */
    public function getProviderStatus(): array
    {
        $status = [];

        foreach ($this->providers as $name => $provider) {
            $status[$name] = [
                'available' => $provider->isAvailable(),
                'supports_text' => $provider->supports('text'),
                'supports_embedding' => $provider->supports('embedding'),
                'config_requirements' => $provider->getConfigRequirements(),
            ];
        }

        return $status;
    }

    /**
     * Authenticate a provider (mainly for OAuth flows)
     */
    public function authenticateProvider(string $providerName, array $credentials = []): bool
    {
        $provider = $this->getProvider($providerName);

        if (! $provider) {
            return false;
        }

        return $provider->authenticate($credentials);
    }

    /**
     * Build telemetry context for LLM operations
     */
    protected function buildTelemetryContext(array $context, string $operationType): array
    {
        return [
            'correlation_id' => CorrelationContext::get(),
            'user_id' => auth()->id(),
            'session_id' => $context['session_id'] ?? null,
            'request_type' => $operationType,
            'start_time' => microtime(true),
            'queue_wait_ms' => 0, // Will be set by caller if applicable
            'retry_count' => 0,   // Will be set by caller if applicable
        ];
    }
}
