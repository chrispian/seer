<?php

use App\Jobs\EmbedFragment;
use App\Models\Fragment;
use App\Contracts\EmbeddingStoreInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

test('job skips when embeddings disabled', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', false);

    Log::partialMock()
        ->shouldReceive('debug')
        ->once()
        ->with('EmbedFragment: embeddings disabled, skipping', ['fragment_id' => 1]);

    $mockEmbeddings = Mockery::mock(\App\Services\AI\Embeddings::class);
    $mockEmbeddings->shouldNotReceive('embed');

    $mockStore = Mockery::mock(EmbeddingStoreInterface::class);
    $mockStore->shouldNotReceive('store');

    $job = new EmbedFragment(
        fragmentId: 1,
        provider: 'openai',
        model: 'text-embedding-3-small',
        contentHash: 'test-hash'
    );

    // Act
    $job->handle($mockEmbeddings, $mockStore);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('job skips when fragment not found', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);

    Log::partialMock()
        ->shouldReceive('warning')
        ->once()
        ->with('EmbedFragment: fragment not found', ['fragment_id' => 999]);

    $mockEmbeddings = Mockery::mock(\App\Services\AI\Embeddings::class);
    $mockEmbeddings->shouldNotReceive('embed');

    $mockStore = Mockery::mock(EmbeddingStoreInterface::class);
    $mockStore->shouldNotReceive('store');

    $job = new EmbedFragment(
        fragmentId: 999,
        provider: 'openai',
        model: 'text-embedding-3-small',
        contentHash: 'test-hash'
    );

    // Act
    $job->handle($mockEmbeddings, $mockStore);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('job skips when fragment has empty message', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);

    $fragment = Fragment::create([
        'message' => '',
        'type' => 'note',
    ]);

    Log::partialMock()
        ->shouldReceive('debug')
        ->once()
        ->with('EmbedFragment: empty text, skipping', ['fragment_id' => $fragment->id]);

    $mockEmbeddings = Mockery::mock(\App\Services\AI\Embeddings::class);
    $mockEmbeddings->shouldNotReceive('embed');

    $mockStore = Mockery::mock(EmbeddingStoreInterface::class);
    $mockStore->shouldNotReceive('store');

    $job = new EmbedFragment(
        fragmentId: $fragment->id,
        provider: 'openai',
        model: 'text-embedding-3-small',
        contentHash: 'test-hash'
    );

    // Act
    $job->handle($mockEmbeddings, $mockStore);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('job skips when using SQLite database', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);

    $fragment = Fragment::create([
        'message' => 'Test message',
        'type' => 'note',
    ]);

    // Since we're using SQLite in tests, this should naturally trigger the warning
    Log::partialMock()
        ->shouldReceive('warning')
        ->once()
        ->with('EmbedFragment: vector extension not available, skipping', [
            'fragment_id' => $fragment->id,
            'driver' => 'sqlite',
            'extension' => 'sqlite-vec',
            'available' => false,
        ]);

    $mockEmbeddings = Mockery::mock(\App\Services\AI\Embeddings::class);
    $mockEmbeddings->shouldNotReceive('embed');

    $mockStore = Mockery::mock(EmbeddingStoreInterface::class);
    $mockStore->shouldReceive('isVectorSupportAvailable')->andReturn(false);
    $mockStore->shouldReceive('getDriverInfo')->andReturn([
        'driver' => 'sqlite',
        'extension' => 'sqlite-vec',
        'available' => false,
    ]);
    $mockStore->shouldNotReceive('store');

    $job = new EmbedFragment(
        fragmentId: $fragment->id,
        provider: 'openai',
        model: 'text-embedding-3-small',
        contentHash: 'test-hash'
    );

    // Act
    $job->handle($mockEmbeddings, $mockStore);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
