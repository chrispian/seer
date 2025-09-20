<?php

use App\Models\Fragment;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

test('backfill command fails when embeddings disabled', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', false);

    // Act
    $this->artisan('embeddings:backfill')
        ->expectsOutput('❌ Embeddings are currently disabled. Set EMBEDDINGS_ENABLED=true to enable backfilling.')
        ->assertExitCode(1);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('backfill command fails when pgvector not available', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);
    Config::set('fragments.embeddings.provider', 'openai');
    Config::set('fragments.embeddings.model', 'text-embedding-3-small');

    // Act & Assert - SQLite doesn't have pgvector so command should fail
    $this->artisan('embeddings:backfill')
        ->expectsOutput('❌ pgvector extension is not available. Embeddings require PostgreSQL with pgvector.')
        ->assertExitCode(1);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('backfill command dry run fails when pgvector not available', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);
    Config::set('fragments.embeddings.provider', 'openai');
    Config::set('fragments.embeddings.model', 'text-embedding-3-small');

    Fragment::create([
        'message' => 'Test fragment 1',
        'type' => 'note',
    ]);

    Fragment::create([
        'message' => 'Test fragment 2',
        'type' => 'note',
    ]);

    // Act & Assert - SQLite doesn't have pgvector so command should fail
    $this->artisan('embeddings:backfill --dry-run')
        ->expectsOutput('❌ pgvector extension is not available. Embeddings require PostgreSQL with pgvector.')
        ->assertExitCode(1);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('backfill command with force flag fails when pgvector not available', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);
    Config::set('fragments.embeddings.provider', 'openai');
    Config::set('fragments.embeddings.model', 'text-embedding-3-small');

    Fragment::create([
        'message' => 'Test fragment 1',
        'type' => 'note',
    ]);

    Fragment::create([
        'message' => 'Test fragment 2',
        'type' => 'note',
    ]);

    Queue::fake();

    // Act & Assert - SQLite doesn't have pgvector so command should fail
    $this->artisan('embeddings:backfill --force')
        ->expectsOutput('❌ pgvector extension is not available. Embeddings require PostgreSQL with pgvector.')
        ->assertExitCode(1);

    // Assert no jobs were queued
    Queue::assertNothingPushed();
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('backfill command with batch size fails when pgvector not available', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);
    Config::set('fragments.embeddings.provider', 'openai');
    Config::set('fragments.embeddings.model', 'text-embedding-3-small');

    // Create 5 fragments
    for ($i = 1; $i <= 5; $i++) {
        Fragment::create([
            'message' => "Test fragment {$i}",
            'type' => 'note',
        ]);
    }

    // Act & Assert - SQLite doesn't have pgvector so command should fail
    $this->artisan('embeddings:backfill --batch=2 --dry-run')
        ->expectsOutput('❌ pgvector extension is not available. Embeddings require PostgreSQL with pgvector.')
        ->assertExitCode(1);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('backfill command with mixed fragments fails when pgvector not available', function () {
    // Arrange
    Config::set('fragments.embeddings.enabled', true);
    Config::set('fragments.embeddings.provider', 'openai');
    Config::set('fragments.embeddings.model', 'text-embedding-3-small');

    Fragment::create([
        'message' => 'Valid fragment',
        'type' => 'note',
    ]);

    Fragment::create([
        'message' => '',
        'type' => 'note',
    ]);

    // Note: Fragment with null message won't be created due to NOT NULL constraint
    // This test validates the backfill command fails for SQLite

    // Act & Assert - SQLite doesn't have pgvector so command should fail
    $this->artisan('embeddings:backfill --dry-run')
        ->expectsOutput('❌ pgvector extension is not available. Embeddings require PostgreSQL with pgvector.')
        ->assertExitCode(1);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
