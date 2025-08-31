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
        Log::debug('Clear command - closing command panel');

        // /clear now just closes the command panel silently
        return new CommandResponse(
            type: 'clear',
        );
    }
}
