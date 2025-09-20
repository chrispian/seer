<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ToastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ToastServiceTest extends TestCase
{
    use RefreshDatabase;

    private ToastService $toastService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->toastService = app(ToastService::class);
    }

    public function test_should_show_toast_respects_minimal_verbosity_setting(): void
    {
        $user = User::factory()->create(['toast_verbosity' => ToastService::VERBOSITY_MINIMAL]);

        // Should show errors and warnings
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_ERROR, $user));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_WARNING, $user));

        // Should not show success and info
        $this->assertFalse($this->toastService->shouldShowToast(ToastService::SEVERITY_SUCCESS, $user));
        $this->assertFalse($this->toastService->shouldShowToast(ToastService::SEVERITY_INFO, $user));
    }

    public function test_should_show_toast_shows_all_toasts_for_normal_verbosity(): void
    {
        $user = User::factory()->create(['toast_verbosity' => ToastService::VERBOSITY_NORMAL]);

        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_ERROR, $user));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_WARNING, $user));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_SUCCESS, $user));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_INFO, $user));
    }

    public function test_should_show_toast_shows_all_toasts_for_verbose_verbosity(): void
    {
        $user = User::factory()->create(['toast_verbosity' => ToastService::VERBOSITY_VERBOSE]);

        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_ERROR, $user));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_WARNING, $user));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_SUCCESS, $user));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_INFO, $user));
    }

    public function test_should_show_toast_defaults_to_true_for_unauthenticated_users(): void
    {
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_SUCCESS, null));
        $this->assertTrue($this->toastService->shouldShowToast(ToastService::SEVERITY_ERROR, null));
    }

    public function test_is_duplicate_suppresses_success_toasts_within_window(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $message = 'Test success message';

        // First call should not be duplicate
        $this->assertFalse($this->toastService->isDuplicate(ToastService::SEVERITY_SUCCESS, $message, $user));

        // Second call with same message should be duplicate
        $this->assertTrue($this->toastService->isDuplicate(ToastService::SEVERITY_SUCCESS, $message, $user));
    }

    public function test_is_duplicate_does_not_suppress_non_success_toasts(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $message = 'Test error message';

        // Error toasts should never be duplicates
        $this->assertFalse($this->toastService->isDuplicate(ToastService::SEVERITY_ERROR, $message, $user));
        $this->assertFalse($this->toastService->isDuplicate(ToastService::SEVERITY_ERROR, $message, $user));
    }

    public function test_is_duplicate_works_with_different_messages(): void
    {
        Cache::flush();

        $user = User::factory()->create();

        $this->assertFalse($this->toastService->isDuplicate(ToastService::SEVERITY_SUCCESS, 'Message 1', $user));
        $this->assertFalse($this->toastService->isDuplicate(ToastService::SEVERITY_SUCCESS, 'Message 2', $user));

        // Same messages should be duplicates
        $this->assertTrue($this->toastService->isDuplicate(ToastService::SEVERITY_SUCCESS, 'Message 1', $user));
        $this->assertTrue($this->toastService->isDuplicate(ToastService::SEVERITY_SUCCESS, 'Message 2', $user));
    }

    public function test_get_verbosity_options_returns_correct_options(): void
    {
        $options = ToastService::getVerbosityOptions();

        $this->assertArrayHasKey(ToastService::VERBOSITY_MINIMAL, $options);
        $this->assertArrayHasKey(ToastService::VERBOSITY_NORMAL, $options);
        $this->assertArrayHasKey(ToastService::VERBOSITY_VERBOSE, $options);

        $this->assertStringContainsString('Minimal', $options[ToastService::VERBOSITY_MINIMAL]);
        $this->assertStringContainsString('Normal', $options[ToastService::VERBOSITY_NORMAL]);
        $this->assertStringContainsString('Verbose', $options[ToastService::VERBOSITY_VERBOSE]);
    }
}
