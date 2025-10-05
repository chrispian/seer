<?php

namespace Tests\Unit;

use App\Services\Embeddings\SqliteVectorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqliteVectorStoreTest extends TestCase
{
    use RefreshDatabase;

    protected SqliteVectorStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'sqlite');
        $this->store = new SqliteVectorStore;
    }

    public function test_vector_blob_conversion()
    {
        $originalVector = [0.1, 0.2, 0.3, 0.4, 0.5];

        $blob = $this->invokeMethod($this->store, 'vectorToBlob', [$originalVector]);
        $converted = $this->invokeMethod($this->store, 'blobToVector', [$blob]);

        // Check that each value is approximately equal (floating point precision)
        for ($i = 0; $i < count($originalVector); $i++) {
            $this->assertEqualsWithDelta($originalVector[$i], $converted[$i], 0.0001,
                "Vector element {$i} doesn't match after conversion");
        }
    }

    public function test_extension_detection()
    {
        $available = $this->store->isVectorSupportAvailable();
        $info = $this->store->getDriverInfo();

        $this->assertIsBool($available);
        $this->assertArrayHasKey('driver', $info);
        $this->assertEquals('sqlite', $info['driver']);
        $this->assertArrayHasKey('extension', $info);
        $this->assertArrayHasKey('available', $info);
    }

    public function test_driver_info_structure()
    {
        $info = $this->store->getDriverInfo();

        $expectedKeys = ['driver', 'extension', 'available', 'version'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $info, "Driver info missing key: {$key}");
        }

        $this->assertEquals('sqlite', $info['driver']);
        $this->assertEquals('sqlite-vec', $info['extension']);
    }

    public function test_diagnose_connection()
    {
        $diagnosis = $this->store->diagnoseConnection();

        $expectedKeys = ['sqlite_version', 'extension_loaded', 'extension_version', 'tables_exist', 'sample_query_works'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $diagnosis, "Diagnosis missing key: {$key}");
        }

        // SQLite version should always be available
        $this->assertNotNull($diagnosis['sqlite_version']);
    }

    public function test_store_and_exists_when_extension_available()
    {
        if (! $this->store->isVectorSupportAvailable()) {
            $this->markTestSkipped('SQLite vector extension not available');
        }

        // Ensure the table exists
        $this->createFragmentEmbeddingsTable();

        $vector = array_fill(0, 10, 0.1); // Small vector for testing

        $this->store->store(1, 'openai', 'text-embedding-3-small', 10, $vector, 'test-hash');

        $exists = $this->store->exists(1, 'openai', 'text-embedding-3-small', 'test-hash');
        $this->assertTrue($exists);
    }

    public function test_store_gracefully_fails_when_extension_unavailable()
    {
        if ($this->store->isVectorSupportAvailable()) {
            $this->markTestSkipped('SQLite vector extension is available, cannot test failure case');
        }

        $vector = array_fill(0, 10, 0.1);

        // Should not throw exception, just log warning and return
        $this->store->store(1, 'openai', 'text-embedding-3-small', 10, $vector, 'test-hash');

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_search_returns_empty_when_extension_unavailable()
    {
        if ($this->store->isVectorSupportAvailable()) {
            $this->markTestSkipped('SQLite vector extension is available, cannot test failure case');
        }

        $queryVector = array_fill(0, 10, 0.1);
        $results = $this->store->search($queryVector, 'openai');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    protected function createFragmentEmbeddingsTable(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS fragment_embeddings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fragment_id INTEGER NOT NULL,
            provider TEXT NOT NULL,
            model TEXT NOT NULL,
            dims INTEGER NOT NULL,
            embedding BLOB,
            content_hash TEXT NOT NULL,
            created_at TEXT,
            updated_at TEXT,
            UNIQUE(fragment_id, provider, model, content_hash)
        )');
    }

    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
