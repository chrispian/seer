<?php

namespace App\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RetrieveChatSession
{
    public function __invoke(string $messageId): array
    {
        $payload = Cache::get("msg:{$messageId}");

        if (! $payload) {
            abort(404, 'Message not found or expired');
        }

        // Normalize the session data with defaults
        return [
            'provider' => $payload['provider'] ?? 'ollama',
            'model' => $payload['model'] ?? 'llama3:latest',
            'messages' => $payload['messages'] ?? [],
            'conversation_id' => $payload['conversation_id'] ?? null,
            'user_fragment_id' => $payload['user_fragment_id'] ?? null,
            'session_id' => $payload['session_id'] ?? (string) Str::uuid(),
        ];
    }
}
