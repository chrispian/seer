<?php

namespace Tests\Feature\Commands;

use App\Actions\Commands\ContextCommand;
use App\DTOs\CommandRequest;
use App\Models\ChatSession;
use App\Models\Project;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextCommandTest extends TestCase
{
    use RefreshDatabase;

    private ContextCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ContextCommand;
    }

    public function test_show_displays_current_context(): void
    {
        $vault = Vault::factory()->create(['name' => 'Test Vault', 'is_default' => true]);
        $project = Project::factory()->create(['vault_id' => $vault->id, 'name' => 'Test Project']);
        $session = ChatSession::factory()->create([
            'vault_id' => $vault->id,
            'project_id' => $project->id,
            'title' => 'Test Session',
        ]);

        $request = new CommandRequest('context', [
            'identifier' => 'show',
            'vault_id' => $vault->id,
            'project_id' => $project->id,
            'current_chat_session_id' => $session->id,
        ]);

        $response = $this->command->handle($request);

        expect($response->type)->toBe('context');
        expect($response->shouldOpenPanel)->toBeTrue();
        expect($response->panelData['context']['vault']['name'])->toBe('Test Vault');
        expect($response->panelData['context']['project']['name'])->toBe('Test Project');
        expect($response->panelData['context']['session']['title'])->toBe('Test Session');
    }

    public function test_show_handles_missing_context(): void
    {
        $request = new CommandRequest('context', ['identifier' => 'show']);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('context');
        expect($response->shouldOpenPanel)->toBeTrue();
        expect($response->panelData['message'])->toContain('Current Context');
    }

    public function test_update_with_no_parameters_shows_error(): void
    {
        $request = new CommandRequest('context', ['identifier' => 'update']);
        $response = $this->command->handle($request);

        expect($response->shouldShowErrorToast)->toBeTrue();
        expect($response->message)->toContain('No valid context updates provided');
    }

    public function test_update_with_valid_vault_id(): void
    {
        $vault = Vault::factory()->create(['name' => 'Test Vault']);

        $request = new CommandRequest('context', [
            'identifier' => 'update',
            'vault_id' => $vault->id,
        ]);

        $response = $this->command->handle($request);

        expect($response->type)->toBe('context-update');
        expect($response->shouldShowSuccessToast)->toBeTrue();
        expect($response->message)->toContain('Context updated');
        expect($response->data['vault_id'])->toBe($vault->id);
    }
}
