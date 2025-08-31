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
                shouldShowErrorToast: true,
                message: "No valid fragment detected. Please try `/frag Your message here...`",
            );
        }

        // Create base fragment (marked as aside to exclude from chat flow)
        $fragment = Fragment::create([
            'vault' => 'default',
            'type' => 'log',
            'message' => $message,
            'source' => 'chat',
            'metadata' => ['aside' => true], // Mark as aside to exclude from main chat
        ]);

        // Dispatch async processing
        ProcessFragmentJob::dispatch($fragment)->onQueue('fragments');

        // Respond with success toast (no panel needed)
        return new CommandResponse(
            type: 'frag',
            shouldShowSuccessToast: true,
            toastData: [
                'title' => 'Fragment Saved',
                'message' => Str::limit($message, 80),
                'fragmentType' => 'fragment',
                'fragmentId' => $fragment->id,
            ],
            fragments: [], // Don't add to chat flow
        );
    }
}
