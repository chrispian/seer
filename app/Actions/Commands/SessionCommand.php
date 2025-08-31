<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use Illuminate\Support\Str;

class SessionCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        if (!empty($command->arguments['identifier']) && $command->arguments['identifier'] === 'show') {
            return $this->renderCurrentSession($command);
        }

        if (!empty($command->arguments['identifier']) && $command->arguments['identifier'] === 'end') {
            return new CommandResponse(
                type: 'session-end',
                message: 'Session ended',
                shouldShowSuccessToast: true,
                fragments: [],
            );
        }

        // Otherwise: Start a new session
        $vault = $command->arguments['vault'] ?? 'default';
        $type = $command->arguments['type'] ?? 'note';
        $tags = $command->arguments['tags'] ?? [];
        $context = $command->arguments['context'] ?? null;
        $identifier = $command->arguments['identifier'] ?? 'Untitled Session';

        $session = [
            'vault' => $vault,
            'type' => $type,
            'tags' => $tags,
            'context' => $context,
            'identifier' => $identifier,
            'started_at' => now()->toISOString(),
            'session_key' => 'sess_' . Str::uuid(),
        ];

        $tagsString = !empty($session['tags']) ? implode(', ', $session['tags']) : '(no tags)';

        $message = <<<TEXT
**✅ Session Started:**
- Vault: `{$session['vault']}`
- Type: `{$session['type']}`
- Tags: `{$tagsString}`
- Identifier: `{$session['identifier']}`
- Context: `{$session['context']}`
- Started: `{$session['started_at']}`
TEXT;


        return new CommandResponse(
            type: 'session-start',
            message: 'Session started',
            shouldShowSuccessToast: true,
            fragments: $session,
        );
    }

    protected function renderCurrentSession(CommandRequest $command): CommandResponse
    {
        $session = $command->arguments['__currentSession'] ?? null;

        if (!$session) {
            return new CommandResponse(
                type: 'session',
                message: 'No active session',
                shouldShowErrorToast: true,
            );
        }

        $tagsString = !empty($session['tags']) ? implode(', ', $session['tags']) : '(no tags)';

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
}
