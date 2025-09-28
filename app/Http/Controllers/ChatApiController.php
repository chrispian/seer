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
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user',   'content' => $data['content']],
        ];

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
        $payload = cache()->get("msg:{$messageId}");
        if (! $payload) {
            abort(404, 'Message not found or expired');
        }

        $provider = $payload['provider'] ?? 'ollama';
        $model = $payload['model'] ?? 'llama3:latest';
        $messages = $payload['messages'] ?? [];
        $conversationId = $payload['conversation_id'] ?? null;
        $userFragmentId = $payload['user_fragment_id'] ?? null;

        if ($provider !== 'ollama') {
            abort(400, 'Only ollama streaming enabled for now');
        }

        $ollamaBase = config('prism.providers.ollama.url', 'http://localhost:11434');

        // Generate session ID for tracking (extend from payload if needed)
        $sessionId = $payload['session_id'] ?? (string) Str::uuid();

        return new StreamedResponse(function () use ($ollamaBase, $model, $messages, $conversationId, $userFragmentId, $provider, $sessionId) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);

            // Start latency measurement
            $startTime = microtime(true);

            $response = Http::withOptions(['stream' => true, 'timeout' => 0])
                ->post(rtrim($ollamaBase, '/').'/api/chat', [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => true,
                ]);

            if ($response->failed()) {
                // surface an error to the client stream and stop
                echo 'data: '.json_encode(['type' => 'assistant_delta', 'content' => "[stream error: {$response->status()}]"])."\n\n";
                echo 'data: '.json_encode(['type' => 'done'])."\n\n";
                @ob_flush();
                @flush();

                return;
            }

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';
            $final = ''; // accumulate assistant text
            $tokenUsage = ['input' => null, 'output' => null];
            $ollamaResponse = null;

            while (! $body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '') {
                    usleep(50_000);

                    continue;
                }
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);
                    if ($line === '') {
                        continue;
                    }

                    $json = json_decode($line, true);
                    if (! is_array($json)) {
                        continue;
                    }

                    if (isset($json['message']['content'])) {
                        $delta = $json['message']['content'];
                        $final .= $delta;
                        echo 'data: '.json_encode(['type' => 'assistant_delta', 'content' => $delta])."\n\n";
                        @ob_flush();
                        @flush();
                    }

                    if (($json['done'] ?? false) === true) {
                        // Capture full Ollama response for token usage
                        $ollamaResponse = $json;
                        // Calculate latency and extract token usage
                        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

                        // Extract token usage from provider response
                        $tokenUsage = $this->extractTokenUsage($provider, $ollamaResponse);

                        // Process assistant fragment using pipeline
                        $processAssistant = app(\App\Actions\ProcessAssistantFragment::class);
                        $assistantFragment = $processAssistant([
                            'message' => $final,
                            'provider' => $provider,
                            'model' => $model,
                            'conversation_id' => $conversationId,
                            'session_id' => $sessionId,
                            'user_fragment_id' => $userFragmentId,
                            'latency_ms' => $latencyMs,
                            'token_usage' => $tokenUsage,
                        ]);

                        echo 'data: '.json_encode(['type' => 'done'])."\n\n";
                        @ob_flush();
                        @flush();
                    }
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Extract token usage from provider response
     */
    private function extractTokenUsage(string $provider, ?array $response): array
    {
        if (! $response) {
            return ['input' => null, 'output' => null];
        }

        return match ($provider) {
            'openai' => [
                'input' => $response['usage']['prompt_tokens'] ?? null,
                'output' => $response['usage']['completion_tokens'] ?? null,
            ],
            'anthropic' => [
                'input' => $response['usage']['input_tokens'] ?? null,
                'output' => $response['usage']['output_tokens'] ?? null,
            ],
            'ollama' => [
                'input' => $response['prompt_eval_count'] ?? null,
                'output' => $response['eval_count'] ?? null,
            ],
            'openrouter' => [
                // OpenRouter typically uses OpenAI format
                'input' => $response['usage']['prompt_tokens'] ?? null,
                'output' => $response['usage']['completion_tokens'] ?? null,
            ],
            default => ['input' => null, 'output' => null],
        };
    }
}
