<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use RuntimeException;

class SqliteVectorStore implements EmbeddingStoreInterface
{
    public function store(int $fragmentId, string $provider, string $model, int $dimensions, array $vector, string $contentHash): void
    {
        throw new RuntimeException('SQLite vector store implementation pending - VECTOR-002');
    }

    public function exists(int $fragmentId, string $provider, string $model, string $contentHash): bool
    {
        throw new RuntimeException('SQLite vector store implementation pending - VECTOR-002');
    }

    public function search(array $queryVector, string $provider, int $limit = 20, float $threshold = 0.0): array
    {
        throw new RuntimeException('SQLite vector store implementation pending - VECTOR-002');
    }

    public function isVectorSupportAvailable(): bool
    {
        return false; // Will be implemented in VECTOR-002
    }

    public function getDriverInfo(): array
    {
        return [
            'driver' => 'sqlite',
            'extension' => 'sqlite-vec',
            'available' => $this->isVectorSupportAvailable(),
        ];
    }
}