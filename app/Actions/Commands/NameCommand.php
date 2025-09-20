<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\ChatSession;

class NameCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $customName = trim($command->arguments['identifier'] ?? '');

        // Get current chat session ID from the command context
        $currentChatSessionId = $command->arguments['current_chat_session_id'] ?? null;

        if (! $currentChatSessionId) {
            return new CommandResponse(
                type: 'name-error',
                shouldShowErrorToast: true,
                message: 'No active chat session to rename. Start a conversation first.'
            );
        }

        if (empty($customName)) {
            return $this->showNameHelp();
        }

        // Handle special case: if name starts with #, remove it
        if (str_starts_with($customName, '#')) {
            $customName = substr($customName, 1);
        }

        // Validate custom name
        if (strlen($customName) < 2 || strlen($customName) > 50) {
            return new CommandResponse(
                type: 'name-error',
                shouldShowErrorToast: true,
                message: 'Channel name must be between 2 and 50 characters.'
            );
        }

        // Find and update the chat session
        $chatSession = ChatSession::find($currentChatSessionId);
        if (! $chatSession) {
            return new CommandResponse(
                type: 'name-error',
                shouldShowErrorToast: true,
                message: 'Current chat session not found.'
            );
        }

        $oldName = $chatSession->custom_name ?: $chatSession->title;
        $chatSession->update(['custom_name' => $customName]);

        return new CommandResponse(
            type: 'name-success',
            shouldShowSuccessToast: true,
            toastData: [
                'title' => 'Channel Renamed',
                'message' => "Changed from '{$oldName}' to '#{$chatSession->short_code} {$customName}'",
                'fragmentType' => 'chat',
                'fragmentId' => null,
            ]
        );
    }

    private function showNameHelp(): CommandResponse
    {
        $message = <<<'MARKDOWN'
# Set Channel Name

Usage:
- `/name My Project Chat` – Set custom name for current channel
- `/name #ProjectAlpha` – Set name (# is optional)

The current channel will be renamed and the new name will appear in the sidebar.

**Current naming:**
- Short code: Always preserved (e.g., `#c5`)
- Custom name: Replaces the auto-generated title
- Display format: `#c5 Your Custom Name`
MARKDOWN;

        return new CommandResponse(
            type: 'help',
            shouldOpenPanel: true,
            panelData: [
                'message' => $message,
            ],
        );
    }
}
