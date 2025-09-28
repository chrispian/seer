<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
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
            'model'    => 'nullable|string',
        ]);

        $messageId = (string) Str::uuid();
        $conversationId = $data['conversation_id'] ?? (string) Str::uuid();

        // ✅ 1) Persist USER fragment (adjust to your schema)
        // --- BEGIN: ADAPT THESE FIELDS TO MATCH YOUR FragmentController::store ---
        $now = now();
        $userFragmentId = DB::table('fragments')->insertGetId([
            'type'         => 'log',                 // keep your current default type
            'source'       => 'chat-user',           // came from chat UI
            'message'      => $data['content'],      // body text/markdown
            'metadata'     => json_encode([
                'turn' => 'prompt',
                // you can add 'conversation_id' here until you formalize it
            ]),
            'vault'        => 'default',             // or derive from session/config
            'created_at'   => $now,
            'updated_at'   => $now,
            // optional: 'tags' => json_encode([]),
            // optional: 'project_id' => ...
        ]);
        // --- END: ADAPT THESE FIELDS ---

        // Minimal chat history for the AI call (extend with real history later)
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user',   'content' => $data['content']],
        ];

        cache()->put("msg:{$messageId}", [
            'messages'         => $messages,      // system + user
            'provider'         => $data['provider'] ?? config('fragments.models.fallback_provider', 'ollama'),
            'model'            => $data['model']    ?? config('fragments.models.fallback_text_model', 'llama3:latest'),
            'user_fragment_id' => $userFragmentId,
        ], now()->addMinutes(10));

        return response()->json([
            'message_id'      => $messageId,
            'conversation_id' => $conversationId,
            'user_fragment_id'=> $userFragmentId,
        ]);
    }

    public function stream(string $messageId)
    {
        $payload = cache()->get("msg:{$messageId}");
        if (!$payload) abort(404, 'Message not found or expired');

        $provider       = $payload['provider']        ?? 'ollama';
        $model          = $payload['model']           ?? 'llama3:latest';
        $messages       = $payload['messages']        ?? [];
        $conversationId = $payload['conversation_id'] ?? null;
        $userFragmentId = $payload['user_fragment_id'] ?? null;

        if ($provider !== 'ollama') {
            abort(400, 'Only ollama streaming enabled for now');
        }

        $ollamaBase = config('prism.providers.ollama.url', 'http://localhost:11434');

        return new StreamedResponse(function () use ($ollamaBase, $model, $messages, $conversationId, $userFragmentId, $provider) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);

            $response = Http::withOptions(['stream' => true, 'timeout' => 0])
                ->post(rtrim($ollamaBase, '/').'/api/chat', [
                    'model'    => $model,
                    'messages' => $messages,
                    'stream'   => true,
                ]);

            if ($response->failed()) {
                // surface an error to the client stream and stop
                echo "data: " . json_encode(['type' => 'assistant_delta', 'content' => "[stream error: {$response->status()}]"]) . "\n\n";
                echo "data: " . json_encode(['type' => 'done']) . "\n\n";
                @ob_flush(); @flush();
                return;
            }

            $body   = $response->toPsrResponse()->getBody();
            $buffer = '';
            $final  = ''; // accumulate assistant text

            while (!$body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '') { usleep(50_000); continue; }
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);
                    if ($line === '') continue;

                    $json = json_decode($line, true);
                    if (!is_array($json)) continue;

                    if (isset($json['message']['content'])) {
                        $delta = $json['message']['content'];
                        $final .= $delta;
                        echo "data: " . json_encode(['type' => 'assistant_delta', 'content' => $delta]) . "\n\n";
                        @ob_flush(); @flush();
                    }

                    if (($json['done'] ?? false) === true) {
                        // persist assistant fragment
                        $now = now();
                        DB::table('fragments')->insert([
                            'type'           => 'log',        // keep your domain type
                            'source'         => 'chat-ai',    // model-generated in chat
                            'message'        => $final,
                            'model_provider' => $provider,    // <-- now in scope
                            'model_name'     => $model,
                            'relationships'  => json_encode([
                                'in_reply_to_id' => $userFragmentId,
                                // 'conversation_id' => $conversationId, // add if you want to track it here
                            ]),
                            'metadata'       => json_encode([
                                'turn' => 'response',
                            ]),
                            'vault'          => 'default',
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ]);

                        echo "data: " . json_encode(['type' => 'done']) . "\n\n";
                        @ob_flush(); @flush();
                    }
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

}

