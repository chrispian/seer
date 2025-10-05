<?php

namespace App\Services;

use App\Database\MigrationHelpers\VectorMigrationHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VectorCapabilityDetector
{
    protected array $capabilities = [];

    protected bool $detectionComplete = false;

    public function detectCapabilities(bool $useCache = true): array
    {
        if ($this->detectionComplete) {
            return $this->capabilities;
        }

        $cacheKey = 'vector_capabilities_'.$this->getDatabaseFingerprint();
        $cacheTtl = config('vectors.capabilities.cache_ttl', 3600);

        if ($useCache && config('vectors.capabilities.cache_detection_results', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $this->capabilities = $cached;
                $this->detectionComplete = true;

                return $this->capabilities;
            }
        }

        $this->performDetection();

        if ($useCache) {
            Cache::put($cacheKey, $this->capabilities, $cacheTtl);
        }

        $this->logCapabilityChanges();

        return $this->capabilities;
    }

    protected function performDetection(): void
    {
        $timeout = config('vectors.capabilities.detection_timeout', 5);

        try {
            $this->capabilities = [
                'database' => $this->detectDatabase(),
                'vector_support' => $this->detectVectorSupport(),
                'text_search' => $this->detectTextSearchCapabilities(),
                'extensions' => $this->detectExtensions(),
                'performance' => $this->detectPerformanceFeatures(),
                'configuration' => $this->detectConfiguration(),
                'detected_at' => now()->toISOString(),
                'detection_version' => '1.0',
            ];

            $this->detectionComplete = true;

        } catch (\Exception $e) {
            Log::error('Vector capability detection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Provide fallback capabilities
            $this->capabilities = $this->getFallbackCapabilities();
            $this->detectionComplete = true;
        }
    }

    protected function detectDatabase(): array
    {
        $driver = VectorMigrationHelper::getDriver();

        $info = [
            'driver' => $driver,
            'connection' => config('database.default'),
            'version' => null,
        ];

        try {
            if ($driver === 'pgsql') {
                $result = DB::select('SELECT version()')[0]->version ?? null;
                $info['version'] = $result;
                $info['is_postgresql'] = true;
                $info['is_sqlite'] = false;
            } elseif ($driver === 'sqlite') {
                $result = DB::select('SELECT sqlite_version() as version')[0]->version ?? null;
                $info['version'] = $result;
                $info['is_postgresql'] = false;
                $info['is_sqlite'] = true;
            }
        } catch (\Exception $e) {
            Log::warning('Database version detection failed', ['error' => $e->getMessage()]);
        }

        return $info;
    }

    protected function detectVectorSupport(): array
    {
        $driver = VectorMigrationHelper::getDriver();

        if ($driver === 'pgsql') {
            return $this->detectPostgreSQLVectorSupport();
        } elseif ($driver === 'sqlite') {
            return $this->detectSQLiteVectorSupport();
        }

        return [
            'available' => false,
            'reason' => 'Unsupported database driver: '.$driver,
        ];
    }

    protected function detectPostgreSQLVectorSupport(): array
    {
        try {
            $extensions = DB::select("SELECT extname, extversion FROM pg_extension WHERE extname = 'vector'");

            if (empty($extensions)) {
                return [
                    'available' => false,
                    'reason' => 'pgvector extension not installed',
                ];
            }

            $extension = $extensions[0];

            // Test vector operations
            $testPassed = $this->testPostgreSQLVectorOperations();

            return [
                'available' => $testPassed,
                'extension' => 'pgvector',
                'version' => $extension->extversion,
                'operations_tested' => $testPassed,
                'supported_functions' => $this->getPostgreSQLVectorFunctions(),
                'index_types' => ['hnsw', 'ivfflat'],
                'similarity_functions' => ['cosine', 'l2', 'inner_product'],
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'reason' => 'Detection failed: '.$e->getMessage(),
            ];
        }
    }

    protected function detectSQLiteVectorSupport(): array
    {
        try {
            // Test sqlite-vec extension
            $result = DB::select('SELECT vec_version() as version');

            if (empty($result)) {
                return [
                    'available' => false,
                    'reason' => 'sqlite-vec extension not available',
                ];
            }

            $testPassed = $this->testSQLiteVectorOperations();

            return [
                'available' => $testPassed,
                'extension' => 'sqlite-vec',
                'version' => $result[0]->version,
                'operations_tested' => $testPassed,
                'supported_functions' => $this->getSQLiteVectorFunctions(),
                'index_types' => ['vec0'],
                'similarity_functions' => ['cosine', 'l2'],
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'reason' => 'sqlite-vec extension not available: '.$e->getMessage(),
            ];
        }
    }

    protected function detectTextSearchCapabilities(): array
    {
        $driver = VectorMigrationHelper::getDriver();

        if ($driver === 'pgsql') {
            return $this->detectPostgreSQLTextSearch();
        } elseif ($driver === 'sqlite') {
            return $this->detectSQLiteTextSearch();
        }

        return ['available' => false];
    }

    protected function detectPostgreSQLTextSearch(): array
    {
        try {
            // Test tsvector functionality
            $result = DB::select("SELECT to_tsvector('english', 'test') as test_vector");

            return [
                'available' => ! empty($result),
                'type' => 'tsvector',
                'languages' => $this->getPostgreSQLTextSearchLanguages(),
                'ranking_functions' => ['ts_rank', 'ts_rank_cd'],
                'operators' => ['@@', '<->', '||'],
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    protected function detectSQLiteTextSearch(): array
    {
        $capabilities = ['available' => false];

        // Test FTS5
        try {
            DB::statement('CREATE VIRTUAL TABLE IF NOT EXISTS _fts_capability_test USING fts5(content)');
            DB::statement('DROP TABLE _fts_capability_test');

            $capabilities['fts5'] = [
                'available' => true,
                'tokenizers' => ['porter', 'ascii', 'unicode61'],
            ];
            $capabilities['available'] = true;

        } catch (\Exception $e) {
            $capabilities['fts5'] = [
                'available' => false,
                'reason' => $e->getMessage(),
            ];
        }

        return $capabilities;
    }

    protected function detectExtensions(): array
    {
        $driver = VectorMigrationHelper::getDriver();
        $extensions = [];

        if ($driver === 'pgsql') {
            try {
                $result = DB::select('SELECT extname, extversion FROM pg_extension ORDER BY extname');
                foreach ($result as $ext) {
                    $extensions[$ext->extname] = $ext->extversion;
                }
            } catch (\Exception $e) {
                Log::warning('PostgreSQL extension detection failed', ['error' => $e->getMessage()]);
            }
        } elseif ($driver === 'sqlite') {
            // SQLite extension detection is more limited
            $knownExtensions = ['vec', 'fts5', 'json1', 'rtree'];

            foreach ($knownExtensions as $ext) {
                try {
                    switch ($ext) {
                        case 'vec':
                            DB::select('SELECT vec_version()');
                            $extensions[$ext] = 'available';
                            break;
                        case 'fts5':
                            DB::statement('CREATE VIRTUAL TABLE _test_fts5 USING fts5(test)');
                            DB::statement('DROP TABLE _test_fts5');
                            $extensions[$ext] = 'available';
                            break;
                        case 'json1':
                            DB::select("SELECT json('{}')");
                            $extensions[$ext] = 'available';
                            break;
                        case 'rtree':
                            DB::statement('CREATE VIRTUAL TABLE _test_rtree USING rtree(id, minX, maxX, minY, maxY)');
                            DB::statement('DROP TABLE _test_rtree');
                            $extensions[$ext] = 'available';
                            break;
                    }
                } catch (\Exception $e) {
                    // Extension not available
                }
            }
        }

        return $extensions;
    }

    protected function detectPerformanceFeatures(): array
    {
        return [
            'concurrent_operations' => config('vectors.performance.enable_concurrent_operations', true),
            'query_cache' => config('vectors.performance.enable_query_cache', false),
            'batch_operations' => true,
            'index_optimization' => $this->detectIndexOptimization(),
        ];
    }

    protected function detectConfiguration(): array
    {
        return [
            'default_driver' => config('vectors.default', 'auto'),
            'hybrid_search_enabled' => true,
            'debug_mode' => config('vectors.debug.enable_diagnostics', true),
            'cache_enabled' => config('vectors.capabilities.cache_detection_results', true),
        ];
    }

    // Helper methods for specific tests
    protected function testPostgreSQLVectorOperations(): bool
    {
        try {
            DB::select("SELECT '[1,2,3]'::vector <-> '[1,2,4]'::vector as distance");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function testSQLiteVectorOperations(): bool
    {
        try {
            $vec1 = pack('f*', 1, 2, 3);
            $vec2 = pack('f*', 1, 2, 4);
            DB::select('SELECT vec_distance_cosine(?, ?) as distance', [$vec1, $vec2]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getPostgreSQLVectorFunctions(): array
    {
        return ['<->', '<#>', '<=>', 'cosine_distance', 'l2_distance', 'inner_product'];
    }

    protected function getSQLiteVectorFunctions(): array
    {
        return ['vec_distance_cosine', 'vec_distance_l2', 'vec_normalize'];
    }

    protected function getPostgreSQLTextSearchLanguages(): array
    {
        try {
            $result = DB::select('SELECT cfgname FROM pg_ts_config');

            return array_map(fn ($row) => $row->cfgname, $result);
        } catch (\Exception $e) {
            return ['english']; // fallback
        }
    }

    protected function detectIndexOptimization(): array
    {
        $driver = VectorMigrationHelper::getDriver();

        try {
            if ($driver === 'pgsql') {
                $indexes = DB::select("
                    SELECT indexname, indexdef 
                    FROM pg_indexes 
                    WHERE tablename = 'fragment_embeddings'
                ");

                return [
                    'vector_indexes' => count(array_filter($indexes, fn ($idx) => str_contains($idx->indexdef, 'hnsw') || str_contains($idx->indexdef, 'ivfflat')
                    )),
                    'total_indexes' => count($indexes),
                ];
            } elseif ($driver === 'sqlite') {
                $indexes = DB::select("
                    SELECT name, sql 
                    FROM sqlite_master 
                    WHERE type = 'index' AND tbl_name = 'fragment_embeddings'
                ");

                return [
                    'indexes' => count($indexes),
                    'vector_tables' => $this->countSQLiteVectorTables(),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Index optimization detection failed', ['error' => $e->getMessage()]);
        }

        return ['detected' => false];
    }

    protected function countSQLiteVectorTables(): int
    {
        try {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM sqlite_master 
                WHERE type = 'table' AND name LIKE '%_vec_idx'
            ");

            return $result[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getDatabaseFingerprint(): string
    {
        return md5(
            config('database.default').
            VectorMigrationHelper::getDriver().
            (app()->environment() ?? 'unknown')
        );
    }

    protected function getFallbackCapabilities(): array
    {
        return [
            'database' => ['driver' => 'unknown'],
            'vector_support' => ['available' => false],
            'text_search' => ['available' => false],
            'extensions' => [],
            'performance' => ['concurrent_operations' => false],
            'configuration' => ['default_driver' => 'fallback'],
            'detected_at' => now()->toISOString(),
            'detection_version' => '1.0',
            'fallback' => true,
        ];
    }

    protected function logCapabilityChanges(): void
    {
        if (! config('vectors.capabilities.log_capability_changes', true)) {
            return;
        }

        Log::info('Vector capabilities detected', [
            'database' => $this->capabilities['database']['driver'] ?? 'unknown',
            'vector_available' => $this->capabilities['vector_support']['available'] ?? false,
            'text_search_available' => $this->capabilities['text_search']['available'] ?? false,
            'extensions' => array_keys($this->capabilities['extensions'] ?? []),
        ]);
    }

    public function clearCache(): void
    {
        $cacheKey = 'vector_capabilities_'.$this->getDatabaseFingerprint();
        Cache::forget($cacheKey);
        $this->detectionComplete = false;
        $this->capabilities = [];
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function isDetectionComplete(): bool
    {
        return $this->detectionComplete;
    }
}
