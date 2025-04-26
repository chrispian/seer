<?php

namespace App\DTOs;

use App\Services\CommandRegistry;

class CommandRequest
{
    public string $command;
    public array $arguments = [];
    public string $raw;
    public string $issuedAt;

    public function __construct(string $command, array $arguments = [], string $raw = '')
    {

        $this->command = $command;
        $this->arguments = $arguments;
        $this->raw = $raw;
        $this->issuedAt = now()->toISOString();
    }
}
