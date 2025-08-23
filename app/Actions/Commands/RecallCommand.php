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

        if (!$type) {
            return new CommandResponse(
                message: "⚡ Please specify a fragment type to recall (e.g. `/recall type:todo limit:5`).",
                type: 'system',
                shouldResetChat: true,
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
                type: 'system',
                shouldResetChat: true,
            );
        }

        $fragments = $results->map(fn ($f) => [
            'type' => $f->type,
            'message' => $f->message,
        ])->toArray();

        return new CommandResponse(
            message: "✅ Recalled {$results->count()} fragment(s) of type `{$type}`.",
            type: 'system',
            fragments: $fragments,
            shouldResetChat: true,
        );
    }
}
