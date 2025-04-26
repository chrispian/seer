<?php

namespace App\DTOs;

class CommandResponse
{
    public ?string $message = null;
    public ?string $type = 'system';
    public ?array $fragments = [];
    public bool $shouldResetChat = false; // 👈 NEW!

    public function __construct(
        ?string $message = null,
        ?string $type = 'system',
        ?array $fragments = [],
        bool $shouldResetChat = false
    ) {
        $this->message = $message;
        $this->type = $type;
        $this->fragments = $fragments;
        $this->shouldResetChat = $shouldResetChat;
    }
}
