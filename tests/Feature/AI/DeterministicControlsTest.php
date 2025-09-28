<?php

use App\Services\AI\ModelSelectionService;

test('applies classification parameters for type inference', function () {
    $modelSelection = app(ModelSelectionService::class);
    $context = ['command' => 'type_inference'];
    $result = $modelSelection->selectTextModel($context);

    expect($result)->toHaveKey('parameters');
    expect($result['parameters']['temperature'])->toBe(0.1);
    expect($result['parameters']['top_p'])->toBe(0.95);
    expect($result['parameters']['max_tokens'])->toBe(500);
});

test('applies enrichment parameters for fragment enrichment', function () {
    $modelSelection = app(ModelSelectionService::class);
    $context = ['command' => 'enrich_fragment'];
    $result = $modelSelection->selectTextModel($context);

    expect($result)->toHaveKey('parameters');
    expect($result['parameters']['temperature'])->toBe(0.3);
    expect($result['parameters']['top_p'])->toBe(0.95);
    expect($result['parameters']['max_tokens'])->toBe(1000);
});



test('applies default enrichment parameters for generic text operations', function () {
    $modelSelection = app(ModelSelectionService::class);
    $context = ['operation_type' => 'text'];
    $result = $modelSelection->selectTextModel($context);

    expect($result)->toHaveKey('parameters');
    expect($result['parameters']['temperature'])->toBe(0.3); // Default to enrichment
    expect($result['parameters']['top_p'])->toBe(0.95);
    expect($result['parameters']['max_tokens'])->toBe(1000);
});

test('allows context overrides for temperature', function () {
    $modelSelection = app(ModelSelectionService::class);
    $context = [
        'command' => 'type_inference',
        'temperature' => 0.05, // Override the default 0.1
    ];
    $result = $modelSelection->selectTextModel($context);

    expect($result['parameters']['temperature'])->toBe(0.05);
    expect($result['parameters']['top_p'])->toBe(0.95); // Should still use default
});

test('allows context overrides for max_tokens', function () {
    $modelSelection = app(ModelSelectionService::class);
    $context = [
        'command' => 'enrich_fragment',
        'max_tokens' => 1500, // Override the default 1000
    ];
    $result = $modelSelection->selectTextModel($context);

    expect($result['parameters']['temperature'])->toBe(0.3); // Should still use default
    expect($result['parameters']['max_tokens'])->toBe(1500);
});

test('applies embedding parameters', function () {
    $modelSelection = app(ModelSelectionService::class);
    $context = ['operation_type' => 'embedding'];
    $result = $modelSelection->selectEmbeddingModel($context);

    expect($result)->toHaveKey('parameters');
    expect($result['parameters'])->toHaveKey('dimensions');
    expect($result['parameters']['dimensions'])->toBeNull(); // Default to null (provider decides)
});

test('returns available parameter types', function () {
    $modelSelection = app(ModelSelectionService::class);
    $types = $modelSelection->getAvailableParameterTypes();

    $expectedTypes = ['classification', 'enrichment', 'embedding', 'tagging', 'title_generation'];
    expect($types)->toBe($expectedTypes);
});

test('returns parameters for specific type', function () {
    $modelSelection = app(ModelSelectionService::class);
    $classificationParams = $modelSelection->getParametersForType('classification');

    expect($classificationParams['temperature'])->toBe(0.1);
    expect($classificationParams['top_p'])->toBe(0.95);
    expect($classificationParams['max_tokens'])->toBe(500);
});

test('handles unknown parameter types gracefully', function () {
    $modelSelection = app(ModelSelectionService::class);
    $unknownParams = $modelSelection->getParametersForType('nonexistent');

    expect($unknownParams)->toBe([]);
});