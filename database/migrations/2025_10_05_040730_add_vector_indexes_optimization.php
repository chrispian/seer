<?php

use App\Database\MigrationHelpers\VectorMigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $this->addVectorIndexes();
        $this->addPerformanceIndexes();
    }

    protected function addVectorIndexes(): void
    {
        try {
            if (VectorMigrationHelper::isPostgreSQL() && VectorMigrationHelper::hasPgVectorExtension()) {
                $this->addPostgreSQLVectorIndexes();
            } elseif (VectorMigrationHelper::isSQLite()) {
                $this->addSQLiteVectorIndexes();
            }
        } catch (\Exception $e) {
            Log::warning('Vector index creation failed', [
                'driver' => VectorMigrationHelper::getDriver(),
                'error' => $e->getMessage(),
            ]);
            // Don't fail migration if index creation fails
        }
    }

    protected function addPostgreSQLVectorIndexes(): void
    {
        // Create HNSW index for better performance on PostgreSQL
        // HNSW is generally better than IVFFlat for most use cases
        DB::statement('
            CREATE INDEX IF NOT EXISTS fragment_embeddings_hnsw_idx 
            ON fragment_embeddings 
            USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        ');

        Log::info('PostgreSQL HNSW vector index created');
    }

    protected function addSQLiteVectorIndexes(): void
    {
        if (! VectorMigrationHelper::hasSQLiteVectorSupport()) {
            Log::info('SQLite vector extension not available, skipping vector indexes');

            return;
        }

        try {
            $dimensions = config('fragments.embeddings.dimensions', 1536);

            // Create virtual table for sqlite-vec indexing
            DB::statement("
                CREATE VIRTUAL TABLE IF NOT EXISTS fragment_embeddings_vec_idx 
                USING vec0(
                    fragment_id INTEGER PRIMARY KEY,
                    embedding FLOAT[{$dimensions}]
                )
            ");

            // Populate the virtual table with existing embeddings
            $existingCount = DB::select('SELECT COUNT(*) as count FROM fragment_embeddings')[0]->count ?? 0;

            if ($existingCount > 0) {
                // Convert PostgreSQL vector format to SQLite-vec format if needed
                DB::statement('
                    INSERT OR IGNORE INTO fragment_embeddings_vec_idx(fragment_id, embedding)
                    SELECT id, embedding 
                    FROM fragment_embeddings
                    WHERE embedding IS NOT NULL
                ');
            }

            Log::info('SQLite vector index created', ['indexed_embeddings' => $existingCount]);
        } catch (\Exception $e) {
            Log::warning('SQLite vector index creation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function addPerformanceIndexes(): void
    {
        try {
            // Add general performance indexes regardless of database
            $indexes = [
                'fragment_embeddings_provider_idx' => 'CREATE INDEX IF NOT EXISTS fragment_embeddings_provider_idx ON fragment_embeddings(provider)',
                'fragment_embeddings_model_idx' => 'CREATE INDEX IF NOT EXISTS fragment_embeddings_model_idx ON fragment_embeddings(model)',
                'fragment_embeddings_content_hash_idx' => 'CREATE INDEX IF NOT EXISTS fragment_embeddings_content_hash_idx ON fragment_embeddings(content_hash)',
                'fragment_embeddings_fragment_provider_idx' => 'CREATE INDEX IF NOT EXISTS fragment_embeddings_fragment_provider_idx ON fragment_embeddings(fragment_id, provider)',
            ];

            foreach ($indexes as $name => $sql) {
                DB::statement($sql);
            }

            Log::info('Performance indexes created', ['count' => count($indexes)]);
        } catch (\Exception $e) {
            Log::warning('Performance index creation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        try {
            // Drop vector indexes
            VectorMigrationHelper::dropVectorIndex('fragment_embeddings');

            if (VectorMigrationHelper::isPostgreSQL()) {
                DB::statement('DROP INDEX IF EXISTS fragment_embeddings_hnsw_idx');
            } elseif (VectorMigrationHelper::isSQLite()) {
                DB::statement('DROP TABLE IF EXISTS fragment_embeddings_vec_idx');
            }

            // Drop performance indexes
            $indexes = [
                'fragment_embeddings_provider_idx',
                'fragment_embeddings_model_idx',
                'fragment_embeddings_content_hash_idx',
                'fragment_embeddings_fragment_provider_idx',
            ];

            foreach ($indexes as $index) {
                DB::statement("DROP INDEX IF EXISTS {$index}");
            }

            Log::info('Vector and performance indexes removed');
        } catch (\Exception $e) {
            Log::warning('Index cleanup failed during rollback', [
                'error' => $e->getMessage(),
            ]);
        }
    }
};
