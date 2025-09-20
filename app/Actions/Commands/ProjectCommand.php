<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Project;
use App\Models\Vault;

class ProjectCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $action = $command->arguments['identifier'] ?? 'list';

        return match ($action) {
            'list' => $this->handleList($command),
            'create' => $this->handleCreate($command),
            'switch' => $this->handleSwitch($command),
            default => $this->handleSwitch($command, $action)
        };
    }

    private function handleList(CommandRequest $command): CommandResponse
    {
        $vaultId = $command->arguments['vault_id'] ?? null;
        $vaultName = $command->arguments['vault'] ?? null;

        // Try to determine vault context
        $vault = null;
        if ($vaultId) {
            $vault = Vault::find($vaultId);
        } elseif ($vaultName) {
            $vault = Vault::where('name', 'LIKE', "%{$vaultName}%")->first();
        } else {
            $vault = Vault::getDefault();
        }

        if (! $vault) {
            return new CommandResponse(
                type: 'project',
                shouldShowErrorToast: true,
                message: 'No vault context found. Please specify a vault or switch to one first.',
            );
        }

        $projects = $vault->projects()->ordered()->get();

        if ($projects->isEmpty()) {
            return new CommandResponse(
                type: 'project',
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'list',
                    'message' => "📂 No projects found in vault '{$vault->name}'. Create one with `/project create \"Project Name\"`",
                    'projects' => [],
                    'vault' => [
                        'id' => $vault->id,
                        'name' => $vault->name,
                    ],
                ],
            );
        }

        $projectData = $projects->map(function (Project $project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'is_default' => $project->is_default,
                'fragments_count' => $project->fragments()->count(),
                'sessions_count' => $project->chatSessions()->count(),
            ];
        })->all();

        return new CommandResponse(
            type: 'project',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'list',
                'message' => '📂 Found **'.count($projectData).'** project'.((count($projectData) !== 1) ? 's' : '')." in vault '{$vault->name}'",
                'projects' => $projectData,
                'vault' => [
                    'id' => $vault->id,
                    'name' => $vault->name,
                ],
            ],
        );
    }

    private function handleCreate(CommandRequest $command): CommandResponse
    {
        $name = $command->arguments['name'] ?? null;
        $vaultId = $command->arguments['vault_id'] ?? null;

        if (empty($name)) {
            return new CommandResponse(
                type: 'project',
                shouldShowErrorToast: true,
                message: 'Please provide a project name. Example: `/project create "My Project"`',
            );
        }

        // Determine vault
        $vault = $vaultId ? Vault::find($vaultId) : Vault::getDefault();

        if (! $vault) {
            return new CommandResponse(
                type: 'project',
                shouldShowErrorToast: true,
                message: 'No vault found. Please create a vault first or specify a valid vault ID.',
            );
        }

        // Check if project already exists in this vault
        if ($vault->projects()->where('name', $name)->exists()) {
            return new CommandResponse(
                type: 'project',
                shouldShowErrorToast: true,
                message: "Project '{$name}' already exists in vault '{$vault->name}'.",
            );
        }

        $project = Project::create([
            'vault_id' => $vault->id,
            'name' => $name,
            'description' => $command->arguments['description'] ?? null,
            'is_default' => $vault->projects()->count() === 0, // First project becomes default
            'sort_order' => $vault->projects()->max('sort_order') + 1,
        ]);

        return new CommandResponse(
            type: 'project',
            shouldShowSuccessToast: true,
            message: "✅ Created project: {$project->name} in vault: {$vault->name}",
            toastData: [
                'title' => 'Project Created',
                'message' => $project->name,
                'projectId' => $project->id,
                'vaultId' => $vault->id,
            ],
        );
    }

    private function handleSwitch(CommandRequest $command, ?string $identifier = null): CommandResponse
    {
        $projectName = $identifier ?? $command->arguments['name'] ?? null;
        $vaultId = $command->arguments['vault_id'] ?? null;

        if (empty($projectName)) {
            return new CommandResponse(
                type: 'project',
                shouldShowErrorToast: true,
                message: 'Please provide a project name or ID to switch to. Example: `/project "My Project"`',
            );
        }

        // Determine vault context
        $vault = $vaultId ? Vault::find($vaultId) : Vault::getDefault();

        if (! $vault) {
            return new CommandResponse(
                type: 'project',
                shouldShowErrorToast: true,
                message: 'No vault context found. Please specify a vault or switch to one first.',
            );
        }

        // Try to find project by name or ID within the vault
        $project = is_numeric($projectName)
            ? $vault->projects()->find((int) $projectName)
            : $vault->projects()->where('name', 'LIKE', "%{$projectName}%")->first();

        if (! $project) {
            // Get available projects for suggestion
            $availableProjects = $vault->projects()->ordered()->take(5)->pluck('name')->join(', ');
            $suggestion = $availableProjects ? " Available projects in '{$vault->name}': {$availableProjects}" : " Use `/project list` to see available projects in '{$vault->name}'.";

            return new CommandResponse(
                type: 'project',
                shouldShowErrorToast: true,
                message: "Project '{$projectName}' not found in vault '{$vault->name}'.{$suggestion}",
            );
        }

        return new CommandResponse(
            type: 'project-switch',
            shouldShowSuccessToast: true,
            message: "📂 Switched to project: {$project->name} (in {$vault->name})",
            data: [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'vault_id' => $vault->id,
                'vault_name' => $vault->name,
            ],
            toastData: [
                'title' => 'Project Switched',
                'message' => $project->name,
                'projectId' => $project->id,
                'vaultId' => $vault->id,
            ],
        );
    }
}
