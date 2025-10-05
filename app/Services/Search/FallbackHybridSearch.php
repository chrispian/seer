<?php

namespace App\Services\Search;

use App\Contracts\EmbeddingStoreInterface;
use App\Contracts\HybridSearchInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FallbackHybridSearch implements HybridSearchInterface
{
    protected $embeddingStore;

    public function __construct(?EmbeddingStoreInterface $embeddingStore)
    {
        $this->embeddingStore = $embeddingStore;
    }

    public function hybridSearch(string $query, array $options = []): array
    {
        // Fallback to basic text search
        return $this->textSearch($query, $options);
    }

    public function vectorSearch(array $vector, array $options = []): array
    {
        Log::info('Vector search not available, returning empty results');

        return [];
    }

    public function textSearch(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;

        try {
            // Most basic search possible - LIKE queries
            $likeQuery = '%'.str_replace(' ', '%', $query).'%';

            $results = DB::select('
                SELECT 
                    id,
                    title,
                    message,
                    created_at,
                    CASE 
                        WHEN LOWER(title) LIKE LOWER(?) THEN 1.0
                        WHEN LOWER(message) LIKE LOWER(?) THEN 0.8
                        ELSE 0.6
                    END as text_score
                FROM fragments
                WHERE LOWER(title) LIKE LOWER(?) 
                   OR LOWER(message) LIKE LOWER(?)
                ORDER BY text_score DESC, created_at DESC
                LIMIT ?
            ', [$likeQuery, $likeQuery, $likeQuery, $likeQuery, $limit]);

            return array_map(function ($result) {
                return [
                    'id' => $result->id,
                    'title' => $result->title,
                    'content' => $result->message,
                    'created_at' => $result->created_at,
                    'score' => (float) $result->text_score,
                    'type' => 'fallback_text',
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error('Fallback text search failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function hasVectorCapability(): bool
    {
        return false;
    }

    public function hasTextCapability(): bool
    {
        return true;
    }

    public function getCapabilities(): array
    {
        return [
            'vector_search' => false,
            'text_search' => false,
            'basic_text_search' => true,
            'hybrid_search' => false,
            'full_text_ranking' => false,
            'vector_similarity' => false,
            'database' => 'fallback',
            'extensions' => [],
        ];
    }
}
