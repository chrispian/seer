<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Jobs\ProcessFragmentJob;
use App\Models\Fragment;
use Illuminate\Support\Str;

class FragCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $message = $command->arguments['identifier'] ?? null;

        if (empty($message)) {
            return new CommandResponse(
                type: 'frag',
                shouldOpenPanel: true,
                panelData: [
                    'error' => true,
                    'message' => "❌ No valid fragment detected. Please try `/frag Your message here...`",
                ]
            );
        }

        // Create base fragment
        $fragment = Fragment::create([
            'vault' => 'default',
            'type' => 'log',
            'message' => $message,
            'source' => 'chat',
        ]);

        // Dispatch async processing
        ProcessFragmentJob::dispatch($fragment)->onQueue('fragments');

        // Respond with panel showing success
        return new CommandResponse(
            type: 'frag',
            shouldOpenPanel: true,
            panelData: [
                'success' => true,
                'message' => "Fragment received and queued for processing!",
                'fragment' => [
                    'id' => $fragment->id,
                    'type' => $fragment->type,
                    'message' => $fragment->message,
                ]
            ],
        );
    }
}
