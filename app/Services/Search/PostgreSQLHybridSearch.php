<?php

namespace App\Services\Search;

use App\Contracts\EmbeddingStoreInterface;
use App\Contracts\HybridSearchInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostgreSQLHybridSearch implements HybridSearchInterface
{
    protected $embeddingStore;

    public function __construct(?EmbeddingStoreInterface $embeddingStore)
    {
        $this->embeddingStore = $embeddingStore;
    }

    public function hybridSearch(string $query, array $options = []): array
    {
        $vectorWeight = $options['vector_weight'] ?? 0.7;
        $textWeight = $options['text_weight'] ?? 0.3;
        $limit = $options['limit'] ?? 20;
        $threshold = $options['threshold'] ?? 0.5;

        try {
            if (! $this->embeddingStore) {
                return $this->textSearch($query, $options);
            }

            // Get vector for the query
            $vector = $this->embeddingStore->embed($query);

            // Perform hybrid search using PostgreSQL's advanced features
            $results = DB::select("
                WITH vector_results AS (
                    SELECT 
                        f.id,
                        f.title,
                        f.message,
                        f.created_at,
                        (1 - (fe.embedding <=> ?::vector)) as vector_score
                    FROM fragments f
                    JOIN fragment_embeddings fe ON f.id = fe.fragment_id
                    WHERE fe.embedding IS NOT NULL
                    ORDER BY fe.embedding <=> ?::vector
                    LIMIT ?
                ),
                text_results AS (
                    SELECT 
                        f.id,
                        f.title,
                        f.message,
                        f.created_at,
                        ts_rank_cd(
                            to_tsvector('english', COALESCE(f.title, '') || ' ' || COALESCE(f.message, '')),
                            plainto_tsquery('english', ?)
                        ) as text_score
                    FROM fragments f
                    WHERE to_tsvector('english', COALESCE(f.title, '') || ' ' || COALESCE(f.message, ''))
                          @@ plainto_tsquery('english', ?)
                    ORDER BY text_score DESC
                    LIMIT ?
                )
                SELECT DISTINCT
                    COALESCE(vr.id, tr.id) as id,
                    COALESCE(vr.title, tr.title) as title,
                    COALESCE(vr.message, tr.message) as message,
                    COALESCE(vr.created_at, tr.created_at) as created_at,
                    COALESCE(vr.vector_score, 0) * ? + COALESCE(tr.text_score, 0) * ? as hybrid_score
                FROM vector_results vr
                FULL OUTER JOIN text_results tr ON vr.id = tr.id
                WHERE COALESCE(vr.vector_score, 0) * ? + COALESCE(tr.text_score, 0) * ? >= ?
                ORDER BY hybrid_score DESC
                LIMIT ?
            ", [
                $vector, $vector, $limit * 2,
                $query, $query, $limit * 2,
                $vectorWeight, $textWeight,
                $vectorWeight, $textWeight, $threshold,
                $limit,
            ]);

            return array_map(function ($result) {
                return [
                    'id' => $result->id,
                    'title' => $result->title,
                    'content' => $result->message,
                    'created_at' => $result->created_at,
                    'score' => (float) $result->hybrid_score,
                    'type' => 'hybrid',
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::warning('PostgreSQL hybrid search failed, falling back to text search', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return $this->textSearch($query, $options);
        }
    }

    public function vectorSearch(array $vector, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;
        $threshold = $options['threshold'] ?? 0.5;

        try {
            $results = DB::select('
                SELECT 
                    f.id,
                    f.title,
                    f.message,
                    f.created_at,
                    (1 - (fe.embedding <=> ?::vector)) as similarity_score
                FROM fragments f
                JOIN fragment_embeddings fe ON f.id = fe.fragment_id
                WHERE fe.embedding IS NOT NULL
                  AND (1 - (fe.embedding <=> ?::vector)) >= ?
                ORDER BY fe.embedding <=> ?::vector
                LIMIT ?
            ', [$vector, $vector, $threshold, $vector, $limit]);

            return array_map(function ($result) {
                return [
                    'id' => $result->id,
                    'title' => $result->title,
                    'content' => $result->message,
                    'created_at' => $result->created_at,
                    'score' => (float) $result->similarity_score,
                    'type' => 'vector',
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error('PostgreSQL vector search failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function textSearch(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;

        try {
            $results = DB::select("
                SELECT 
                    f.id,
                    f.title,
                    f.message,
                    f.created_at,
                    ts_rank_cd(
                        to_tsvector('english', COALESCE(f.title, '') || ' ' || COALESCE(f.message, '')),
                        plainto_tsquery('english', ?)
                    ) as text_score
                FROM fragments f
                WHERE to_tsvector('english', COALESCE(f.title, '') || ' ' || COALESCE(f.message, ''))
                      @@ plainto_tsquery('english', ?)
                ORDER BY text_score DESC
                LIMIT ?
            ", [$query, $query, $limit]);

            return array_map(function ($result) {
                return [
                    'id' => $result->id,
                    'title' => $result->title,
                    'content' => $result->message,
                    'created_at' => $result->created_at,
                    'score' => (float) $result->text_score,
                    'type' => 'text',
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error('PostgreSQL text search failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function hasVectorCapability(): bool
    {
        return true;
    }

    public function hasTextCapability(): bool
    {
        return true;
    }

    public function getCapabilities(): array
    {
        return [
            'vector_search' => true,
            'text_search' => true,
            'hybrid_search' => true,
            'full_text_ranking' => true,
            'vector_similarity' => true,
            'database' => 'postgresql',
            'extensions' => ['pgvector', 'tsvector'],
        ];
    }
}
