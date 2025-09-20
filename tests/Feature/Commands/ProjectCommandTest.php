<?php

namespace Tests\Feature\Commands;

use App\Actions\Commands\ProjectCommand;
use App\DTOs\CommandRequest;
use App\Models\Project;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCommandTest extends TestCase
{
    use RefreshDatabase;

    private ProjectCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ProjectCommand;
    }

    public function test_list_shows_empty_message_when_no_projects(): void
    {
        $vault = Vault::factory()->create(['is_default' => true]);

        $request = new CommandRequest('project', ['identifier' => 'list', 'vault_id' => $vault->id]);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('project');
        expect($response->shouldOpenPanel)->toBeTrue();
        expect($response->panelData['message'])->toContain('No projects found');
    }

    public function test_list_shows_projects_when_they_exist(): void
    {
        $vault = Vault::factory()->create(['is_default' => true]);
        $project1 = Project::factory()->create(['vault_id' => $vault->id, 'name' => 'Project 1']);
        $project2 = Project::factory()->create(['vault_id' => $vault->id, 'name' => 'Project 2']);

        $request = new CommandRequest('project', ['identifier' => 'list', 'vault_id' => $vault->id]);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('project');
        expect($response->shouldOpenPanel)->toBeTrue();
        expect($response->panelData['message'])->toContain('Found **2** projects');
        expect($response->panelData['projects'])->toHaveCount(2);
    }

    public function test_create_requires_name(): void
    {
        $request = new CommandRequest('project', ['identifier' => 'create']);
        $response = $this->command->handle($request);

        expect($response->shouldShowErrorToast)->toBeTrue();
        expect($response->message)->toContain('provide a project name');
    }

    public function test_create_successfully_creates_project(): void
    {
        $vault = Vault::factory()->create(['is_default' => true]);

        $request = new CommandRequest('project', ['identifier' => 'create', 'name' => 'My New Project', 'vault_id' => $vault->id]);
        $response = $this->command->handle($request);

        expect($response->shouldShowSuccessToast)->toBeTrue();
        expect($response->message)->toContain('Created project: My New Project');
        $this->assertDatabaseHas('projects', ['name' => 'My New Project', 'vault_id' => $vault->id]);
    }

    public function test_switch_finds_project_by_name(): void
    {
        $vault = Vault::factory()->create(['is_default' => true]);
        $project = Project::factory()->create(['vault_id' => $vault->id, 'name' => 'Test Project']);

        $request = new CommandRequest('project', ['identifier' => 'switch', 'name' => 'Test Project', 'vault_id' => $vault->id]);
        $response = $this->command->handle($request);

        expect($response->type)->toBe('project-switch');
        expect($response->shouldShowSuccessToast)->toBeTrue();
        expect($response->message)->toContain('Switched to project: Test Project');
        expect($response->data['project_id'])->toBe($project->id);
    }
}
