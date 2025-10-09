<?php

use App\Models\Source;
use App\Models\User;
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

test('readwise sync command runs in dry mode', function () {
    Http::fake([
        'readwise.io/api/v2/export/*' => Http::response([
            'results' => [],
            'nextPageCursor' => null,
        ]),
    ]);

    $this->artisan('readwise:sync', ['--dry-run' => true])
        ->expectsOutputToContain('Readwise Sync Summary')
        ->assertExitCode(0);
});
