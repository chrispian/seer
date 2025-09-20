<?php

namespace Tests\Feature\Commands;

use App\Actions\Commands\VaultCommand;
use App\DTOs\CommandRequest;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VaultCommandTest extends TestCase
{
    use RefreshDatabase;

    private VaultCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new VaultCommand;
    }

    public function test_list_shows_empty_message_when_no_vaults(): void
    {
        // Ensure database is truly empty
        Vault::query()->delete();

        $request = new CommandRequest('vault', ['identifier' => 'list']);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('vault');
        expect($response->shouldOpenPanel)->toBeTrue();
        expect($response->panelData['message'])->toContain('No vaults found');
    }

    public function test_list_shows_vaults_when_they_exist(): void
    {
        // Ensure clean slate
        Vault::query()->delete();

        $vault1 = Vault::factory()->create(['name' => 'Test Vault 1']);
        $vault2 = Vault::factory()->create(['name' => 'Test Vault 2']);

        $request = new CommandRequest('vault', ['identifier' => 'list']);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('vault');
        expect($response->shouldOpenPanel)->toBeTrue();
        expect($response->panelData['message'])->toContain('Found **2** vaults');
        expect($response->panelData['vaults'])->toHaveCount(2);
    }

    public function test_create_requires_name(): void
    {
        $request = new CommandRequest('vault', ['identifier' => 'create']);
        $response = $this->command->handle($request);

        expect($response->shouldShowErrorToast)->toBeTrue();
        expect($response->message)->toContain('provide a vault name');
    }

    public function test_create_successfully_creates_vault(): void
    {
        $request = new CommandRequest('vault', ['identifier' => 'create', 'name' => 'My New Vault']);
        $response = $this->command->handle($request);

        expect($response->shouldShowSuccessToast)->toBeTrue();
        expect($response->message)->toContain('Created vault: My New Vault');
        $this->assertDatabaseHas('vaults', ['name' => 'My New Vault']);
    }

    public function test_create_prevents_duplicate_names(): void
    {
        Vault::factory()->create(['name' => 'Existing Vault']);

        $request = new CommandRequest('vault', ['identifier' => 'create', 'name' => 'Existing Vault']);
        $response = $this->command->handle($request);

        expect($response->shouldShowErrorToast)->toBeTrue();
        expect($response->message)->toContain('already exists');
    }

    public function test_switch_requires_vault_name(): void
    {
        $request = new CommandRequest('vault', ['identifier' => 'switch']);
        $response = $this->command->handle($request);

        expect($response->shouldShowErrorToast)->toBeTrue();
        expect($response->message)->toContain('provide a vault name');
    }

    public function test_switch_finds_vault_by_name(): void
    {
        $vault = Vault::factory()->create(['name' => 'Test Vault']);

        $request = new CommandRequest('vault', ['identifier' => 'switch', 'name' => 'Test Vault']);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('vault-switch');
        expect($response->shouldShowSuccessToast)->toBeTrue();
        expect($response->message)->toContain('Switched to vault: Test Vault');
        expect($response->data['vault_id'])->toBe($vault->id);
    }

    public function test_switch_handles_partial_name_match(): void
    {
        $vault = Vault::factory()->create(['name' => 'My Test Vault']);

        $request = new CommandRequest('vault', ['identifier' => 'switch', 'name' => 'Test']);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('vault-switch');
        expect($response->shouldShowSuccessToast)->toBeTrue();
        expect($response->data['vault_id'])->toBe($vault->id);
    }

    public function test_switch_handles_vault_not_found(): void
    {
        $request = new CommandRequest('vault', ['identifier' => 'switch', 'name' => 'NonExistent']);
        $response = $this->command->handle($request);

        expect($response->shouldShowErrorToast)->toBeTrue();
        expect($response->message)->toContain('not found');
    }
}
