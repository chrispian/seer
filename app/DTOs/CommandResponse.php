<?php

namespace App\DTOs;

class CommandResponse
{
    public ?string $message = null;

    public ?string $type = 'system';

    public ?array $fragments = [];

    public bool $shouldResetChat = false; // 👈 Legacy support - will be phased out

    public bool $shouldOpenPanel = false; // 👈 NEW! For slide-over panel

    public ?array $panelData = []; // 👈 NEW! Data to display in panel

    public function __construct(
        ?string $message = null,
        ?string $type = 'system',
        ?array $fragments = [],
        bool $shouldResetChat = false,
        bool $shouldOpenPanel = false,
        ?array $panelData = []
    ) {
        $this->message = $message;
        $this->type = $type;
        $this->fragments = $fragments;
        $this->shouldResetChat = $shouldResetChat;
        $this->shouldOpenPanel = $shouldOpenPanel;
        $this->panelData = $panelData ?? [];
    }
}
