<?php

namespace App\Database\MigrationHelpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VectorMigrationHelper
{
    public static function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function isPostgreSQL(): bool
    {
        return self::getDriver() === 'pgsql';
    }

    public static function isSQLite(): bool
    {
        return self::getDriver() === 'sqlite';
    }

    public static function hasPostgreSQLVectorSupport(): bool
    {
        if (! self::isPostgreSQL()) {
            return false;
        }

        try {
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector'");

            return ! empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    // Alias for backwards compatibility
    public static function hasPgVectorExtension(): bool
    {
        return self::hasPostgreSQLVectorSupport();
    }

    public static function hasSQLiteVectorSupport(): bool
    {
        if (! self::isSQLite()) {
            return false;
        }

        try {
            DB::select('SELECT vec_version()');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function hasFTS5Support(): bool
    {
        if (! self::isSQLite()) {
            return false;
        }

        try {
            DB::statement('CREATE VIRTUAL TABLE IF NOT EXISTS _fts_test USING fts5(content)');
            DB::statement('DROP TABLE _fts_test');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create vector column with appropriate type for the database driver
     */
    public static function addVectorColumn(string $table, string $column, int $dimensions = 1536): void
    {
        if (self::isPostgreSQL()) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} vector({$dimensions})");
        } elseif (self::isSQLite()) {
            Schema::table($table, function ($table) use ($column) {
                $table->binary($column)->nullable();
            });
        } else {
            throw new \InvalidArgumentException('Unsupported database driver: '.self::getDriver());
        }
    }

    /**
     * Create appropriate indexes for vector columns
     */
    public static function createVectorIndex(string $table, string $column, ?string $indexName = null): void
    {
        $indexName = $indexName ?? "{$table}_{$column}_idx";

        if (self::isPostgreSQL()) {
            // Create HNSW index for PostgreSQL (more efficient than IVFFlat for most cases)
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} USING hnsw ({$column} vector_cosine_ops)");
        } elseif (self::isSQLite() && self::hasSQLiteVectorSupport()) {
            // Create virtual table for sqlite-vec
            try {
                $dimensions = config('fragments.embeddings.dimensions', 1536);
                DB::statement("
                    CREATE VIRTUAL TABLE IF NOT EXISTS {$table}_vec_idx 
                    USING vec0(embedding FLOAT[{$dimensions}])
                ");
            } catch (\Exception $e) {
                // Index creation is optional
                \Illuminate\Support\Facades\Log::warning('SQLite vector index creation failed', [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Drop vector indexes
     */
    public static function dropVectorIndex(string $table, ?string $indexName = null): void
    {
        $indexName = $indexName ?? "{$table}_embedding_idx";

        try {
            if (self::isPostgreSQL()) {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
            } elseif (self::isSQLite()) {
                DB::statement("DROP TABLE IF EXISTS {$table}_vec_idx");
            }
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }
    }
}
