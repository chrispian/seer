<?php

namespace App\Services\Search;

use App\Contracts\HybridSearchInterface;
use App\Database\MigrationHelpers\VectorMigrationHelper;
use Illuminate\Support\Manager;

class HybridSearchManager extends Manager implements HybridSearchInterface
{
    protected $embeddingStore;

    public function __construct($app)
    {
        parent::__construct($app);

        // Try to resolve embedding store, but don't fail if it's not available
        try {
            $this->embeddingStore = $app['embedding-store'];
        } catch (\Exception $e) {
            $this->embeddingStore = null;
        }
    }

    public function getDefaultDriver()
    {
        // Auto-detect best driver based on database capabilities
        if (VectorMigrationHelper::isPostgreSQL() && VectorMigrationHelper::hasPgVectorExtension()) {
            return 'postgresql';
        } elseif (VectorMigrationHelper::isSQLite()) {
            if (VectorMigrationHelper::hasSQLiteVectorSupport() && VectorMigrationHelper::hasFTS5Support()) {
                return 'sqlite_full';
            } elseif (VectorMigrationHelper::hasFTS5Support()) {
                return 'sqlite_text';
            } else {
                return 'sqlite_basic';
            }
        }

        return 'fallback';
    }

    protected function createPostgresqlDriver()
    {
        return new PostgreSQLHybridSearch($this->embeddingStore);
    }

    protected function createSqliteFullDriver()
    {
        return new SQLiteHybridSearch($this->embeddingStore, true, true);
    }

    protected function createSqliteTextDriver()
    {
        return new SQLiteHybridSearch($this->embeddingStore, false, true);
    }

    protected function createSqliteBasicDriver()
    {
        return new SQLiteHybridSearch($this->embeddingStore, false, false);
    }

    protected function createFallbackDriver()
    {
        return new FallbackHybridSearch($this->embeddingStore);
    }

    // Proxy methods to default driver
    public function hybridSearch(string $query, array $options = []): array
    {
        return $this->driver()->hybridSearch($query, $options);
    }

    public function vectorSearch(array $vector, array $options = []): array
    {
        return $this->driver()->vectorSearch($vector, $options);
    }

    public function textSearch(string $query, array $options = []): array
    {
        return $this->driver()->textSearch($query, $options);
    }

    public function hasVectorCapability(): bool
    {
        return $this->driver()->hasVectorCapability();
    }

    public function hasTextCapability(): bool
    {
        return $this->driver()->hasTextCapability();
    }

    public function getCapabilities(): array
    {
        return $this->driver()->getCapabilities();
    }
}
