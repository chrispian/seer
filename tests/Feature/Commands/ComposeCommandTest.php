<?php

namespace Tests\Feature\Commands;

use App\Actions\Commands\ComposeCommand;
use App\DTOs\CommandRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComposeCommandTest extends TestCase
{
    use RefreshDatabase;

    private ComposeCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ComposeCommand;
    }

    public function test_handle_opens_compose_panel(): void
    {
        $request = new CommandRequest('compose');
        $response = $this->command->handle($request);

        expect($response->type)->toBe('compose');
        expect($response->shouldOpenPanel)->toBeTrue();
        expect($response->panelData['action'])->toBe('open');
        expect($response->panelData['message'])->toContain('Compose Panel');
    }

    public function test_handle_includes_available_modes(): void
    {
        $request = new CommandRequest('compose');
        $response = $this->command->handle($request);

        expect($response->panelData['compose']['modes'])->toBeArray();
        expect($response->panelData['compose']['modes'])->not->toBeEmpty();

        $noteMode = collect($response->panelData['compose']['modes'])
            ->firstWhere('value', 'note');

        expect($noteMode)->not->toBeNull();
        expect($noteMode['label'])->toBe('Note');
    }

    public function test_handle_includes_templates_for_type(): void
    {
        $request = new CommandRequest('compose', ['type' => 'todo']);
        $response = $this->command->handle($request);

        expect($response->panelData['compose']['templates'])->toBeArray();
        expect($response->panelData['compose']['templates'])->not->toBeEmpty();

        $simpleTask = collect($response->panelData['compose']['templates'])
            ->firstWhere('name', 'Simple Task');

        expect($simpleTask)->not->toBeNull();
        expect($simpleTask['content'])->toContain('## Task');
    }

    public function test_handle_includes_context_data(): void
    {
        $request = new CommandRequest('compose', [
            'vault_id' => 123,
            'project_id' => 456,
        ]);
        $response = $this->command->handle($request);

        expect($response->panelData['compose']['vault_id'])->toBe(123);
        expect($response->panelData['compose']['project_id'])->toBe(456);
    }

    public function test_handle_sets_default_type_to_note(): void
    {
        $request = new CommandRequest('compose');
        $response = $this->command->handle($request);

        expect($response->panelData['compose']['type'])->toBe('note');
    }

    public function test_templates_vary_by_type(): void
    {
        $noteRequest = new CommandRequest('compose', ['type' => 'note']);
        $noteResponse = $this->command->handle($noteRequest);

        $meetingRequest = new CommandRequest('compose', ['type' => 'meeting']);
        $meetingResponse = $this->command->handle($meetingRequest);

        $noteTemplates = $noteResponse->panelData['compose']['templates'];
        $meetingTemplates = $meetingResponse->panelData['compose']['templates'];

        expect($noteTemplates)->not->toEqual($meetingTemplates);

        $meetingTemplate = collect($meetingTemplates)
            ->firstWhere('name', 'Meeting Notes');

        expect($meetingTemplate)->not->toBeNull();
        expect($meetingTemplate['content'])->toContain('## Meeting:');
    }
}
