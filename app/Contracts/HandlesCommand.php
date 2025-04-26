<?php

namespace App\Contracts;

use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;

interface HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse;
}

