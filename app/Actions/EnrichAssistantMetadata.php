<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\Vault;

class EnrichAssistantMetadata
{
    public function handle(array $payload, \Closure $next): mixed
    {
        $fragment = $payload['fragment'];
        $data = $payload['data'];
        $jsonMetadata = $payload['json_metadata'] ?? ['found' => false];

        // Get vault and project context
        $defaultVault = Vault::getDefault();
        $defaultProject = Project::getDefaultForVault($defaultVault->id);

        // Calculate cost based on provider and token usage
        $costUsd = null;
        if ($data['token_usage']['input'] && $data['token_usage']['output']) {
            $costUsd = match ($data['provider']) {
                'ollama' => 0.00, // Ollama is free
                'openai' => $this->calculateOpenAICost($data['token_usage'], $data['model']),
                'anthropic' => $this->calculateAnthropicCost($data['token_usage'], $data['model']),
                'openrouter' => $this->calculateOpenRouterCost($data['token_usage'], $data['model']),
                default => null,
            };
        }

        // Build comprehensive metadata
        $metadata = array_merge($fragment->metadata ?? [], [
            'turn' => 'response',
            'conversation_id' => $data['conversation_id'],
            'session_id' => $data['session_id'],
            'provider' => $data['provider'],
            'model' => $data['model'],
            'router' => $data['provider'], // For now, router = provider
            'latency_ms' => $data['latency_ms'],
            'token_usage' => $data['token_usage'],
            'cost_usd' => $costUsd,
            'vault' => $defaultVault->name ?? 'default',
            'project_id' => $defaultProject->id ?? null,
        ]);

        // Merge JSON metadata if found
        if ($jsonMetadata['found'] && $jsonMetadata['metadata']) {
            $metadata = array_merge($metadata, $jsonMetadata['metadata']);
        }

        // Update fragment with enriched metadata
        $fragment->update([
            'metadata' => $metadata,
            'tags' => array_merge(
                $fragment->tags ?? [],
                $jsonMetadata['tags'] ?? []
            ),
        ]);

        // Store links for potential future processing
        if ($jsonMetadata['found'] && ! empty($jsonMetadata['links'])) {
            // TODO: Implement fragment link creation for JSON metadata links
            // This would require additional model relationships
            $payload['json_links'] = $jsonMetadata['links'];
        }

        return $next($payload['fragment']);
    }

    /**
     * Calculate OpenAI API costs based on token usage and model
     * Updated rates as of December 2024
     */
    private function calculateOpenAICost(array $tokenUsage, string $model): float
    {
        // Current OpenAI pricing per 1K tokens (USD)
        $rates = [
            'gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
            'gpt-4' => ['input' => 0.03, 'output' => 0.06],
            'gpt-3.5-turbo' => ['input' => 0.0015, 'output' => 0.002],
        ];

        $rate = $rates[$model] ?? $rates['gpt-4o-mini']; // Default to most common model

        return (($tokenUsage['input'] ?? 0) * $rate['input'] / 1000) +
               (($tokenUsage['output'] ?? 0) * $rate['output'] / 1000);
    }

    /**
     * Calculate Anthropic API costs based on token usage and model
     * Updated rates as of December 2024
     */
    private function calculateAnthropicCost(array $tokenUsage, string $model): float
    {
        // Current Anthropic pricing per 1K tokens (USD)
        $rates = [
            'claude-3-5-sonnet-latest' => ['input' => 0.003, 'output' => 0.015],
            'claude-3-5-haiku-latest' => ['input' => 0.0008, 'output' => 0.004],
            'claude-3-opus-latest' => ['input' => 0.015, 'output' => 0.075],
            // Legacy model names for compatibility
            'claude-3-opus' => ['input' => 0.015, 'output' => 0.075],
            'claude-3-sonnet' => ['input' => 0.003, 'output' => 0.015],
            'claude-3-haiku' => ['input' => 0.00025, 'output' => 0.00125],
        ];

        $rate = $rates[$model] ?? $rates['claude-3-5-sonnet-latest']; // Default to most common model

        return (($tokenUsage['input'] ?? 0) * $rate['input'] / 1000) +
               (($tokenUsage['output'] ?? 0) * $rate['output'] / 1000);
    }

    /**
     * Calculate OpenRouter API costs based on token usage and model
     * OpenRouter uses dynamic pricing, so these are approximate rates
     */
    private function calculateOpenRouterCost(array $tokenUsage, string $model): float
    {
        // OpenRouter pricing varies by model - these are approximate rates per 1K tokens (USD)
        $rates = [
            'anthropic/claude-3.5-sonnet' => ['input' => 0.003, 'output' => 0.015],
            'openai/gpt-4o' => ['input' => 0.0025, 'output' => 0.01],
            'meta-llama/llama-3.1-70b-instruct' => ['input' => 0.0004, 'output' => 0.0004],
        ];

        // Default to a reasonable rate if model not found
        $rate = $rates[$model] ?? ['input' => 0.001, 'output' => 0.002];

        return (($tokenUsage['input'] ?? 0) * $rate['input'] / 1000) +
               (($tokenUsage['output'] ?? 0) * $rate['output'] / 1000);
    }
}
