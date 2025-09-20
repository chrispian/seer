<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\ChatSession;
use App\Models\Project;
use App\Models\Vault;

class SessionCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $action = $command->arguments['identifier'] ?? 'list';

        return match ($action) {
            'show' => $this->renderCurrentSession($command),
            'end' => $this->handleEnd($command),
            'list' => $this->handleList($command),
            'start' => $this->handleStart($command),
            default => $this->handleList($command)
        };
    }

    protected function renderCurrentSession(CommandRequest $command): CommandResponse
    {
        $session = $command->arguments['__currentSession'] ?? null;

        if (! $session) {
            return new CommandResponse(
                type: 'session',
                message: 'No active session',
                shouldShowErrorToast: true,
            );
        }

        $tagsString = ! empty($session['tags']) ? implode(', ', $session['tags']) : '(no tags)';

        $message = <<<TEXT
**Session Active:**
- Vault: `{$session['vault']}`
- Type: `{$session['type']}`
- Tags: `{$tagsString}`
- Identifier: `{$session['identifier']}`
- Context: `{$session['context']}`
- Started: `{$session['started_at']}`
TEXT;

        return new CommandResponse(
            type: 'session',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'show',
                'message' => $message,
                'session' => $session,
            ],
        );
    }

    private function handleList(CommandRequest $command): CommandResponse
    {
        $vaultId = $command->arguments['vault_id'] ?? null;
        $projectId = $command->arguments['project_id'] ?? null;
        $limit = (int) ($command->arguments['limit'] ?? 10);

        $query = ChatSession::recent($limit)->with(['vault', 'project']);

        if ($vaultId) {
            $query->forVault($vaultId);
        }

        if ($projectId) {
            $query->forProject($projectId);
        }

        $sessions = $query->get();

        if ($sessions->isEmpty()) {
            return new CommandResponse(
                type: 'session',
                shouldOpenPanel: true,
                panelData: [
                    'action' => 'list',
                    'message' => '💬 No chat sessions found. Start one with `/session start`',
                    'sessions' => [],
                ],
            );
        }

        $sessionData = $sessions->map(function (ChatSession $session) {
            return [
                'id' => $session->id,
                'short_code' => $session->short_code,
                'custom_name' => $session->custom_name,
                'title' => $session->title,
                'display_title' => $session->display_title,
                'channel_display' => $session->channel_display,
                'message_count' => $session->message_count,
                'last_activity_at' => $session->last_activity_at?->format('M j, g:i A'),
                'vault' => $session->vault ? [
                    'id' => $session->vault->id,
                    'name' => $session->vault->name,
                ] : null,
                'project' => $session->project ? [
                    'id' => $session->project->id,
                    'name' => $session->project->name,
                ] : null,
                'is_pinned' => $session->is_pinned,
            ];
        })->all();

        return new CommandResponse(
            type: 'session',
            shouldOpenPanel: true,
            panelData: [
                'action' => 'list',
                'message' => '💬 Found **'.count($sessionData).'** recent session'.((count($sessionData) !== 1) ? 's' : ''),
                'sessions' => $sessionData,
            ],
        );
    }

    private function handleStart(CommandRequest $command): CommandResponse
    {
        $vaultId = $command->arguments['vault_id'] ?? null;
        $projectId = $command->arguments['project_id'] ?? null;
        $title = $command->arguments['title'] ?? null;
        $customName = $command->arguments['custom_name'] ?? null;

        // Determine vault and project
        $vault = $vaultId ? Vault::find($vaultId) : Vault::getDefault();
        $project = null;

        if ($projectId && $vault) {
            $project = $vault->projects()->find($projectId);
        } elseif ($vault && ! $projectId) {
            $project = Project::getDefaultForVault($vault->id);
        }

        if (! $vault) {
            return new CommandResponse(
                type: 'session',
                shouldShowErrorToast: true,
                message: 'No vault found. Please create a vault first or specify a valid vault ID.',
            );
        }

        $session = ChatSession::create([
            'vault_id' => $vault->id,
            'project_id' => $project?->id,
            'title' => $title,
            'custom_name' => $customName,
            'is_active' => true,
            'last_activity_at' => now(),
        ]);

        return new CommandResponse(
            type: 'session-start',
            shouldShowSuccessToast: true,
            message: "💬 Started session: {$session->channel_display}",
            data: [
                'session_id' => $session->id,
                'short_code' => $session->short_code,
                'vault_id' => $vault->id,
                'project_id' => $project?->id,
            ],
            toastData: [
                'title' => 'Session Started',
                'message' => $session->channel_display,
                'sessionId' => $session->id,
            ],
        );
    }

    private function handleEnd(CommandRequest $command): CommandResponse
    {
        $sessionId = $command->arguments['session_id'] ?? $command->arguments['current_chat_session_id'] ?? null;

        if (! $sessionId) {
            return new CommandResponse(
                type: 'session',
                shouldShowErrorToast: true,
                message: 'No active session to end.',
            );
        }

        $session = ChatSession::find($sessionId);

        if (! $session) {
            return new CommandResponse(
                type: 'session',
                shouldShowErrorToast: true,
                message: 'Session not found.',
            );
        }

        $session->update(['is_active' => false]);

        return new CommandResponse(
            type: 'session-end',
            shouldShowSuccessToast: true,
            message: "💬 Ended session: {$session->channel_display}",
            data: [
                'session_id' => $session->id,
            ],
        );
    }
}
