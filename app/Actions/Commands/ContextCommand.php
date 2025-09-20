<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\ChatSession;
use App\Models\Project;
use App\Models\Vault;

class ContextCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $action = $command->arguments['identifier'] ?? 'show';

        return match ($action) {
            'show' => $this->handleShow($command),
            'update' => $this->handleUpdate($command),
            default => $this->handleShow($command)
        };
    }

    private function handleShow(CommandRequest $command): CommandResponse
    {
        // Extract current context from command arguments
        $vaultId = $command->arguments['vault_id'] ?? null;
        $projectId = $command->arguments['project_id'] ?? null;
        $sessionId = $command->arguments['current_chat_session_id'] ?? null;
        $modelProvider = $command->arguments['model_provider'] ?? 'claude';
        $modelName = $command->arguments['model_name'] ?? 'claude-3-5-sonnet-20241022';

        // Load related models
        $vault = $vaultId ? Vault::find($vaultId) : Vault::getDefault();
        $project = $projectId ? Project::with('vault')->find($projectId) : null;
        $session = $sessionId ? ChatSession::with(['vault', 'project'])->find($sessionId) : null;

        $contextData = [
            'vault' => $vault ? [
                'id' => $vault->id,
                'name' => $vault->name,
                'is_default' => $vault->is_default,
                'projects_count' => $vault->projects()->count(),
            ] : null,
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'vault' => $project->vault ? [
                    'id' => $project->vault->id,
                    'name' => $project->vault->name,
                ] : null,
                'is_default' => $project->is_default,
            ] : null,
            'session' => $session ? [
                'id' => $session->id,
                'short_code' => $session->short_code,
                'custom_name' => $session->custom_name,
                'title' => $session->title,
                'channel_display' => $session->channel_display,
                'message_count' => $session->message_count,
                'is_active' => $session->is_active,
                'last_activity_at' => $session->last_activity_at?->format('M j, g:i A'),
            ] : null,
            'model' => [
                'provider' => $modelProvider,
                'name' => $modelName,
                'display_name' => $this->getModelDisplayName($modelProvider, $modelName),
            ],
        ];

        $message = $this->buildContextMessage($contextData);

        return new CommandResponse(
            type: 'context',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'show',
                'message' => $message,
                'context' => $contextData,
            ],
        );
    }

    private function handleUpdate(CommandRequest $command): CommandResponse
    {
        $updates = [];
        $vaultId = $command->arguments['vault_id'] ?? null;
        $projectId = $command->arguments['project_id'] ?? null;
        $sessionId = $command->arguments['session_id'] ?? null;

        if ($vaultId) {
            $vault = Vault::find($vaultId);
            if ($vault) {
                $updates['vault'] = $vault->name;
            }
        }

        if ($projectId) {
            $project = Project::find($projectId);
            if ($project) {
                $updates['project'] = $project->name;
            }
        }

        if ($sessionId) {
            $session = ChatSession::find($sessionId);
            if ($session) {
                $updates['session'] = $session->channel_display;
            }
        }

        if (empty($updates)) {
            return new CommandResponse(
                type: 'context',
                shouldShowErrorToast: true,
                message: 'No valid context updates provided. Use `/context update` with vault_id, project_id, or session_id.',
            );
        }

        $updateList = implode(', ', array_map(fn ($key, $value) => "{$key}: {$value}", array_keys($updates), $updates));

        return new CommandResponse(
            type: 'context-update',
            shouldShowSuccessToast: true,
            message: "⚙️ Context updated: {$updateList}",
            data: [
                'vault_id' => $vaultId,
                'project_id' => $projectId,
                'session_id' => $sessionId,
            ],
        );
    }

    private function buildContextMessage(array $context): string
    {
        $lines = ['⚙️ **Current Context:**'];

        if ($context['vault']) {
            $vault = $context['vault'];
            $defaultText = $vault['is_default'] ? ' (default)' : '';
            $lines[] = "- **Vault:** {$vault['name']}{$defaultText} ({$vault['projects_count']} projects)";
        } else {
            $lines[] = '- **Vault:** _Not set_';
        }

        if ($context['project']) {
            $project = $context['project'];
            $defaultText = $project['is_default'] ? ' (default)' : '';
            $vaultText = $project['vault'] ? " in {$project['vault']['name']}" : '';
            $lines[] = "- **Project:** {$project['name']}{$defaultText}{$vaultText}";
        } else {
            $lines[] = '- **Project:** _Not set_';
        }

        if ($context['session']) {
            $session = $context['session'];
            $statusText = $session['is_active'] ? 'active' : 'inactive';
            $activityText = $session['last_activity_at'] ? ", last: {$session['last_activity_at']}" : '';
            $lines[] = "- **Session:** {$session['channel_display']} ({$session['message_count']} messages, {$statusText}{$activityText})";
        } else {
            $lines[] = '- **Session:** _Not set_';
        }

        if ($context['model']) {
            $model = $context['model'];
            $lines[] = "- **Model:** {$model['display_name']}";
        }

        return implode("\n", $lines);
    }

    private function getModelDisplayName(string $provider, string $name): string
    {
        return match ($provider) {
            'claude' => match ($name) {
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku',
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                default => "Claude ({$name})"
            },
            'openai' => match ($name) {
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                default => "OpenAI ({$name})"
            },
            default => "{$provider} ({$name})"
        };
    }
}
