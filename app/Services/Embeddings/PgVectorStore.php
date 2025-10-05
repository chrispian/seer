<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use App\DTOs\VectorSearchResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PgVectorStore implements EmbeddingStoreInterface
{
    public function store(int $fragmentId, string $provider, string $model, int $dimensions, array $vector, string $contentHash): void
    {
        if (!$this->isVectorSupportAvailable()) {
            throw new \RuntimeException('pgvector extension is not available');
        }

        $vec = '[' . implode(',', $vector) . ']';
        
        DB::statement('
            INSERT INTO fragment_embeddings (fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?::vector, ?, now(), now())
            ON CONFLICT (fragment_id, provider, model, content_hash)
            DO UPDATE SET dims = EXCLUDED.dims, embedding = EXCLUDED.embedding, updated_at = now()
        ', [$fragmentId, $provider, $model, $dimensions, $vec, $contentHash]);

        Log::debug('PgVectorStore: embedding stored', [
            'fragment_id' => $fragmentId,
            'provider' => $provider,
            'model' => $model,
            'dimensions' => $dimensions,
        ]);
    }

    public function exists(int $fragmentId, string $provider, string $model, string $contentHash): bool
    {
        return DB::table('fragment_embeddings')
            ->where('fragment_id', $fragmentId)
            ->where('provider', $provider)
            ->where('model', $model)
            ->where('content_hash', $contentHash)
            ->exists();
    }

    public function search(array $queryVector, string $provider, int $limit = 20, float $threshold = 0.0): array
    {
        if (!$this->isVectorSupportAvailable()) {
            return [];
        }

        $queryVec = '[' . implode(',', $queryVector) . ']';

        // Use PostgreSQL vector search with cosine similarity
        $results = DB::select('
            SELECT 
                fe.fragment_id,
                1 - (fe.embedding <=> ?::vector) as similarity,
                ts_rank(to_tsvector(f.message), plainto_tsquery(?)) as text_rank,
                (1 - (fe.embedding <=> ?::vector)) * 0.7 + ts_rank(to_tsvector(f.message), plainto_tsquery(?)) * 0.3 as combined_score,
                left(f.message, 200) as snippet
            FROM fragment_embeddings fe
            JOIN fragments f ON fe.fragment_id = f.id
            WHERE fe.provider = ?
            AND (1 - (fe.embedding <=> ?::vector)) > ?
            ORDER BY combined_score DESC
            LIMIT ?
        ', [
            $queryVec,          // similarity calculation
            '',                 // text search (empty for now)
            $queryVec,          // combined score similarity
            '',                 // combined score text search (empty for now)
            $provider,
            $queryVec,          // threshold comparison
            $threshold,
            $limit
        ]);

        return array_map(function ($row) {
            return new VectorSearchResult(
                fragmentId: $row->fragment_id,
                similarity: (float) $row->similarity,
                textRank: (float) $row->text_rank,
                combinedScore: (float) $row->combined_score,
                snippet: $row->snippet
            );
        }, $results);
    }

    public function isVectorSupportAvailable(): bool
    {
        try {
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
            return !empty($result);
        } catch (\Throwable $e) {
            Log::warning('PgVectorStore: could not check pgvector availability', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getDriverInfo(): array
    {
        return [
            'driver' => 'postgresql',
            'extension' => 'pgvector',
            'available' => $this->isVectorSupportAvailable(),
            'version' => $this->getExtensionVersion(),
        ];
    }

    protected function getExtensionVersion(): ?string
    {
        try {
            $result = DB::select("SELECT extversion FROM pg_extension WHERE extname = 'vector'");
            return $result[0]->extversion ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}