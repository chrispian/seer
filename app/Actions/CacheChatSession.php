<?php

namespace App\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheChatSession
{
    public function __invoke(
        string $messageId,
        array $messages,
        string $provider,
        string $model,
        $userFragmentId,
        string $conversationId,
        ?string $sessionId = null
    ): void {
        $payload = [
            'messages' => $messages,
            'provider' => $provider,
            'model' => $model,
            'user_fragment_id' => $userFragmentId,
            'conversation_id' => $conversationId,
            'session_id' => $sessionId ?? (string) Str::uuid(),
        ];

        Cache::put("msg:{$messageId}", $payload, now()->addMinutes(10));
    }
}