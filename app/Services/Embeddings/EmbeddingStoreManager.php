<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingStoreInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmbeddingStoreManager
{
    protected array $drivers = [];

    public function driver(?string $connection = null): EmbeddingStoreInterface
    {
        $connection = $connection ?? $this->getDefaultDriver();

        if (! isset($this->drivers[$connection])) {
            $this->drivers[$connection] = $this->createDriver($connection);
        }

        return $this->drivers[$connection];
    }

    protected function getDefaultDriver(): string
    {
        $configured = config('fragments.embeddings.driver', 'auto');

        if ($configured === 'auto') {
            return $this->detectOptimalDriver();
        }

        return $configured;
    }

    protected function detectOptimalDriver(): string
    {
        $dbDriver = DB::connection()->getDriverName();

        return match ($dbDriver) {
            'sqlite' => 'sqlite',
            'pgsql' => 'postgresql',
            default => throw new InvalidArgumentException("Unsupported database driver: {$dbDriver}")
        };
    }

    protected function createDriver(string $driver): EmbeddingStoreInterface
    {
        return match ($driver) {
            'sqlite' => new SqliteVectorStore,
            'postgresql' => new PgVectorStore,
            default => throw new InvalidArgumentException("Unknown embedding driver: {$driver}")
        };
    }

    public function getSupportedDrivers(): array
    {
        return ['sqlite', 'postgresql'];
    }
}
