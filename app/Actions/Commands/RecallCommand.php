<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Models\Fragment;

class RecallCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $type = $command->arguments['type'] ?? null;
        $limit = (int) ($command->arguments['limit'] ?? 5);

        if (! $type) {
            return new CommandResponse(
                message: '⚡ Please specify a fragment type to recall (e.g. `/recall type:todo limit:5`).',
                type: 'system',
                shouldOpenPanel: true,
                panelData: [
                    'message' => "⚡ Please specify a fragment type to recall.\n\nExample: `/recall type:todo limit:5`\n\nAvailable types: todo, note, idea, meeting, task",
                ],
            );
        }

        $results = Fragment::query()
            ->where('type', $type)
            ->latest()
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            return new CommandResponse(
                message: "⚡ No fragments found of type `{$type}`.",
                type: 'recall',
                shouldOpenPanel: true,
                panelData: [
                    'message' => "No fragments found of type `{$type}`.",
                    'type' => $type,
                    'fragments' => [],
                ],
            );
        }

        return new CommandResponse(
            type: 'recall',
            shouldOpenPanel: true,
            panelData: [
                'type' => $type,
                'fragments' => $results->toArray(),
            ],
        );
    }
}
