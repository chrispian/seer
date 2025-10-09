<?php

use App\Models\Fragment;
use App\Models\User;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->testVaultPath = storage_path('testing/obsidian-vault');
    
    if (File::exists($this->testVaultPath)) {
        File::deleteDirectory($this->testVaultPath);
    }
    File::makeDirectory($this->testVaultPath, 0755, true);
});

afterEach(function () {
    if (File::exists($this->testVaultPath)) {
        File::deleteDirectory($this->testVaultPath);
    }
});

test('imports obsidian notes with filename as title', function () {
    File::put($this->testVaultPath.'/My Note.md', <<<MD
# Heading in File
This is the content of my note.
MD
    );

    $this->user->update([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->testVaultPath,
                    'sync_enabled' => true,
                ],
            ],
        ],
    ]);

    $this->artisan('obsidian:sync')
        ->assertSuccessful();

    $fragment = Fragment::where('source_key', 'obsidian')->first();

    expect($fragment)->not->toBeNull()
        ->and($fragment->title)->toBe('My Note')
        ->and($fragment->getAttribute('message'))->toContain('# Heading in File')
        ->and($fragment->vault)->toBe('codex')
        ->and($fragment->tags)->toContain('obsidian');
});

test('front matter title overrides filename', function () {
    File::put($this->testVaultPath.'/filename.md', <<<MD
---
title: Front Matter Title
---
Content here
MD
    );

    $this->user->update([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->testVaultPath,
                    'sync_enabled' => true,
                ],
            ],
        ],
    ]);

    $this->artisan('obsidian:sync')
        ->assertSuccessful();

    $fragment = Fragment::where('source_key', 'obsidian')->first();

    expect($fragment->title)->toBe('Front Matter Title');
});

test('upserts by obsidian path - same file updates existing fragment', function () {
    $filePath = $this->testVaultPath.'/Test Note.md';
    
    File::put($filePath, 'Original content');

    $this->user->update([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->testVaultPath,
                    'sync_enabled' => true,
                ],
            ],
        ],
    ]);

    $this->artisan('obsidian:sync');

    expect(Fragment::where('source_key', 'obsidian')->count())->toBe(1);
    
    $firstFragment = Fragment::where('source_key', 'obsidian')->first();
    expect($firstFragment->title)->toBe('Test Note')
        ->and($firstFragment->metadata['obsidian_path'])->toBe('Test Note.md');

    File::delete($filePath);
    File::put($filePath, 'Updated content');
    touch($filePath, time() + 2);

    $this->artisan('obsidian:sync');

    expect(Fragment::where('source_key', 'obsidian')->count())->toBe(1);
    
    $secondFragment = Fragment::where('source_key', 'obsidian')->first();
    expect($secondFragment->id)->toBe($firstFragment->id)
        ->and($secondFragment->title)->toBe('Test Note');
});

test('creates folder tags', function () {
    File::makeDirectory($this->testVaultPath.'/Daily Notes', 0755, true);
    File::put($this->testVaultPath.'/Daily Notes/2025-01-06.md', <<<MD
Today's note
MD
    );

    $this->user->update([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->testVaultPath,
                    'sync_enabled' => true,
                ],
            ],
        ],
    ]);

    $this->artisan('obsidian:sync');

    $fragment = Fragment::where('source_key', 'obsidian')->first();

    expect($fragment->tags)->toContain('daily-notes')
        ->and($fragment->tags)->toContain('obsidian');
});

test('dry run mode does not create fragments', function () {
    File::put($this->testVaultPath.'/Test.md', 'Content');

    $this->user->update([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->testVaultPath,
                    'sync_enabled' => true,
                ],
            ],
        ],
    ]);

    $this->artisan('obsidian:sync --dry-run')
        ->assertSuccessful();

    expect(Fragment::where('source_key', 'obsidian')->count())->toBe(0);
});

test('imports multiple files', function () {
    File::put($this->testVaultPath.'/Note 1.md', 'First note');
    File::put($this->testVaultPath.'/Note 2.md', 'Second note');
    File::put($this->testVaultPath.'/Note 3.md', 'Third note');

    $this->user->update([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->testVaultPath,
                    'sync_enabled' => true,
                ],
            ],
        ],
    ]);

    $this->artisan('obsidian:sync');

    expect(Fragment::where('source_key', 'obsidian')->count())->toBe(3);
    
    $titles = Fragment::where('source_key', 'obsidian')
        ->get()
        ->map(fn($f) => $f->title)
        ->sort()
        ->values()
        ->all();

    expect($titles)->toBe(['Note 1', 'Note 2', 'Note 3']);
});

test('parses front matter tags', function () {
    File::put($this->testVaultPath.'/Tagged Note.md', <<<MD
---
tags: [work, urgent, project]
---
Content
MD
    );

    $this->user->update([
        'profile_settings' => [
            'integrations' => [
                'obsidian' => [
                    'vault_path' => $this->testVaultPath,
                    'sync_enabled' => true,
                ],
            ],
        ],
    ]);

    $this->artisan('obsidian:sync');

    $fragment = Fragment::where('source_key', 'obsidian')->first();

    expect($fragment->tags)->toContain('work')
        ->and($fragment->tags)->toContain('urgent')
        ->and($fragment->tags)->toContain('project')
        ->and($fragment->tags)->toContain('obsidian');
});
