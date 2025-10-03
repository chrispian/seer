<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatApiController extends Controller
{
    public function send(Request $req)
    {
        $data = $req->validate([
            'content' => 'required|string',
            'conversation_id' => 'nullable|string',
            'session_id' => 'nullable|integer|exists:chat_sessions,id',
            'attachments' => 'array',
            'provider' => 'nullable|string',
            'model' => 'nullable|string',
        ]);

        $messageId = (string) Str::uuid();
        $conversationId = $data['conversation_id'] ?? (string) Str::uuid();
        $sessionId = $data['session_id'] ?? null;

        // Get session-specific model settings if session_id provided
        $sessionProvider = null;
        $sessionModel = null;
        if ($sessionId) {
            $chatSession = \App\Models\ChatSession::find($sessionId);
            if ($chatSession && $chatSession->model_provider && $chatSession->model_name) {
                $sessionProvider = $chatSession->model_provider;
                $sessionModel = $chatSession->model_name;
            }
        }

        // ✅ 1) Create USER fragment using chat-specific action (bypasses deduplication)
        $createChatFragment = app(\App\Actions\CreateChatFragment::class);
        $fragment = $createChatFragment($data['content']);
        $userFragmentId = $fragment->id;

        // Determine which provider and model to use (priority: request > session > fallback)
        $useProvider = $data['provider'] ?? $sessionProvider ?? config('fragments.models.fallback_provider', 'ollama');
        $useModel = $data['model'] ?? $sessionModel ?? config('fragments.models.fallback_text_model', 'llama3:latest');

        // Update the fragment with chat-specific metadata
        $fragment->update([
            'metadata' => array_merge($fragment->metadata ?? [], [
                'turn' => 'prompt',
                'conversation_id' => $conversationId,
                'session_id' => $sessionId,
                'provider' => $useProvider,
                'model' => $useModel,
            ]),
        ]);

        // Minimal chat history for the AI call (extend with real history later)
        // TODO: Implement real history
        // TODO: Implement real system message system so they aren't hard coded.
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user',   'content' => $data['content']],
        ];

        // Cache chat session using dedicated action
        app(\App\Actions\CacheChatSession::class)(
            $messageId,
            $messages,
            $useProvider,
            $useModel,
            $userFragmentId,
            $conversationId
        );

        // Add message to ChatSession if session_id provided
        if ($sessionId) {
            $chatSession = \App\Models\ChatSession::find($sessionId);
            if ($chatSession) {
                $chatSession->addMessage([
                    'id' => $userFragmentId,
                    'type' => 'user',
                    'message' => $data['content'],
                    'fragment_id' => $userFragmentId,
                    'created_at' => now()->toISOString(),
                ]);
            }
        }

        return response()->json([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'user_fragment_id' => $userFragmentId,
        ]);
    }

    public function stream(string $messageId)
    {
        // Retrieve and validate session
        $session = app(\App\Actions\RetrieveChatSession::class)($messageId);

        return new StreamedResponse(function () use ($session) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);

            // Start latency measurement
            $startTime = microtime(true);

            try {
                // Stream using the new provider system
                $streamResult = app(\App\Actions\StreamChatProvider::class)(
                    $session['provider'],
                    $session['messages'],
                    [
                        'model' => $session['model'],
                        'temperature' => 0.7, // Could be configurable
                    ],
                    // onDelta callback
                    function ($delta) {
                        echo 'data: '.json_encode(['type' => 'assistant_delta', 'content' => $delta])."\n\n";
                        @ob_flush();
                        @flush();
                    },
                    // onComplete callback
                    function () {
                        echo 'data: '.json_encode(['type' => 'done'])."\n\n";
                        @ob_flush();
                        @flush();
                    }
                );
            } catch (\Exception $e) {
                // Handle streaming errors
                $errorMessage = "[Stream error: {$e->getMessage()}]";
                echo 'data: '.json_encode(['type' => 'assistant_delta', 'content' => $errorMessage])."\n\n";
                echo 'data: '.json_encode(['type' => 'done'])."\n\n";
                @ob_flush();
                @flush();

                return;
            }

            // Calculate latency and extract token usage
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $tokenUsage = app(\App\Actions\ExtractTokenUsage::class)($session['provider'], $streamResult['provider_response']);

            // Process assistant fragment using pipeline
            app(\App\Actions\ProcessAssistantFragment::class)([
                'message' => $streamResult['final_message'],
                'provider' => $session['provider'],
                'model' => $session['model'],
                'conversation_id' => $session['conversation_id'],
                'session_id' => $session['session_id'],
                'user_fragment_id' => $session['user_fragment_id'],
                'latency_ms' => $latencyMs,
                'token_usage' => $tokenUsage,
            ]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
