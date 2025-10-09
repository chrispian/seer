<?php

namespace App\Services\Orchestration\ToolAware\DTOs;

final class ContextBundle
{
    public string $conversation_summary;
    public string $user_message;
    public array $agent_prefs = [];
    public array $tool_registry_preview = [];

    public function __construct(
        string $user_message,
        string $conversation_summary = '',
        array $agent_prefs = [],
        array $tool_registry_preview = []
    ) {
        $this->user_message = $user_message;
        $this->conversation_summary = $conversation_summary;
        $this->agent_prefs = $agent_prefs;
        $this->tool_registry_preview = $tool_registry_preview;
    }

    public function toArray(): array
    {
        return [
            'conversation_summary' => $this->conversation_summary,
            'user_message' => $this->user_message,
            'agent_prefs' => $this->agent_prefs,
            'tool_registry_preview' => $this->tool_registry_preview,
        ];
    }
}
