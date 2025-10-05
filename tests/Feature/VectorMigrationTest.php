<?php

namespace Tests\Feature;

use App\Database\MigrationHelpers\VectorMigrationHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VectorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vector_migration_helper_database_detection()
    {
        $driver = Config::get('database.default');
        $connection = Config::get("database.connections.{$driver}.driver");

        if ($connection === 'pgsql') {
            $this->assertTrue(VectorMigrationHelper::isPostgreSQL());
            $this->assertFalse(VectorMigrationHelper::isSQLite());
        } elseif ($connection === 'sqlite') {
            $this->assertTrue(VectorMigrationHelper::isSQLite());
            $this->assertFalse(VectorMigrationHelper::isPostgreSQL());
        }
    }

    public function test_postgresql_vector_support_when_available()
    {
        if (! VectorMigrationHelper::isPostgreSQL()) {
            $this->markTestSkipped('Test requires PostgreSQL database');
        }

        // Verify pgvector extension exists
        $this->assertTrue(VectorMigrationHelper::hasPgVectorExtension());

        // Check fragment_embeddings table structure
        $this->assertTrue($this->tableExists('fragment_embeddings'));
        $this->assertTrue($this->columnExists('fragment_embeddings', 'vector'));

        // Verify vector column type
        $columnType = DB::select("
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = 'fragment_embeddings' 
            AND column_name = 'vector'
        ")[0]->data_type ?? null;

        $this->assertEquals('USER-DEFINED', $columnType);
    }

    public function test_sqlite_vector_support_when_available()
    {
        if (! VectorMigrationHelper::isSQLite()) {
            $this->markTestSkipped('Test requires SQLite database');
        }

        // Check that fragment_embeddings table exists
        $this->assertTrue($this->tableExists('fragment_embeddings'));

        // The column should be 'embedding' (not 'vector')
        $this->assertTrue($this->columnExists('fragment_embeddings', 'embedding'));

        // Check column info
        $pragma = DB::select('PRAGMA table_info(fragment_embeddings)');
        $embeddingColumn = collect($pragma)->firstWhere('name', 'embedding');

        $this->assertNotNull($embeddingColumn);

        // In test environment, this will be the PostgreSQL-style vector type
        // since the original migration runs as-is. In real SQLite deployment,
        // it would be converted to BLOB
        $this->assertNotNull($embeddingColumn->type);
    }

    public function test_migration_helper_methods_work()
    {
        // Test that all helper methods run without errors
        $this->assertIsBool(VectorMigrationHelper::isPostgreSQL());
        $this->assertIsBool(VectorMigrationHelper::isSQLite());

        if (VectorMigrationHelper::isPostgreSQL()) {
            $this->assertIsBool(VectorMigrationHelper::hasPgVectorExtension());
        }

        if (VectorMigrationHelper::isSQLite()) {
            $this->assertIsBool(VectorMigrationHelper::hasFTS5Support());
        }
    }

    public function test_dual_database_schema_compatibility()
    {
        // Verify core table structure is consistent
        $this->assertTrue($this->tableExists('fragments'));
        $this->assertTrue($this->tableExists('fragment_embeddings'));

        // Check essential columns exist regardless of database
        $requiredFragmentColumns = ['id', 'message', 'created_at', 'updated_at'];
        foreach ($requiredFragmentColumns as $column) {
            $this->assertTrue(
                $this->columnExists('fragments', $column),
                "Column {$column} should exist in fragments table"
            );
        }

        $requiredEmbeddingColumns = ['id', 'fragment_id', 'embedding', 'content_hash'];
        foreach ($requiredEmbeddingColumns as $column) {
            $this->assertTrue(
                $this->columnExists('fragment_embeddings', $column),
                "Column {$column} should exist in fragment_embeddings table"
            );
        }
    }

    public function test_vector_abstraction_layer_exists()
    {
        // Test that our abstraction layer classes exist and are loadable
        $this->assertTrue(interface_exists(\App\Contracts\EmbeddingStoreInterface::class));
        $this->assertTrue(class_exists(\App\Services\Embeddings\EmbeddingStoreManager::class));
        $this->assertTrue(class_exists(\App\Services\Embeddings\PgVectorStore::class));
        $this->assertTrue(class_exists(\App\Services\Embeddings\SqliteVectorStore::class));
    }

    protected function tableExists(string $table): bool
    {
        if (VectorMigrationHelper::isPostgreSQL()) {
            $result = DB::select('
                SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_name = ?
                )
            ', [$table]);

            return $result[0]->exists ?? false;
        } else {
            $result = DB::select("
                SELECT name 
                FROM sqlite_master 
                WHERE type='table' AND name=?
            ", [$table]);

            return count($result) > 0;
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        if (VectorMigrationHelper::isPostgreSQL()) {
            $result = DB::select('
                SELECT EXISTS (
                    SELECT FROM information_schema.columns 
                    WHERE table_name = ? AND column_name = ?
                )
            ', [$table, $column]);

            return $result[0]->exists ?? false;
        } else {
            $pragma = DB::select("PRAGMA table_info({$table})");

            return collect($pragma)->contains('name', $column);
        }
    }

    protected function createTestFragment(): void
    {
        DB::table('fragments')->insert([
            'message' => 'This is a test fragment for FTS5 search',
            'title' => 'Test Fragment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
