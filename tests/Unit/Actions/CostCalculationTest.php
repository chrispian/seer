<?php

use App\Actions\EnrichAssistantMetadata;
use App\Models\Fragment;
use App\Models\Project;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

uses(RefreshDatabase::class);

test('OpenAI cost calculation with gpt-4o-mini', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateOpenAICost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => 1000, 'output' => 500];
    $model = 'gpt-4o-mini';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    // gpt-4o-mini: input $0.00015/1K, output $0.0006/1K
    // Expected: (1000 * 0.00015 / 1000) + (500 * 0.0006 / 1000) = 0.00015 + 0.0003 = 0.00045
    expect($cost)->toBe(0.00045);
});

test('OpenAI cost calculation with gpt-4o', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateOpenAICost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => 2000, 'output' => 1000];
    $model = 'gpt-4o';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    // gpt-4o: input $0.0025/1K, output $0.01/1K
    // Expected: (2000 * 0.0025 / 1000) + (1000 * 0.01 / 1000) = 0.005 + 0.01 = 0.015
    expect($cost)->toBe(0.015);
});

test('Anthropic cost calculation with claude-3-5-sonnet', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateAnthropicCost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => 1500, 'output' => 800];
    $model = 'claude-3-5-sonnet-latest';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    // claude-3-5-sonnet: input $0.003/1K, output $0.015/1K
    // Expected: (1500 * 0.003 / 1000) + (800 * 0.015 / 1000) = 0.0045 + 0.012 = 0.0165
    expect($cost)->toBe(0.0165);
});

test('Anthropic cost calculation with claude-3-5-haiku', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateAnthropicCost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => 5000, 'output' => 2000];
    $model = 'claude-3-5-haiku-latest';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    // claude-3-5-haiku: input $0.0008/1K, output $0.004/1K
    // Expected: (5000 * 0.0008 / 1000) + (2000 * 0.004 / 1000) = 0.004 + 0.008 = 0.012
    expect($cost)->toBe(0.012);
});

test('OpenRouter cost calculation', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateOpenRouterCost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => 1000, 'output' => 1000];
    $model = 'anthropic/claude-3.5-sonnet';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    // OpenRouter claude-3.5-sonnet: input $0.003/1K, output $0.015/1K
    // Expected: (1000 * 0.003 / 1000) + (1000 * 0.015 / 1000) = 0.003 + 0.015 = 0.018
    expect($cost)->toBe(0.018);
});

test('Cost calculation handles unknown model with default rates', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateOpenAICost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => 1000, 'output' => 1000];
    $model = 'unknown-model';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    // Should use gpt-4o-mini default rates: input $0.00015/1K, output $0.0006/1K
    // Expected: (1000 * 0.00015 / 1000) + (1000 * 0.0006 / 1000) = 0.00015 + 0.0006 = 0.00075
    expect(round($cost, 5))->toBe(0.00075);
});

test('Cost calculation handles zero tokens', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateOpenAICost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => 0, 'output' => 0];
    $model = 'gpt-4o-mini';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    expect($cost)->toBe(0.0);
});

test('Cost calculation handles missing token data', function () {
    $action = new EnrichAssistantMetadata;
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('calculateOpenAICost');
    $method->setAccessible(true);

    $tokenUsage = ['input' => null, 'output' => null];
    $model = 'gpt-4o-mini';

    $cost = $method->invokeArgs($action, [$tokenUsage, $model]);

    expect($cost)->toBe(0.0);
});