<?php

namespace Tests\Feature;

use App\Filament\Resources\FragmentResource\Pages\ChatInterface;
use App\Models\User;
use App\Services\ToastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToastVerbosityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_toast_verbosity_preference(): void
    {
        $user = User::factory()->create(['toast_verbosity' => ToastService::VERBOSITY_NORMAL]);

        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->call('updateToastVerbosity', ToastService::VERBOSITY_MINIMAL)
            ->assertSet('showToastSettings', false);

        $this->assertEquals(ToastService::VERBOSITY_MINIMAL, $user->fresh()->toast_verbosity);
    }

    public function test_update_toast_verbosity_ignores_invalid_values(): void
    {
        $user = User::factory()->create(['toast_verbosity' => ToastService::VERBOSITY_NORMAL]);

        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->call('updateToastVerbosity', 'invalid_value');

        $this->assertEquals(ToastService::VERBOSITY_NORMAL, $user->fresh()->toast_verbosity);
    }

    public function test_get_current_toast_verbosity_returns_user_preference(): void
    {
        $user = User::factory()->create(['toast_verbosity' => ToastService::VERBOSITY_MINIMAL]);

        $this->actingAs($user);

        $livewire = Livewire::test(ChatInterface::class);

        $this->assertEquals(ToastService::VERBOSITY_MINIMAL, $livewire->instance()->getCurrentToastVerbosity());
    }

    public function test_get_current_toast_verbosity_defaults_to_normal_for_unauthenticated(): void
    {
        $livewire = Livewire::test(ChatInterface::class);

        $this->assertEquals(ToastService::VERBOSITY_NORMAL, $livewire->instance()->getCurrentToastVerbosity());
    }

    public function test_get_toast_verbosity_options_returns_service_options(): void
    {
        $livewire = Livewire::test(ChatInterface::class);

        $options = $livewire->instance()->getToastVerbosityOptions();

        $this->assertEquals(ToastService::getVerbosityOptions(), $options);
    }

    public function test_toggle_toast_settings_changes_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(ChatInterface::class)
            ->assertSet('showToastSettings', false)
            ->call('toggleToastSettings')
            ->assertSet('showToastSettings', true)
            ->call('toggleToastSettings')
            ->assertSet('showToastSettings', false);
    }
}
