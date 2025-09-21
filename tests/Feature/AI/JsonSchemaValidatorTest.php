<?php

use App\Services\AI\JsonSchemaValidator;

beforeEach(function () {
    $this->validator = new JsonSchemaValidator();
});

test('validates chaos fragments successfully', function () {
    $validJson = json_encode([
        [
            'type' => 'todo',
            'message' => 'Call the doctor',
            'tags' => ['health'],
        ],
        [
            'type' => 'reminder',
            'message' => 'Email the client',
            'tags' => ['work'],
        ],
    ]);

    $result = $this->validator->validateAndParse($validJson, 'chaos_fragments');

    expect($result['success'])->toBeTrue();
    expect($result['data'])->toHaveCount(2);
    expect($result['data'][0]['type'])->toBe('todo');
    expect($result['data'][0]['message'])->toBe('Call the doctor');
    expect($result['data'][1]['type'])->toBe('reminder');
    expect($result['attempts'])->toBe(1);
});

test('validates chaos fragments with markdown code blocks', function () {
    $markdownJson = "```json\n" . json_encode([
        ['type' => 'note', 'message' => 'Test note'],
    ]) . "\n```";

    $result = $this->validator->validateAndParse($markdownJson, 'chaos_fragments');

    expect($result['success'])->toBeTrue();
    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]['message'])->toBe('Test note');
});

test('validates fragment enrichment successfully', function () {
    $validJson = json_encode([
        'type' => 'log',
        'message' => 'System startup completed',
        'tags' => ['system'],
        'metadata' => ['confidence' => 0.9],
    ]);

    $result = $this->validator->validateAndParse($validJson, 'fragment_enrichment');

    expect($result['success'])->toBeTrue();
    expect($result['data']['type'])->toBe('log');
    expect($result['data']['message'])->toBe('System startup completed');
    expect($result['data']['tags'])->toBe(['system']);
});

test('validates type inference successfully', function () {
    $validJson = json_encode([
        'type' => 'todo',
        'confidence' => 0.85,
        'reasoning' => 'Contains action words',
    ]);

    $result = $this->validator->validateAndParse($validJson, 'type_inference');

    expect($result['success'])->toBeTrue();
    expect($result['data']['type'])->toBe('todo');
    expect($result['data']['confidence'])->toBe(0.85);
    expect($result['data']['reasoning'])->toBe('Contains action words');
});

test('handles malformed JSON with retry logic', function () {
    $malformedJson = '{"type": "log", "message": "test"'; // Missing closing brace

    $result = $this->validator->validateAndParse($malformedJson, 'fragment_enrichment');

    expect($result['success'])->toBeFalse();
    expect($result['attempts'])->toBe(3); // Should attempt all retries
    expect($result['error'])->toContain('Syntax error');
});

test('handles invalid chaos fragments structure', function () {
    $invalidJson = json_encode([
        ['type' => 'todo'], // Missing required message field
    ]);

    $result = $this->validator->validateAndParse($invalidJson, 'chaos_fragments');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain("missing required 'message' field");
});

test('handles missing required fields in fragment enrichment', function () {
    $invalidJson = json_encode([
        'message' => 'Test message', // Missing required type field
    ]);

    $result = $this->validator->validateAndParse($invalidJson, 'fragment_enrichment');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Missing required field: type');
});

test('handles missing required fields in type inference', function () {
    $invalidJson = json_encode([
        'type' => 'log', // Missing required confidence field
    ]);

    $result = $this->validator->validateAndParse($invalidJson, 'type_inference');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain("Missing or invalid 'confidence' field");
});

test('normalizes confidence values in type inference', function () {
    $jsonWithHighConfidence = json_encode([
        'type' => 'log',
        'confidence' => 1.5, // Above 1.0
        'reasoning' => 'Test',
    ]);

    $result = $this->validator->validateAndParse($jsonWithHighConfidence, 'type_inference');

    expect($result['success'])->toBeTrue();
    expect($result['data']['confidence'])->toBe(1.0); // Clamped to 1.0
});

test('applies alternative cleaning strategies on retry', function () {
    $messyJson = 'Sure! Here is what you asked for:\n\n{"type": "log", "message": "test", "tags": []}';

    $result = $this->validator->validateAndParse($messyJson, 'fragment_enrichment');

    expect($result['success'])->toBeTrue();
    expect($result['data']['type'])->toBe('log');
    expect($result['attempts'])->toBeGreaterThan(1); // Should have required retries
});

test('generates correlation IDs', function () {
    $correlationId = $this->validator->generateCorrelationId();

    expect($correlationId)->toBeString();
    expect(strlen($correlationId))->toBe(36); // UUID length
});

test('includes correlation ID in result', function () {
    $validJson = json_encode([
        'type' => 'log',
        'message' => 'Test',
    ]);

    $result = $this->validator->validateAndParse($validJson, 'fragment_enrichment');

    expect($result)->toHaveKey('correlation_id');
    expect($result['correlation_id'])->toBeString();
});

test('validates chaos fragments with default values', function () {
    $minimalJson = json_encode([
        ['message' => 'Just a message'], // Only required field
    ]);

    $result = $this->validator->validateAndParse($minimalJson, 'chaos_fragments');

    expect($result['success'])->toBeTrue();
    expect($result['data'][0]['type'])->toBe('note'); // Default type
    expect($result['data'][0]['tags'])->toBe([]); // Default empty tags
    expect($result['data'][0]['state'])->toBe(['status' => 'open']); // Default state
});

test('validates fragment enrichment with default values', function () {
    $minimalJson = json_encode([
        'type' => 'log',
        'message' => 'Required message',
    ]);

    $result = $this->validator->validateAndParse($minimalJson, 'fragment_enrichment');

    expect($result['success'])->toBeTrue();
    expect($result['data']['vault'])->toBe('default'); // Default vault
    expect($result['data']['metadata'])->toBe(['confidence' => 0.9]); // Default metadata
    expect($result['data']['state'])->toBe(['status' => 'open']); // Default state
});

test('handles unknown schema type', function () {
    $validJson = json_encode(['test' => 'data']);

    $result = $this->validator->validateAndParse($validJson, 'unknown_schema');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Unknown schema type: unknown_schema');
});

test('logs validation context', function () {
    $validJson = json_encode([
        'type' => 'log',
        'message' => 'Test',
    ]);

    $context = [
        'fragment_id' => 123,
        'provider' => 'openai',
        'model' => 'gpt-4',
    ];

    $result = $this->validator->validateAndParse(
        $validJson,
        'fragment_enrichment',
        null,
        $context
    );

    expect($result['success'])->toBeTrue();
    // The actual logging is tested by checking the structure is maintained
    expect($result)->toHaveKey('correlation_id');
});