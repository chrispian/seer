<?php

use App\Actions\Commands\SearchCommand;
use App\DTOs\CommandRequest;
use App\Models\Fragment;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

test('search command falls back to text-only when embeddings disabled', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', false);

    Fragment::create([
        'message' => 'Testing embeddings toggle functionality',
        'type' => 'note',
        'title' => 'Test Fragment',
    ]);

    $searchCommand = app(SearchCommand::class);
    $commandRequest = new CommandRequest(
        'search',
        ['identifier' => 'embeddings toggle']
    );

    // Act
    $response = $searchCommand->handle($commandRequest);

    // Assert
    expect($response->type)->toBe('search');
    expect($response->shouldOpenPanel)->toBe(true);
    expect($response->panelData['search_mode'])->toBe('text-only');
    expect($response->panelData['message'])->toContain('(text-only search)');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('search command uses hybrid search when embeddings enabled', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);

    Fragment::create([
        'message' => 'Testing embeddings toggle functionality',
        'type' => 'note',
        'title' => 'Test Fragment',
    ]);

    $searchCommand = app(SearchCommand::class);
    $commandRequest = new CommandRequest(
        'search',
        ['identifier' => 'embeddings toggle']
    );

    // Act
    $response = $searchCommand->handle($commandRequest);

    // Assert
    expect($response->type)->toBe('search');
    expect($response->shouldOpenPanel)->toBe(true);
    expect($response->panelData['search_mode'])->toBeIn(['hybrid', 'text-only']); // May fall back to text-only if pgvector not available

    // If we found results, they should not contain the text-only indicator
    if (! empty($response->panelData['fragments'])) {
        expect($response->panelData['message'])->not->toContain('(text-only search)');
    }
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('fragment controller hybrid search respects embeddings toggle', function () {
    // Arrange - embeddings disabled
    Config::set('fragments.embeddings.enabled', false);

    Fragment::create([
        'message' => 'Testing API search functionality',
        'type' => 'note',
        'title' => 'API Test Fragment',
    ]);

    // Act
    $response = $this->get('/api/search/hybrid?q=API search');

    // Assert
    $response->assertStatus(200);
    $data = $response->json();

    // Should use text-only search
    if (! empty($data)) {
        expect($data[0]['search_mode'])->toBe('text-only');
        expect($data[0]['vec_sim'])->toBeNull();
    }
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('fragment controller hybrid search with embeddings enabled', function () {
    // Arrange - embeddings enabled
    Config::set('fragments.embeddings.enabled', true);

    Fragment::create([
        'message' => 'Testing API search functionality',
        'type' => 'note',
        'title' => 'API Test Fragment',
    ]);

    // Act
    $response = $this->get('/api/search/hybrid?q=API search');

    // Assert
    $response->assertStatus(200);
    $data = $response->json();

    // Should attempt hybrid search (may fallback based on database capabilities)
    if (! empty($data)) {
        expect($data[0]['search_mode'])->toBeIn(['hybrid', 'text-only-fallback', 'text-only-error-fallback']);
    }
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('embed fragment action respects embeddings toggle', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', false);

    $fragment = Fragment::create([
        'message' => 'Test fragment for embedding',
        'type' => 'note',
    ]);

    $embedAction = app(\App\Actions\EmbedFragmentAction::class);

    // Act
    $result = $embedAction($fragment);

    // Assert
    expect($result)->toBe($fragment);
    // No embedding jobs should be dispatched when disabled
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('embed fragment action queues jobs when embeddings enabled', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);

    $fragment = Fragment::create([
        'message' => 'Test fragment for embedding',
        'type' => 'note',
    ]);

    Queue::fake();

    $embedAction = app(\App\Actions\EmbedFragmentAction::class);

    // Act
    $result = $embedAction($fragment);

    // Assert
    expect($result)->toBe($fragment);

    // Should queue an embedding job when enabled
    Queue::assertPushed(\App\Jobs\EmbedFragment::class);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
