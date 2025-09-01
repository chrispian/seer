<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\ChatSession;

class JoinCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $identifier = trim($command->arguments['identifier'] ?? '');
        
        // Get vault/project context from command
        $vaultId = $command->arguments['vault_id'] ?? null;
        $projectId = $command->arguments['project_id'] ?? null;
        
        // If no identifier provided, show autocomplete help
        if (empty($identifier)) {
            return $this->showAutocompleteHelp();
        }
        
        // Handle autocomplete request (/join #)
        if ($identifier === '#') {
            return $this->showAutocompleteResults('', null, $vaultId, $projectId);
        }
        
        // Handle channel navigation (/join #c5 or /join #custom)
        if (str_starts_with($identifier, '#')) {
            $channelIdentifier = substr($identifier, 1); // Remove the #
            return $this->joinByChannelIdentifier($channelIdentifier);
        }
        
        // Handle search query (/join bug)
        return $this->searchAndJoin($identifier, $vaultId, $projectId);
    }
    
    private function joinByChannelIdentifier(string $identifier): CommandResponse
    {
        $chatSession = null;
        
        // First, try to find by short code (e.g., "c5" from "#c5")
        if (preg_match('/^c\d+$/', $identifier)) {
            $chatSession = ChatSession::findByShortCode($identifier);
        }
        
        // If not found by short code, try to find by custom name (e.g., "custom" from "#custom")
        if (!$chatSession) {
            $chatSession = ChatSession::findByCustomName($identifier);
        }
        
        if (!$chatSession) {
            return new CommandResponse(
                type: 'join-error',
                shouldShowErrorToast: true,
                message: "Channel #{$identifier} not found. Try /join # to see available channels or /channels to list all."
            );
        }
        
        // Return success response that will trigger chat switching
        return new CommandResponse(
            type: 'join-success',
            shouldOpenPanel: false,
            shouldShowSuccessToast: true,
            toastData: [
                'title' => 'Channel Joined',
                'message' => "Switched to {$chatSession->channel_display}",
                'fragmentType' => 'chat',
                'fragmentId' => null
            ],
            data: [
                'action' => 'switch_chat',
                'chat_session_id' => $chatSession->id,
                'channel_name' => $chatSession->channel_display
            ]
        );
    }
    
    private function searchAndJoin(string $query, ?int $vaultId = null, ?int $projectId = null): CommandResponse
    {
        $results = ChatSession::searchForAutocomplete($query, $vaultId, $projectId, 5);
        
        if ($results->isEmpty()) {
            return new CommandResponse(
                type: 'join-error',
                shouldShowErrorToast: true,
                message: "No channels found matching '{$query}'. Try /join # to see all channels."
            );
        }
        
        if ($results->count() === 1) {
            // Single result - join directly
            $chatSession = $results->first();
            return new CommandResponse(
                type: 'join-success',
                shouldOpenPanel: false,
                shouldShowSuccessToast: true,
                toastData: [
                    'title' => 'Channel Joined',
                    'message' => "Switched to {$chatSession->channel_display}",
                    'fragmentType' => 'chat',
                    'fragmentId' => null
                ],
                data: [
                    'action' => 'switch_chat',
                    'chat_session_id' => $chatSession->id,
                    'channel_name' => $chatSession->channel_display
                ]
            );
        }
        
        // Multiple results - show selection
        return $this->showAutocompleteResults($query, $results);
    }
    
    private function showAutocompleteHelp(): CommandResponse
    {
        $message = <<<MARKDOWN
# Join Channel

Usage:
- `/join #c5` – Join channel #c5 directly
- `/join #` – Show all available channels
- `/join bug` – Search for channels containing "bug"

Examples:
- `/join #c1` – Switch to channel #c1
- `/join project` – Find channels about "project"
MARKDOWN;

        return new CommandResponse(
            type: 'help',
            shouldOpenPanel: true,
            panelData: [
                'message' => $message,
            ],
        );
    }
    
    private function showAutocompleteResults(string $query = '', $results = null, ?int $vaultId = null, ?int $projectId = null): CommandResponse
    {
        if ($results === null) {
            $results = ChatSession::searchForAutocomplete($query, $vaultId, $projectId, 10);
        }
        
        if ($results->isEmpty()) {
            return new CommandResponse(
                type: 'join-error',
                shouldShowErrorToast: true,
                message: empty($query) ? "No active channels found." : "No channels found matching '{$query}'."
            );
        }
        
        $channelsList = $results->map(function ($session) {
            return "- `#{$session->short_code}` – {$session->display_name}";
        })->join("\n");
        
        $title = empty($query) ? 'Available Channels' : "Channels matching '{$query}'";
        $message = "**{$title}**\n\n{$channelsList}\n\nType `/join #c5` to join a specific channel.";
        
        return new CommandResponse(
            type: 'join-channels',
            shouldOpenPanel: true,
            panelData: [
                'message' => $message,
                'channels' => $results->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'short_code' => $session->short_code,
                        'display_name' => $session->display_name,
                        'channel_display' => $session->channel_display,
                        'last_activity' => $session->last_activity_at?->diffForHumans()
                    ];
                })->toArray()
            ]
        );
    }
}