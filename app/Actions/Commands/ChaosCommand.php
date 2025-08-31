<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use App\Jobs\ProcessFragmentJob;
use App\Models\Fragment;
use Illuminate\Support\Str;

class ChaosCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        $message = $command->arguments['identifier'] ?? null;

        if (empty($message)) {
            return new CommandResponse(
                type: 'chaos',
                shouldOpenPanel: true,
                panelData: [
                    'error' => true,
                    'message' => "❌ No valid chaos fragment detected. Please try `/chaos Your mixed thoughts here...`",
                ]
            );
        }

        // Create base chaos fragment (marked as aside to exclude from chat flow)
        $fragment = Fragment::create([
            'vault' => 'default',
            'type' => 'chaos',
            'message' => $message,
            'source' => 'chat',
            'metadata' => ['aside' => true], // Mark as aside to exclude from main chat
        ]);

        // Dispatch async processing - chaos fragments will be parsed into atomic fragments
        ProcessFragmentJob::dispatch($fragment)->onQueue('fragments');

        // Respond with success toast (no panel needed)
        return new CommandResponse(
            type: 'chaos',
            shouldShowSuccessToast: true,
            toastData: [
                'title' => 'Chaos Fragment Processing',
                'message' => 'Mixed thoughts will be parsed and organized automatically',
                'fragmentType' => 'chaos',
                'fragmentId' => $fragment->id,
            ],
            fragments: [], // Don't add to chat flow
        );
    }
}