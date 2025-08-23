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
                message: "🚪 Session ended. Normal fragment logging resumed.",
                type: 'session-end',
                fragments: [],
                shouldResetChat: true,
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
            message: $message,
            type: 'session-start',
            fragments: $session,
            shouldResetChat: true,
        );
    }

    protected function renderCurrentSession(CommandRequest $command): CommandResponse
    {
        $session = $command->arguments['__currentSession'] ?? null;

        if (!$session) {
            return new CommandResponse(
                message: "⚡ No active session.",
                type: 'system',
                shouldResetChat: true,
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
            message: $message,
            type: 'system',
            shouldResetChat: true,
        );
    }
}
