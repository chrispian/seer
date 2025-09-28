<?php

use App\Actions\ExtractTokenUsage;
use App\Actions\RetrieveChatSession;
use App\Actions\ValidateStreamingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('RetrieveChatSession', function () {
    test('retrieves valid session from cache', function () {
        $messageId = 'test-message-id';
        $sessionData = [
            'provider' => 'ollama',
            'model' => 'llama3:latest',
            'messages' => [['role' => 'user', 'content' => 'test']],
            'conversation_id' => 'conv-123',
            'user_fragment_id' => 'frag-456',
        ];

        Cache::put("msg:{$messageId}", $sessionData, now()->addMinutes(10));

        $action = new RetrieveChatSession;
        $result = $action($messageId);

        expect($result['provider'])->toBe('ollama');
        expect($result['model'])->toBe('llama3:latest');
        expect($result['messages'])->toBe([['role' => 'user', 'content' => 'test']]);
        expect($result['conversation_id'])->toBe('conv-123');
        expect($result['user_fragment_id'])->toBe('frag-456');
        expect($result)->toHaveKey('session_id');
    });

    test('applies defaults for missing fields', function () {
        $messageId = 'test-message-id-empty';
        $sessionData = [
            'messages' => [],
        ]; // Minimal session

        Cache::put("msg:{$messageId}", $sessionData, now()->addMinutes(10));

        $action = new RetrieveChatSession;
        $result = $action($messageId);

        expect($result['provider'])->toBe('ollama');
        expect($result['model'])->toBe('llama3:latest');
        expect($result['messages'])->toBe([]);
        expect($result['conversation_id'])->toBeNull();
        expect($result['user_fragment_id'])->toBeNull();
        expect($result)->toHaveKey('session_id');
    });

    test('throws 404 for missing session', function () {
        $action = new RetrieveChatSession;

        expect(fn () => $action('non-existent'))
            ->toThrow(Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
    });
});

describe('ValidateStreamingProvider', function () {
    test('validates supported provider', function () {
        $action = new ValidateStreamingProvider;
        $result = $action('ollama');

        expect($result['provider'])->toBe('ollama');
        expect($result['base_url'])->toBe('http://localhost:11434');
        expect($result['config'])->toHaveKey('streaming');
        expect($result['config']['streaming'])->toBeTrue();
    });

    test('throws error for unsupported provider', function () {
        $action = new ValidateStreamingProvider;

        expect(fn () => $action('unsupported-provider'))
            ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    test('uses config value for provider URL', function () {
        config(['prism.providers.ollama.url' => 'http://custom-ollama:11434']);

        $action = new ValidateStreamingProvider;
        $result = $action('ollama');

        expect($result['base_url'])->toBe('http://custom-ollama:11434');
    });
});

describe('ExtractTokenUsage', function () {
    test('extracts OpenAI token usage', function () {
        $action = new ExtractTokenUsage;
        $response = [
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
            ],
        ];

        $result = $action('openai', $response);

        expect($result['input'])->toBe(100);
        expect($result['output'])->toBe(50);
    });

    test('extracts Anthropic token usage', function () {
        $action = new ExtractTokenUsage;
        $response = [
            'usage' => [
                'input_tokens' => 150,
                'output_tokens' => 75,
            ],
        ];

        $result = $action('anthropic', $response);

        expect($result['input'])->toBe(150);
        expect($result['output'])->toBe(75);
    });

    test('extracts Ollama token usage', function () {
        $action = new ExtractTokenUsage;
        $response = [
            'prompt_eval_count' => 200,
            'eval_count' => 100,
        ];

        $result = $action('ollama', $response);

        expect($result['input'])->toBe(200);
        expect($result['output'])->toBe(100);
    });

    test('handles missing response data', function () {
        $action = new ExtractTokenUsage;

        $result = $action('ollama', null);

        expect($result['input'])->toBeNull();
        expect($result['output'])->toBeNull();
    });

    test('handles incomplete response data', function () {
        $action = new ExtractTokenUsage;
        $response = ['some_other_field' => 'value'];

        $result = $action('ollama', $response);

        expect($result['input'])->toBeNull();
        expect($result['output'])->toBeNull();
    });

    test('handles unknown provider', function () {
        $action = new ExtractTokenUsage;
        $response = ['usage' => ['tokens' => 100]];

        $result = $action('unknown-provider', $response);

        expect($result['input'])->toBeNull();
        expect($result['output'])->toBeNull();
    });
});
