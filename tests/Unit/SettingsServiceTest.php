<?php

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->service = new SettingsService;
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
});

test('validates valid settings data', function () {
    $validData = [
        'version' => '1.0',
        'profile' => [
            'display_name' => 'Test User',
            'use_gravatar' => true,
        ],
        'settings' => [
            'theme' => 'dark',
            'language' => 'es',
            'notifications' => [
                'email' => false,
                'desktop' => true,
            ],
            'ai' => [
                'default_provider' => 'anthropic',
                'temperature' => 1.0,
                'max_tokens' => 2000,
            ],
        ],
    ];

    // Should not throw an exception
    $this->service->validateSettingsData($validData);
    expect(true)->toBeTrue(); // Test passes if no exception thrown
});

test('rejects invalid theme values', function () {
    $invalidData = [
        'settings' => [
            'theme' => 'invalid_theme',
        ],
    ];

    expect(fn () => $this->service->validateSettingsData($invalidData))
        ->toThrow(\InvalidArgumentException::class);
});

test('rejects invalid temperature range', function () {
    $invalidData = [
        'settings' => [
            'ai' => [
                'temperature' => 5.0, // Out of 0-2 range
            ],
        ],
    ];

    expect(fn () => $this->service->validateSettingsData($invalidData))
        ->toThrow(\InvalidArgumentException::class);
});

test('provides correct default settings', function () {
    $defaults = $this->service->getDefaultSettings();

    expect($defaults)->toBeArray();
    expect($defaults)->toHaveKey('preferences');
    expect($defaults)->toHaveKey('notifications');
    expect($defaults)->toHaveKey('layout');
    expect($defaults)->toHaveKey('ai');

    // Check specific default values
    expect($defaults['preferences']['theme'])->toBe('system');
    expect($defaults['preferences']['language'])->toBe('en');
    expect($defaults['notifications']['email'])->toBeTrue();
    expect($defaults['notifications']['sound'])->toBeFalse();
    expect($defaults['ai']['temperature'])->toBe(0.7);
});

test('imports profile data safely', function () {
    $importData = [
        'profile' => [
            'display_name' => 'New Name',
            'use_gravatar' => false,
        ],
        'settings' => [
            'theme' => 'dark',
        ],
    ];

    Log::shouldReceive('info')->once();
    Log::shouldReceive('channel')->andReturnSelf();

    $changes = $this->service->importSettings($this->user, $importData);

    $this->user->refresh();
    expect($this->user->display_name)->toBe('New Name');
    expect($this->user->use_gravatar)->toBe(false);
    expect($this->user->profile_settings['theme'])->toBe('dark');

    // Verify changes were tracked
    expect($changes)->toHaveKey('profile');
    expect($changes)->toHaveKey('theme');
});

test('merges settings without overwriting entire structure', function () {
    $importData = [
        'settings' => [
            'theme' => 'dark',
            'ai' => [
                'default_provider' => 'anthropic',
            ],
        ],
    ];

    Log::shouldReceive('info')->once();
    Log::shouldReceive('channel')->andReturnSelf();

    $this->service->importSettings($this->user, $importData);

    $this->user->refresh();
    $settings = $this->user->profile_settings;

    // Updated values
    expect($settings['theme'])->toBe('dark');
    expect($settings['ai']['default_provider'])->toBe('anthropic');

    // Preserved values
    expect($settings['language'])->toBe('en');
    expect($settings['ai']['temperature'])->toBe(0.7);
    expect($settings['notifications']['email'])->toBeTrue();
});

test('resets specific sections', function () {
    Log::shouldReceive('info')->once();
    Log::shouldReceive('channel')->andReturnSelf();

    $resetSections = $this->service->resetSettings($this->user, ['preferences', 'ai']);

    $this->user->refresh();
    $settings = $this->user->profile_settings;

    // Reset sections should have default values
    expect($settings['theme'])->toBe('system');
    expect($settings['language'])->toBe('en');
    expect($settings['ai']['default_provider'])->toBe('');
    expect($settings['ai']['temperature'])->toBe(0.7);

    // Non-reset sections should be preserved
    expect($settings['notifications']['email'])->toBeTrue();
    expect($settings['notifications']['desktop'])->toBeFalse();

    // Verify reset sections were tracked
    expect($resetSections)->toContain('preferences');
    expect($resetSections)->toContain('ai');
    expect($resetSections)->not->toContain('notifications');
});

test('handles empty import data', function () {
    Log::shouldReceive('info')->once();
    Log::shouldReceive('channel')->andReturnSelf();

    $changes = $this->service->importSettings($this->user, []);

    expect($changes)->toBeEmpty();

    // Settings should remain unchanged
    $this->user->refresh();
    expect($this->user->profile_settings['theme'])->toBe('light');
});

test('handles null values in import data', function () {
    $importData = [
        'settings' => [
            'theme' => null, // Should be ignored
            'language' => 'es', // Should be imported
            'ai' => [
                'default_provider' => null, // Should be ignored
                'temperature' => 1.0, // Should be imported
            ],
        ],
    ];

    Log::shouldReceive('info')->once();
    Log::shouldReceive('channel')->andReturnSelf();

    $this->service->importSettings($this->user, $importData);

    $this->user->refresh();
    $settings = $this->user->profile_settings;

    // Null values should be ignored, existing values preserved
    expect($settings['theme'])->toBe('light'); // Preserved
    expect($settings['language'])->toBe('es'); // Updated
    expect($settings['ai']['default_provider'])->toBe('openai'); // Preserved
    expect($settings['ai']['temperature'])->toBe(1.0); // Updated
});
