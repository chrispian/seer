<?php

namespace App\Contracts;

interface EmbeddingStoreInterface
{
    /**
     * Store or update an embedding for a fragment
     */
    public function store(
        int $fragmentId,
        string $provider,
        string $model,
        int $dimensions,
        array $vector,
        string $contentHash
    ): void;

    /**
     * Check if embedding exists and is current
     */
    public function exists(
        int $fragmentId,
        string $provider,
        string $model,
        string $contentHash
    ): bool;

    /**
     * Search for similar embeddings
     */
    public function search(
        array $queryVector,
        string $provider,
        int $limit = 20,
        float $threshold = 0.0
    ): array;

    /**
     * Check if vector operations are available
     */
    public function isVectorSupportAvailable(): bool;

    /**
     * Get driver-specific information
     */
    public function getDriverInfo(): array;
}