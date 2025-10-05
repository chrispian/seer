<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use App\DTOs\VectorSearchResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class SqliteVectorStore implements EmbeddingStoreInterface
{
    protected ?PDO $pdo = null;
    protected bool $extensionLoaded = false;
    protected array $driverInfo = [];

    public function __construct()
    {
        $this->initializeExtension();
    }

    protected function initializeExtension(): void
    {
        if (VectorMigrationHelper::getDriver() !== 'sqlite') {
            $this->extensionLoaded = false;
            $this->driverInfo['error'] = 'Not using SQLite connection';
            Log::debug('SQLite vector extension initialization failed', [
                'error' => 'Not using SQLite connection',
                'extension' => 'sqlite-vec'
            ]);
            return;
        }

        // Try to auto-load extension if configured
        $this->attemptExtensionLoading();

        try {
            // Try to call a sqlite-vec function to test if it's available
            $result = DB::select("SELECT vec_version() as version");
            
            if (!empty($result)) {
                $this->extensionLoaded = true;
                $this->driverInfo = [
                    'driver' => 'sqlite',
                    'extension' => 'sqlite-vec',
                    'version' => $result[0]->version,
                    'status' => 'available',
                ];
                
                Log::info('SQLite vector extension loaded successfully', [
                    'version' => $result[0]->version
                ]);
            }
            
        } catch (\Exception $e) {
            $this->extensionLoaded = false;
            $this->driverInfo = [
                'driver' => 'sqlite',
                'extension' => 'sqlite-vec',
                'status' => 'not_available',
                'error' => $e->getMessage(),
            ];
            
            Log::debug('SQLite vector extension not available', [
                'error' => $e->getMessage(),
                'extension' => 'sqlite-vec'
            ]);
        }
    }

    protected function attemptExtensionLoading(): void
    {
        if (!config('vectors.drivers.sqlite.auto_load_extension', true)) {
            return;
        }

        $extensionPath = $this->detectExtensionPath();
        if (!$extensionPath) {
            return;
        }

        try {
            DB::statement("SELECT load_extension(?)", [$extensionPath]);
            Log::info('SQLite vector extension loaded from path', [
                'path' => $extensionPath
            ]);
        } catch (\Exception $e) {
            Log::debug('Failed to load SQLite vector extension', [
                'path' => $extensionPath,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function detectExtensionPath(): ?string
    {
        // Check explicit configuration first
        $configuredPath = config('vectors.drivers.sqlite.extension_path');
        if ($configuredPath && $configuredPath !== 'auto-detect' && file_exists($configuredPath)) {
            return $configuredPath;
        }

        // Auto-detection for NativePHP deployments
        if ($configuredPath === 'auto-detect' || !$configuredPath) {
            return $this->autoDetectExtensionPath();
        }

        return null;
    }

    protected function autoDetectExtensionPath(): ?string
    {
        $basePath = base_path();
        
        // Platform detection
        $platform = PHP_OS_FAMILY;
        $arch = php_uname('m');
        
        // Normalize names
        $platformMap = [
            'Darwin' => 'macos',
            'Linux' => 'linux',
            'Windows' => 'windows',
        ];
        
        $archMap = [
            'x86_64' => 'x86_64',
            'aarch64' => 'aarch64', 
            'arm64' => 'aarch64',
            'AMD64' => 'x86_64',
        ];
        
        $extensionMap = [
            'macos' => '.dylib',
            'linux' => '.so',
            'windows' => '.dll',
        ];
        
        $normalizedPlatform = $platformMap[$platform] ?? strtolower($platform);
        $normalizedArch = $archMap[$arch] ?? $arch;
        $extension = $extensionMap[$normalizedPlatform] ?? '.so';
        
        // Search paths in order of preference
        $searchPaths = [
            // Platform-specific packaged extension
            "{$basePath}/storage/extensions/vec-{$normalizedPlatform}-{$normalizedArch}{$extension}",
            
            // Generic extension in storage
            "{$basePath}/storage/extensions/vec{$extension}",
            "{$basePath}/storage/extensions/sqlite-vec{$extension}",
            
            // System-wide installations
            "/usr/local/lib/vec{$extension}",
            "/usr/lib/sqlite3/vec{$extension}",
            
            // Homebrew on macOS
            "/opt/homebrew/lib/vec{$extension}",
            "/usr/local/homebrew/lib/vec{$extension}",
        ];
        
        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                Log::debug('Auto-detected SQLite vector extension', [
                    'path' => $path,
                    'platform' => $normalizedPlatform,
                    'arch' => $normalizedArch
                ]);
                return $path;
            }
        }
        
        return null;
    }
            
            // Try to load extension if PDO supports it and extension loading is enabled
            if (method_exists($this->pdo, 'loadExtension')) {
                $this->loadExtensionIfAvailable($extensionName);
            } else {
                // Try to test if extension is already available (pre-loaded)
                $this->testExistingExtension();
            }
            
        } catch (PDOException $e) {
            Log::debug('SQLite vector extension initialization failed', [
                'error' => $e->getMessage(),
                'extension' => $extensionName,
            ]);
            $this->extensionLoaded = false;
        }
        
        $this->driverInfo = [
            'driver' => 'sqlite',
            'extension' => $extensionName,
            'available' => $this->extensionLoaded,
            'version' => $this->extensionLoaded ? $this->getExtensionVersion() : null,
        ];
    }

    protected function loadExtensionIfAvailable(string $extensionName): void
    {
        $extensionPath = config('fragments.embeddings.drivers.sqlite.extension_path');
        
        if ($extensionPath) {
            $this->pdo->loadExtension($extensionPath);
        } else {
            // Try common extension names/paths
            $this->tryLoadExtension($extensionName);
        }
        
        // Verify extension loaded by testing a function
        $version = $this->pdo->query("SELECT vec_version()")->fetchColumn();
        $this->extensionLoaded = true;
        
        Log::info('SQLite vector extension loaded successfully', [
            'extension' => $extensionName,
            'version' => $version,
        ]);
    }

    protected function testExistingExtension(): void
    {
        try {
            // Test if extension functions are available (pre-loaded or built-in)
            $version = $this->pdo->query("SELECT vec_version()")->fetchColumn();
            $this->extensionLoaded = true;
            
            Log::info('SQLite vector extension already available', [
                'version' => $version,
            ]);
        } catch (PDOException $e) {
            // Extension not available
            $this->extensionLoaded = false;
            Log::debug('SQLite vector extension not available', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function tryLoadExtension(string $extensionName): void
    {
        $attempts = [
            $extensionName,
            "lib{$extensionName}",
            "{$extensionName}.so",
            "lib{$extensionName}.so",
            "{$extensionName}.dll",
            "{$extensionName}.dylib",
        ];
        
        foreach ($attempts as $attempt) {
            try {
                $this->pdo->loadExtension($attempt);
                return; // Success
            } catch (PDOException $e) {
                // Try next variant
                continue;
            }
        }
        
        throw new PDOException("Could not load extension with any attempted name");
    }

    protected function getExtensionVersion(): ?string
    {
        try {
            return $this->pdo->query("SELECT vec_version()")->fetchColumn();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function isVectorSupportAvailable(): bool
    {
        return $this->extensionLoaded;
    }

    public function getDriverInfo(): array
    {
        return $this->driverInfo;
    }

    public function embed(string $text): array
    {
        // For now, delegate to a simple embedding service
        // In the future, this could be configurable per provider
        $embeddingService = app(\App\Services\EmbeddingService::class);
        return $embeddingService->embed($text);
    }

    public function convertToBlob(array $vector): string
    {
        return $this->vectorToBlob($vector);
    }

    // Vector data conversion methods
    protected function vectorToBlob(array $vector): string
    {
        // Convert PHP array to binary format expected by sqlite-vec
        // Using float32 little-endian format
        return pack('f*', ...$vector);
    }

    protected function blobToVector(string $blob): array
    {
        $unpacked = unpack('f*', $blob);
        return array_values($unpacked);
    }

    public function store(int $fragmentId, string $provider, string $model, int $dimensions, array $vector, string $contentHash): void
    {
        if (!$this->isVectorSupportAvailable()) {
            Log::warning('SQLite vector store: extension not available, skipping storage', [
                'fragment_id' => $fragmentId,
                'provider' => $provider,
            ]);
            return;
        }

        try {
            $vectorBlob = $this->vectorToBlob($vector);
            
            // SQLite upsert using INSERT OR REPLACE
            $sql = "
                INSERT OR REPLACE INTO fragment_embeddings 
                (fragment_id, provider, model, dims, embedding, content_hash, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ";
            
            DB::statement($sql, [
                $fragmentId,
                $provider, 
                $model,
                $dimensions,
                $vectorBlob,
                $contentHash
            ]);
            
            Log::debug('SQLite vector store: embedding saved', [
                'fragment_id' => $fragmentId,
                'provider' => $provider,
                'model' => $model,
                'dimensions' => $dimensions,
            ]);
            
        } catch (\Exception $e) {
            Log::error('SQLite vector store: failed to store embedding', [
                'fragment_id' => $fragmentId,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
            Log::warning('SQLite vector store: extension not available, returning empty results');
            return [];
        }

        try {
            $queryBlob = $this->vectorToBlob($queryVector);
            
            // Basic vector similarity search using sqlite-vec
            $sql = "
                SELECT 
                    fe.fragment_id,
                    (1 - vec_distance_cosine(fe.embedding, ?)) AS similarity,
                    0 AS text_rank,
                    (1 - vec_distance_cosine(fe.embedding, ?)) AS combined_score,
                    SUBSTR(COALESCE(f.message, ''), 1, 200) AS snippet
                FROM fragment_embeddings fe
                JOIN fragments f ON fe.fragment_id = f.id
                WHERE fe.provider = ?
                  AND (1 - vec_distance_cosine(fe.embedding, ?)) >= ?
                ORDER BY similarity DESC
                LIMIT ?
            ";
            
            $results = DB::select($sql, [
                $queryBlob, // For similarity calculation
                $queryBlob, // For score calculation  
                $provider,
                $queryBlob, // For threshold comparison
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
            
        } catch (\Exception $e) {
            Log::error('SQLite vector store: search failed', [
                'provider' => $provider,
                'limit' => $limit,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // Diagnostic methods
    public function diagnoseConnection(): array
    {
        $diagnosis = [
            'sqlite_version' => null,
            'extension_loaded' => $this->extensionLoaded,
            'extension_version' => null,
            'tables_exist' => false,
            'sample_query_works' => false,
        ];
        
        try {
            $diagnosis['sqlite_version'] = $this->pdo->query("SELECT sqlite_version()")->fetchColumn();
            
            if ($this->extensionLoaded) {
                $diagnosis['extension_version'] = $this->getExtensionVersion();
                
                // Test basic vector operation
                $testVector = array_fill(0, 10, 0.1);
                $testBlob = $this->vectorToBlob($testVector);
                $stmt = $this->pdo->prepare("SELECT vec_distance_cosine(?, ?)");
                $stmt->execute([$testBlob, $testBlob]);
                $diagnosis['sample_query_works'] = true;
            }
            
            // Check table existence
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='fragment_embeddings'");
            $diagnosis['tables_exist'] = !empty($tables);
            
        } catch (\Exception $e) {
            $diagnosis['error'] = $e->getMessage();
        }
        
        return $diagnosis;
    }
}