<?php

describe('CSRF Protection', function () {
    test('setup routes require CSRF token for POST requests', function () {
        // Test setup profile endpoint rejects request without CSRF token
        $response = $this->postJson('/setup/profile', [
            'display_name' => 'Test User',
        ]);

        // Should return 419 for missing CSRF token
        $response->assertStatus(419);
    });

    test('setup routes accept requests with valid CSRF token', function () {
        // Start session to get CSRF token
        $this->get('/setup/welcome');

        // Make authenticated request with CSRF token
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/setup/profile', [
            'display_name' => 'Test User',
        ]);

        // Should succeed with valid token (or fail with validation error, not CSRF error)
        expect($response->status())->not->toBe(419);
    });

    test('setup avatar upload requires CSRF token', function () {
        // Test avatar upload endpoint rejects request without CSRF token
        $response = $this->post('/setup/avatar', [
            'use_gravatar' => 'true',
        ]);

        // Should return 419 for missing CSRF token
        $response->assertStatus(419);
    });

    test('settings routes also require CSRF protection', function () {
        // Test settings profile update endpoint rejects request without CSRF token
        $response = $this->postJson('/settings/profile', [
            'display_name' => 'Updated Name',
        ]);

        // Should return 419 for missing CSRF token
        $response->assertStatus(419);
    });
});
