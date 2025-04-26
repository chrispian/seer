<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;
use Illuminate\Support\Facades\Log;

class ClearCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        Log::debug('Chat Cleared....');

        return new CommandResponse(
            message: "🧹 Chat cleared. Ready for new ideas.",
            type: 'system',
            shouldResetChat: true // 👈 YES!
        );
    }
}
