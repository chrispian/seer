<?php

use App\Actions\StreamChatProvider;
use App\Contracts\AIProviderInterface;
use App\Services\AI\AIProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock provider that supports streaming
    $this->mockProvider = Mockery::mock(AIProviderInterface::class);

    // Mock provider manager
    $this->mockProviderManager = Mockery::mock(AIProviderManager::class);
    $this->app->instance(AIProviderManager::class, $this->mockProviderManager);
});

afterEach(function () {
    Mockery::close();
});

test('StreamChatProvider streams successfully with valid provider', function () {
    $messages = [
        ['role' => 'user', 'content' => 'Hello'],
    ];
    $options = ['model' => 'gpt-4', 'temperature' => 0.7];

    $deltas = [];
    $completed = false;

    // Mock generator that yields some deltas
    $generator = (function () {
        yield 'Hello ';
        yield 'world!';

        return ['usage' => ['input' => 10, 'output' => 5]];
    })();

    $this->mockProvider->shouldReceive('supportsStreaming')->andReturn(true);
    $this->mockProvider->shouldReceive('isAvailable')->andReturn(true);
    $this->mockProvider->shouldReceive('streamChat')
        ->with($messages, $options)
        ->andReturn($generator);

    $this->mockProviderManager->shouldReceive('getProvider')
        ->with('openai')
        ->andReturn($this->mockProvider);

    $action = new StreamChatProvider;

    $result = $action(
        'openai',
        $messages,
        $options,
        function ($delta) use (&$deltas) {
            $deltas[] = $delta;
        },
        function () use (&$completed) {
            $completed = true;
        }
    );

    expect($deltas)->toBe(['Hello ', 'world!']);
    expect($completed)->toBeTrue();
    expect($result['final_message'])->toBe('Hello world!');
    expect($result['provider_response'])->toBe(['usage' => ['input' => 10, 'output' => 5]]);
});

test('StreamChatProvider throws error for non-existent provider', function () {
    $this->mockProviderManager->shouldReceive('getProvider')
        ->with('invalid')
        ->andReturn(null);

    $action = new StreamChatProvider;

    expect(fn () => $action('invalid', [], [], fn ($delta) => null, fn () => null))
        ->toThrow(RuntimeException::class, "Provider 'invalid' not found");
});

test('StreamChatProvider throws error for provider without streaming support', function () {
    $this->mockProvider->shouldReceive('supportsStreaming')->andReturn(false);

    $this->mockProviderManager->shouldReceive('getProvider')
        ->with('basic-provider')
        ->andReturn($this->mockProvider);

    $action = new StreamChatProvider;

    expect(fn () => $action('basic-provider', [], [], fn ($delta) => null, fn () => null))
        ->toThrow(RuntimeException::class, "Provider 'basic-provider' does not support streaming");
});

test('StreamChatProvider throws error for unavailable provider', function () {
    $this->mockProvider->shouldReceive('supportsStreaming')->andReturn(true);
    $this->mockProvider->shouldReceive('isAvailable')->andReturn(false);

    $this->mockProviderManager->shouldReceive('getProvider')
        ->with('unavailable-provider')
        ->andReturn($this->mockProvider);

    $action = new StreamChatProvider;

    expect(fn () => $action('unavailable-provider', [], [], fn ($delta) => null, fn () => null))
        ->toThrow(RuntimeException::class, "Provider 'unavailable-provider' is not available");
});
