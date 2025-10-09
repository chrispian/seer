<?php

use App\Models\Fragment;
use App\Models\User;
use App\Services\Readwise\ReadwiseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::factory()->create([
        'profile_settings' => [
            'integrations' => [
                'readwise' => [
                    'api_token' => Crypt::encryptString('demo-token'),
                    'sync_enabled' => true,
                    'last_synced_at' => null,
                    'next_cursor' => null,
                ],
            ],
        ],
    ]);
});

test('it imports highlights into fragments', function () {
    Http::fake([
        'readwise.io/api/v2/export/*' => Http::response([
            'results' => [
                [
                    'id' => 123,
                    'text' => 'Readwise highlight text',
                    'note' => 'Personal note',
                    'book_title' => 'Deep Work',
                    'book_author' => 'Cal Newport',
                    'category' => 'book',
                    'highlighted_at' => '2025-10-01T12:00:00Z',
                    'updated_at' => '2025-10-01T12:05:00Z',
                    'tags' => [
                        ['name' => 'focus'],
                        ['name' => 'productivity'],
                    ],
                    'url' => 'https://readwise.io/hl/123',
                ],
            ],
            'nextPageCursor' => null,
        ]),
    ]);

    $service = app(ReadwiseImportService::class);
    $stats = $service->import();

    expect($stats['highlights_imported'])->toBe(1)
        ->and(Fragment::count())->toBe(1);

    $fragment = Fragment::first();
    expect($fragment->source_key)->toBe('readwise')
        ->and($fragment->message)->toContain('Readwise highlight text')
        ->and($fragment->tags)->toContain('focus');
});

test('dry run completes without writing fragments', function () {
    Http::fake([
        'readwise.io/api/v2/export/*' => Http::response([
            'results' => [],
            'nextPageCursor' => null,
        ]),
    ]);

    $service = app(ReadwiseImportService::class);
    $stats = $service->import(null, null, true);

    expect($stats['dry_run'])->toBeTrue()
        ->and(Fragment::count())->toBe(0);
});
