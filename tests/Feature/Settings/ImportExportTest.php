<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create([
        'profile_settings' => [
            'theme' => 'light',
            'language' => 'en',
            'notifications' => [
                'email' => true,
                'desktop' => false,
            ],
            'ai' => [
                'default_provider' => 'openai',
                'temperature' => 0.7,
            ],
        ],
    ]);

    $this->actingAs($this->user);
});

test('can import valid settings file', function () {
    $settingsData = [
        'version' => '1.0',
        'exported_at' => now()->toISOString(),
        'profile' => [
            'display_name' => 'New Display Name',
            'use_gravatar' => false,
        ],
        'settings' => [
            'theme' => 'dark',
            'language' => 'es',
            'ai' => [
                'default_provider' => 'anthropic',
                'temperature' => 1.0,
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'settings.json',
        json_encode($settingsData)
    );

    $response = $this->post('/settings/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // Verify settings were imported
    $this->user->refresh();
    expect($this->user->display_name)->toBe('New Display Name');
    expect($this->user->use_gravatar)->toBe(false);
    expect($this->user->profile_settings['theme'])->toBe('dark');
    expect($this->user->profile_settings['language'])->toBe('es');
    expect($this->user->profile_settings['ai']['default_provider'])->toBe('anthropic');
});

test('rejects non-json files', function () {
    $file = UploadedFile::fake()->create('settings.txt', 100);

    $response = $this->post('/settings/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['file']);
});

test('rejects files with malicious content', function () {
    $maliciousContent = [
        'settings' => ['theme' => 'dark'],
        'malicious' => '<?php system("rm -rf /"); ?>',
    ];

    $file = UploadedFile::fake()->createWithContent(
        'malicious.json',
        json_encode($maliciousContent)
    );

    $response = $this->post('/settings/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['file']);
});

test('rejects invalid json files', function () {
    $file = UploadedFile::fake()->createWithContent(
        'invalid.json',
        'invalid json content'
    );

    $response = $this->post('/settings/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['file']);
});

test('can generate reset token', function () {
    $response = $this->post('/settings/reset-token');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'token',
        'expires_in',
    ]);

    expect(session('settings_reset_token'))->not->toBeNull();
    expect(session('settings_reset_token_expires'))->not->toBeNull();
});

test('can reset settings with valid token', function () {
    // Generate token first
    $tokenResponse = $this->post('/settings/reset-token');
    $token = $tokenResponse->json('token');

    // Reset preferences section
    $response = $this->post('/settings/reset', [
        'sections' => ['preferences'],
        'confirmation_token' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'reset_sections' => ['preferences'],
    ]);

    // Verify settings were reset
    $this->user->refresh();
    expect($this->user->profile_settings['theme'])->toBe('system');
    expect($this->user->profile_settings['language'])->toBe('en');
});

test('rejects reset without valid token', function () {
    $response = $this->post('/settings/reset', [
        'sections' => ['preferences'],
        'confirmation_token' => 'invalid_token',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['confirmation_token']);
});

test('rejects reset with invalid sections', function () {
    $tokenResponse = $this->post('/settings/reset-token');
    $token = $tokenResponse->json('token');

    $response = $this->post('/settings/reset', [
        'sections' => ['invalid_section'],
        'confirmation_token' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sections.0']);
});

test('validates settings data structure', function () {
    $invalidSettingsData = [
        'settings' => [
            'theme' => 'invalid_theme',
            'ai' => [
                'temperature' => 5.0,
                'max_tokens' => -100,
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'invalid_settings.json',
        json_encode($invalidSettingsData)
    );

    $response = $this->post('/settings/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
});

test('preserves existing settings during partial import', function () {
    $settingsData = [
        'settings' => [
            'theme' => 'dark', // Should update
            // language not provided, should preserve existing
            'ai' => [
                'default_provider' => 'anthropic', // Should update
                // temperature not provided, should preserve existing
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'partial_update.json',
        json_encode($settingsData)
    );

    $response = $this->post('/settings/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);

    // Verify settings were merged correctly
    $this->user->refresh();
    expect($this->user->profile_settings['theme'])->toBe('dark'); // Updated
    expect($this->user->profile_settings['language'])->toBe('en'); // Preserved
    expect($this->user->profile_settings['ai']['default_provider'])->toBe('anthropic'); // Updated
    expect($this->user->profile_settings['ai']['temperature'])->toBe(0.7); // Preserved
});
