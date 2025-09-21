<?php

namespace App\Contracts;

interface AIProviderInterface
{
    /**
     * Get the provider name
     */
    public function getName(): string;

    /**
     * Check if this provider supports the given operation type
     */
    public function supports(string $operation): bool;

    /**
     * Authenticate with the provider (for OAuth flows)
     */
    public function authenticate(array $credentials = []): bool;

    /**
     * Check if the provider is properly configured and available
     */
    public function isAvailable(): bool;

    /**
     * Generate text completion
     */
    public function generateText(string $prompt, array $options = []): array;

    /**
     * Generate embeddings
     */
    public function generateEmbedding(string $text, array $options = []): array;

    /**
     * Health check - test connectivity and basic functionality
     */
    public function healthCheck(): array;

    /**
     * Get provider-specific configuration requirements
     */
    public function getConfigRequirements(): array;

    /**
     * Get available models for this provider
     */
    public function getAvailableModels(): array;
}
