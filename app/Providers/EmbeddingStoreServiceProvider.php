<?php

namespace App\Providers;

use App\Contracts\EmbeddingStoreInterface;
use App\Services\Embeddings\EmbeddingStoreManager;
use Illuminate\Support\ServiceProvider;

class EmbeddingStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmbeddingStoreManager::class);

        $this->app->bind(EmbeddingStoreInterface::class, function ($app) {
            return $app->make(EmbeddingStoreManager::class)->driver();
        });
    }

    public function boot(): void
    {
        // Additional boot logic if needed
    }
}