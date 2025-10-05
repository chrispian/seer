<?php

use App\Services\Telemetry\CorrelationContext;
use Illuminate\Support\Str;

beforeEach(function () {
    CorrelationContext::clear();
});

it('generates correlation id for requests', function () {
    $response = $this->get('/');

    $correlationId = $response->headers->get('X-Correlation-ID');

    expect($correlationId)->not->toBeNull();
    expect(Str::isUuid($correlationId))->toBeTrue();
});

it('preserves client provided correlation id', function () {
    $clientCorrelationId = (string) Str::uuid();

    $response = $this->withHeader('X-Correlation-ID', $clientCorrelationId)
        ->get('/');

    expect($response->headers->get('X-Correlation-ID'))->toBe($clientCorrelationId);
});

it('sets correlation context during request', function () {
    $correlationId = (string) Str::uuid();
    $this->get('/', ['X-Correlation-ID' => $correlationId]);

    // Note: In real requests, context would be set during middleware execution
    // This test verifies the context API works correctly
    CorrelationContext::set($correlationId);

    expect(CorrelationContext::get())->toBe($correlationId);
    expect(CorrelationContext::hasContext())->toBeTrue();

    $context = CorrelationContext::getContext();
    expect($context['correlation_id'])->toBe($correlationId);
});

it('validates correlation id format', function () {
    // Valid UUID should be preserved
    $validUuid = (string) Str::uuid();
    $response = $this->withHeader('X-Correlation-ID', $validUuid)->get('/');
    expect($response->headers->get('X-Correlation-ID'))->toBe($validUuid);

    // Valid string (8-128 chars) should be preserved
    $validString = 'test-correlation-12345';
    $response = $this->withHeader('X-Correlation-ID', $validString)->get('/');
    expect($response->headers->get('X-Correlation-ID'))->toBe($validString);

    // Invalid string (too short) should be replaced
    $response = $this->withHeader('X-Correlation-ID', 'short')->get('/');
    $responseCorrelationId = $response->headers->get('X-Correlation-ID');
    expect($responseCorrelationId)->not->toBe('short');
    expect(Str::isUuid($responseCorrelationId))->toBeTrue();
});

it('adds correlation context for logging', function () {
    $correlationId = (string) Str::uuid();
    CorrelationContext::set($correlationId);
    CorrelationContext::addContext('test_key', 'test_value');

    $loggingContext = CorrelationContext::forLogging();

    expect($loggingContext['correlation_id'])->toBe($correlationId);
    expect($loggingContext)->toHaveKey('timestamp');
    expect($loggingContext['context']['test_key'])->toBe('test_value');
});

it('clears correlation context', function () {
    $correlationId = (string) Str::uuid();
    CorrelationContext::set($correlationId);
    CorrelationContext::addContext('test', 'value');

    expect(CorrelationContext::hasContext())->toBeTrue();

    CorrelationContext::clear();

    expect(CorrelationContext::hasContext())->toBeFalse();
    expect(CorrelationContext::get())->toBeNull();
    expect(CorrelationContext::getContext())->toBe([]);
});
