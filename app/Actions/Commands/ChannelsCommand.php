<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\ChatSession;

class ChannelsCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        // Get vault/project context from command
        $vaultId = $command->arguments['vault_id'] ?? null;
        $projectId = $command->arguments['project_id'] ?? null;
        
        // Get all active chat sessions
        $channels = ChatSession::searchForAutocomplete('', $vaultId, $projectId, 25);
        
        if ($channels->isEmpty()) {
            return new CommandResponse(
                type: 'channels-list',
                shouldOpenPanel: true,
                panelData: [
                    'message' => "**No Active Channels**\n\nThere are no active chat channels in the current context.",
                ]
            );
        }
        
        // Format channels list
        $channelsList = $channels->map(function ($session) {
            $activity = $session->last_activity_at ? $session->last_activity_at->diffForHumans() : 'No activity';
            return "- `#{$session->short_code}` – {$session->display_name} *(last activity: {$activity})*";
        })->join("\n");
        
        $count = $channels->count();
        $title = "**Active Channels ({$count})**";
        $message = "{$title}\n\n{$channelsList}\n\nType `/join #c5` to join a specific channel.";
        
        return new CommandResponse(
            type: 'channels-list',
            shouldOpenPanel: true,
            panelData: [
                'message' => $message,
                'channels' => $channels->map(function ($session) {
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