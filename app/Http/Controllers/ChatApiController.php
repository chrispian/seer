<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatApiController extends Controller
{
    public function send(Request $req)
    {
        $data = $req->validate([
            'content' => 'required|string',
            'conversation_id' => 'nullable|string',
            'attachments' => 'array',
            'provider' => 'nullable|string',
            'model' => 'nullable|string',
        ]);

        $messageId = (string) Str::uuid();
        $conversationId = $data['conversation_id'] ?? (string) Str::uuid();

        // ✅ 1) Persist USER fragment using RouteFragment action
        $routeFragment = app(\App\Actions\RouteFragment::class);
        $fragment = $routeFragment($data['content']);
        $userFragmentId = $fragment->id;

        // Update the fragment with chat-specific metadata and source
        // TODO: Should this logic be in the pipeline code? Feels wrong to have this here.
        $fragment->update([
            'source' => 'chat-user',
            'metadata' => array_merge($fragment->metadata ?? [], [
                'turn' => 'prompt',
                'conversation_id' => $conversationId,
                'provider' => $data['provider'] ?? config('fragments.models.fallback_provider', 'ollama'),
                'model' => $data['model'] ?? config('fragments.models.fallback_text_model', 'llama3:latest'),
            ]),
        ]);

        // Minimal chat history for the AI call (extend with real history later)
        // TODO: Implement real history
        // TODO: Implement real system message system so they aren't hard coded.
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user',   'content' => $data['content']],
        ];

        // TODO: The cache section seems like it should extracted to either a helper or service or similar pattern.
        cache()->put("msg:{$messageId}", [
            'messages' => $messages,      // system + user
            'provider' => $data['provider'] ?? config('fragments.models.fallback_provider', 'ollama'),
            'model' => $data['model'] ?? config('fragments.models.fallback_text_model', 'llama3:latest'),
            'user_fragment_id' => $userFragmentId,
            'conversation_id' => $conversationId,
        ], now()->addMinutes(10));

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

        // Validate streaming provider
        $providerConfig = app(\App\Actions\ValidateStreamingProvider::class)($session['provider']);

        // Configure HTTP client for provider
        $response = app(\App\Actions\ConfigureProviderClient::class)(
            $providerConfig,
            $session['model'],
            $session['messages']
        );

        return new StreamedResponse(function () use ($response, $session) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);

            // Start latency measurement
            $startTime = microtime(true);

            // Handle failed response
            if ($response->failed()) {
                app(\App\Actions\HandleStreamingError::class)($response, function ($errorMessage) {
                    echo 'data: '.json_encode(['type' => 'assistant_delta', 'content' => $errorMessage])."\n\n";
                    echo 'data: '.json_encode(['type' => 'done'])."\n\n";
                    @ob_flush();
                    @flush();
                });

                return;
            }

            // Process streaming response
            $streamResult = app(\App\Actions\StreamProviderResponse::class)(
                $response,
                $session['provider'],
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
