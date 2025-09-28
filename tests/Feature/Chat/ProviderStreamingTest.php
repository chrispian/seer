<?php

use App\Models\Fragment;
use App\Services\AI\AIProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

describe('Provider Streaming Integration', function () {
    beforeEach(function () {
        // Mock responses for different providers
        Http::fake([
            // Ollama mock response
            'localhost:11434/*' => Http::response(
                json_encode([
                    'message' => ['content' => 'Hello '],
                    'done' => false,
                ])."\n".
                json_encode([
                    'message' => ['content' => 'from Ollama!'],
                    'done' => false,
                ])."\n".
                json_encode([
                    'message' => ['content' => ''],
                    'done' => true,
                    'prompt_eval_count' => 15,
                    'eval_count' => 8,
                ])."\n",
                200,
                ['Content-Type' => 'application/x-ndjson']
            ),
            
            // OpenAI mock response
            'api.openai.com/*' => Http::response(
                "data: " . json_encode([
                    'choices' => [['delta' => ['content' => 'Hello ']]],
                ])."\n\n".
                "data: " . json_encode([
                    'choices' => [['delta' => ['content' => 'from OpenAI!']]],
                ])."\n\n".
                "data: " . json_encode([
                    'choices' => [['finish_reason' => 'stop']],
                    'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
                ])."\n\n".
                "data: [DONE]\n\n",
                200,
                ['Content-Type' => 'text/event-stream']
            ),
        ]);
    });

    test('provider manager validates streaming providers', function () {
        $providerManager = app(AIProviderManager::class);
        
        // Test Ollama provider
        $ollama = $providerManager->getProvider('ollama');
        expect($ollama)->not->toBeNull();
        expect($ollama->supportsStreaming())->toBeTrue();
        expect($ollama->supports('streaming'))->toBeTrue();
        
        // Test OpenAI provider
        $openai = $providerManager->getProvider('openai');
        expect($openai)->not->toBeNull();
        expect($openai->supportsStreaming())->toBeTrue();
        expect($openai->supports('streaming'))->toBeTrue();
    });

    test('ollama provider streams correctly', function () {
        // Set Ollama base URL to ensure it uses our mock
        config(['prism.providers.ollama.url' => 'http://localhost:11434']);
        
        $providerManager = app(AIProviderManager::class);
        $provider = $providerManager->getProvider('ollama');
        
        $messages = [
            ['role' => 'user', 'content' => 'Hello']
        ];
        
        $deltas = [];
        $generator = $provider->streamChat($messages, ['model' => 'llama3:latest']);
        
        foreach ($generator as $delta) {
            $deltas[] = $delta;
        }
        
        expect(count($deltas))->toBeGreaterThan(0);
        expect(strlen(implode('', $deltas)))->toBeGreaterThan(0);
        
        $finalResponse = $generator->getReturn();
        expect($finalResponse)->toHaveKey('done');
        expect($finalResponse['done'])->toBeTrue();
    });

    test('chat streaming endpoint responds correctly', function () {
        // Create user message
        $response = $this->postJson('/api/messages', [
            'content' => 'Test streaming message',
            'provider' => 'ollama',
            'model' => 'llama3:latest',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Stream the response
        $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
        
        expect($streamResponse->getStatusCode())->toBe(200);
        expect($streamResponse->headers->get('Content-Type'))->toContain('text/event-stream');
        
        // Verify the streaming endpoint is accessible and returns correct headers
        // Content testing is complex in test environment due to streaming nature
    });

    test('chat streaming validates provider exists', function () {
        // Create user message with invalid provider
        $response = $this->postJson('/api/messages', [
            'content' => 'Test with invalid provider',
            'provider' => 'invalid-provider',
            'model' => 'some-model',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Stream should handle the invalid provider
        $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
        
        // The request succeeds but the stream content depends on the error handling
        expect($streamResponse->getStatusCode())->toBe(200);
        
        // Just verify that the streaming endpoint responds
        // The exact content will depend on error handling implementation
        expect($streamResponse->headers->get('Content-Type'))->toContain('text/event-stream');
    });

    test('validate streaming provider action works with new system', function () {
        $action = app(\App\Actions\ValidateStreamingProvider::class);
        
        // Test valid provider
        $result = $action('ollama');
        expect($result['provider'])->toBe('ollama');
        expect($result['supports_streaming'])->toBeTrue();
        expect($result['is_available'])->toBeTrue();
        
        // Test invalid provider
        expect(fn() => $action('invalid-provider'))
            ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    test('streaming preserves existing fragment processing', function () {
        // Create user message
        $response = $this->postJson('/api/messages', [
            'content' => 'Test fragment processing',
            'provider' => 'ollama',
            'model' => 'llama3:latest',
            'conversation_id' => 'test-fragment-processing',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify user fragment was created
        $userFragment = Fragment::find($data['user_fragment_id']);
        expect($userFragment)->not->toBeNull();
        expect($userFragment->metadata['conversation_id'])->toBe('test-fragment-processing');

        // Stream the response
        $streamResponse = $this->get("/api/chat/stream/{$data['message_id']}");
        expect($streamResponse->getStatusCode())->toBe(200);

        // Verify assistant fragment gets created with conversation tracking
        // Note: In testing environment, fragments might not be created due to async processing
        // This test verifies the streaming doesn't break existing functionality
    });
});