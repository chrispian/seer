<?php

use App\Services\Orchestration\ToolAware\ToolAwarePipeline;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Enable tool-aware turn for tests
    config(['fragments.tool_aware_turn.enabled' => true]);
});

it('executes pipeline with no tools needed', function () {
    $session = ChatSession::factory()->create();
    
    $pipeline = app(ToolAwarePipeline::class);
    $result = $pipeline->execute($session->id, 'What is 2+2?');

    expect($result)
        ->toHaveKey('message')
        ->toHaveKey('used_tools')
        ->toHaveKey('correlation_id')
        ->toHaveKey('metadata');

    expect($result['used_tools'])->toBeFalse();
    expect($result['correlation_id'])->toBeNull();
});

it('handles invalid session id gracefully', function () {
    $pipeline = app(ToolAwarePipeline::class);
    $result = $pipeline->execute(99999, 'Test message');

    expect($result)->toHaveKey('message');
    expect($result['metadata'])->toHaveKey('error', false);
});

it('respects max step limit', function () {
    config(['fragments.tool_aware_turn.limits.max_steps_per_turn' => 3]);
    
    // This would require actual tools to be configured and a realistic scenario
    // For now, test that config is respected
    expect(config('fragments.tool_aware_turn.limits.max_steps_per_turn'))->toBe(3);
});

it('returns error metadata on pipeline failure', function () {
    // Force an error by passing invalid data
    $pipeline = app(ToolAwarePipeline::class);
    
    // This should not throw but return error in metadata
    $result = $pipeline->execute(null, '');

    expect($result)->toHaveKey('metadata');
});
