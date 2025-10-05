<?php

namespace App\Services\Search;

use App\Contracts\EmbeddingStoreInterface;
use App\Contracts\HybridSearchInterface;
use App\Database\MigrationHelpers\VectorMigrationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SQLiteHybridSearch implements HybridSearchInterface
{
    protected $embeddingStore;

    protected $hasVector;

    protected $hasFullText;

    public function __construct(?EmbeddingStoreInterface $embeddingStore, bool $hasVector = false, bool $hasFullText = false)
    {
        $this->embeddingStore = $embeddingStore;
        $this->hasVector = $hasVector && VectorMigrationHelper::hasSQLiteVectorSupport();
        $this->hasFullText = $hasFullText && VectorMigrationHelper::hasFTS5Support();
    }

    public function hybridSearch(string $query, array $options = []): array
    {
        $vectorWeight = $options['vector_weight'] ?? 0.7;
        $textWeight = $options['text_weight'] ?? 0.3;
        $limit = $options['limit'] ?? 20;

        // Fallback strategies based on available capabilities
        if ($this->hasVector && $this->hasFullText) {
            return $this->performFullHybridSearch($query, $options);
        } elseif ($this->hasFullText) {
            return $this->textSearch($query, $options);
        } elseif ($this->hasVector) {
            return $this->vectorSearch($this->embeddingStore->embed($query), $options);
        } else {
            return $this->performBasicTextSearch($query, $options);
        }
    }

    protected function performFullHybridSearch(string $query, array $options = []): array
    {
        $vectorWeight = $options['vector_weight'] ?? 0.7;
        $textWeight = $options['text_weight'] ?? 0.3;
        $limit = $options['limit'] ?? 20;

        try {
            if (! $this->embeddingStore) {
                return $this->hasFullText
                    ? $this->textSearch($query, $options)
                    : $this->performBasicTextSearch($query, $options);
            }

            // Get vector for the query
            $vector = $this->embeddingStore->embed($query);

            // Combine vector and text search results
            $vectorResults = $this->vectorSearch($vector, ['limit' => $limit * 2]);
            $textResults = $this->textSearch($query, ['limit' => $limit * 2]);

            // Merge and rerank results
            $combinedResults = [];
            $seenIds = [];

            // Add vector results
            foreach ($vectorResults as $result) {
                $combinedResults[$result['id']] = $result;
                $combinedResults[$result['id']]['vector_score'] = $result['score'];
                $combinedResults[$result['id']]['text_score'] = 0;
                $seenIds[] = $result['id'];
            }

            // Add text results and merge scores
            foreach ($textResults as $result) {
                if (isset($combinedResults[$result['id']])) {
                    $combinedResults[$result['id']]['text_score'] = $result['score'];
                } else {
                    $combinedResults[$result['id']] = $result;
                    $combinedResults[$result['id']]['vector_score'] = 0;
                    $combinedResults[$result['id']]['text_score'] = $result['score'];
                }
            }

            // Calculate hybrid scores and sort
            foreach ($combinedResults as $id => &$result) {
                $result['score'] = ($result['vector_score'] * $vectorWeight) + ($result['text_score'] * $textWeight);
                $result['type'] = 'hybrid';
            }

            usort($combinedResults, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            return array_slice($combinedResults, 0, $limit);

        } catch (\Exception $e) {
            Log::warning('SQLite hybrid search failed, falling back', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            // Fallback to text search
            return $this->hasFullText
                ? $this->textSearch($query, $options)
                : $this->performBasicTextSearch($query, $options);
        }
    }

    public function vectorSearch(array $vector, array $options = []): array
    {
        if (! $this->hasVector) {
            return [];
        }

        $limit = $options['limit'] ?? 20;

        try {
            if (! $this->embeddingStore) {
                return [];
            }

            // Use sqlite-vec for similarity search
            $vectorBlob = $this->embeddingStore->convertToBlob($vector);

            $results = DB::select('
                SELECT 
                    f.id,
                    f.title,
                    f.message,
                    f.created_at,
                    vec_distance_cosine(fe.embedding, ?) as distance
                FROM fragments f
                JOIN fragment_embeddings fe ON f.id = fe.fragment_id
                WHERE fe.embedding IS NOT NULL
                ORDER BY distance ASC
                LIMIT ?
            ', [$vectorBlob, $limit]);

            return array_map(function ($result) {
                return [
                    'id' => $result->id,
                    'title' => $result->title,
                    'content' => $result->message,
                    'created_at' => $result->created_at,
                    'score' => 1.0 - (float) $result->distance, // Convert distance to similarity
                    'type' => 'vector',
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error('SQLite vector search failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function textSearch(string $query, array $options = []): array
    {
        if (! $this->hasFullText) {
            return $this->performBasicTextSearch($query, $options);
        }

        $limit = $options['limit'] ?? 20;

        try {
            // Use FTS5 for full-text search
            $results = DB::select('
                SELECT 
                    f.id,
                    f.title,
                    f.message,
                    f.created_at,
                    rank as text_score
                FROM fragments_fts fts
                JOIN fragments f ON f.id = fts.rowid
                WHERE fragments_fts MATCH ?
                ORDER BY rank DESC
                LIMIT ?
            ', [$query, $limit]);

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
            Log::warning('SQLite FTS5 search failed, using basic search', [
                'error' => $e->getMessage(),
            ]);

            return $this->performBasicTextSearch($query, $options);
        }
    }

    protected function performBasicTextSearch(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;

        try {
            // Basic LIKE search as ultimate fallback
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
                    'type' => 'basic_text',
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error('Basic text search failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function hasVectorCapability(): bool
    {
        return $this->hasVector;
    }

    public function hasTextCapability(): bool
    {
        return $this->hasFullText || true; // Basic text search always available
    }

    public function getCapabilities(): array
    {
        return [
            'vector_search' => $this->hasVector,
            'text_search' => $this->hasFullText,
            'basic_text_search' => true,
            'hybrid_search' => $this->hasVector && $this->hasFullText,
            'full_text_ranking' => $this->hasFullText,
            'vector_similarity' => $this->hasVector,
            'database' => 'sqlite',
            'extensions' => array_filter([
                $this->hasVector ? 'sqlite-vec' : null,
                $this->hasFullText ? 'fts5' : null,
            ]),
        ];
    }
}
