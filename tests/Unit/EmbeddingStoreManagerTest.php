<?php

namespace Tests\Unit;

use App\Contracts\EmbeddingStoreInterface;
use App\Services\Embeddings\EmbeddingStoreManager;
use App\Services\Embeddings\PgVectorStore;
use App\Services\Embeddings\SqliteVectorStore;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmbeddingStoreManagerTest extends TestCase
{
    public function test_resolves_postgresql_driver_for_pgsql_connection()
    {
        // Mock database driver detection
        DB::shouldReceive('connection->getDriverName')->andReturn('pgsql');

        $manager = new EmbeddingStoreManager;
        $driver = $manager->driver();

        $this->assertInstanceOf(PgVectorStore::class, $driver);
        $this->assertInstanceOf(EmbeddingStoreInterface::class, $driver);
    }

    public function test_resolves_sqlite_driver_for_sqlite_connection()
    {
        // Mock database driver detection and PDO connection
        $mockStatement = \Mockery::mock(\PDOStatement::class);
        $mockStatement->shouldReceive('fetchColumn')->andThrow(new \PDOException('Extension not available'));

        $mockPdo = \Mockery::mock(\PDO::class);
        $mockPdo->shouldReceive('query')->with('SELECT vec_version()')->andReturn($mockStatement);

        $mockConnection = \Mockery::mock();
        $mockConnection->shouldReceive('getDriverName')->andReturn('sqlite');
        $mockConnection->shouldReceive('getPdo')->andReturn($mockPdo);

        DB::shouldReceive('connection')->andReturn($mockConnection);

        $manager = new EmbeddingStoreManager;
        $driver = $manager->driver();

        $this->assertInstanceOf(SqliteVectorStore::class, $driver);
        $this->assertInstanceOf(EmbeddingStoreInterface::class, $driver);
    }

    public function test_respects_manual_driver_configuration()
    {
        config(['fragments.embeddings.driver' => 'postgresql']);

        $manager = new EmbeddingStoreManager;
        $driver = $manager->driver();

        $this->assertInstanceOf(PgVectorStore::class, $driver);
    }

    public function test_driver_implements_contract()
    {
        $manager = app(EmbeddingStoreManager::class);
        $driver = $manager->driver();

        $this->assertInstanceOf(EmbeddingStoreInterface::class, $driver);
    }

    public function test_returns_supported_drivers()
    {
        $manager = new EmbeddingStoreManager;
        $supported = $manager->getSupportedDrivers();

        $this->assertEquals(['sqlite', 'postgresql'], $supported);
    }

    public function test_throws_exception_for_unsupported_database()
    {
        DB::shouldReceive('connection->getDriverName')->andReturn('mysql');

        $manager = new EmbeddingStoreManager;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported database driver: mysql');

        $manager->driver();
    }
}
