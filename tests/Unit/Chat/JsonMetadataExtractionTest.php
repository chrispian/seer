<?php

use App\Actions\ExtractJsonMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

uses(RefreshDatabase::class);

test('extractJsonMetadata correctly parses valid JSON block', function () {
    $extractor = new ExtractJsonMetadata;
    $reflection = new ReflectionClass($extractor);
    $method = $reflection->getMethod('extractJsonMetadata');
    $method->setAccessible(true);

    $message = <<<'MESSAGE'
Hello! Here's my response.

<<<JSON_METADATA>>>
{
    "tags": ["helpful", "response"],
    "facets": {"category": "assistance", "priority": "high"},
    "links": ["https://example.com"]
}
<<<END_JSON_METADATA>>>

That's all folks!
MESSAGE;

    $result = $method->invokeArgs($extractor, [$message]);

    expect($result['found'])->toBeTrue();
    expect($result['tags'])->toBe(['helpful', 'response']);
    expect($result['metadata'])->toBe(['category' => 'assistance', 'priority' => 'high']);
    expect($result['links'])->toBe(['https://example.com']);
    expect($result['cleaned_message'])->toBe("Hello! Here's my response.\n\n\n\nThat's all folks!");
});

test('extractJsonMetadata handles malformed JSON gracefully', function () {
    $extractor = new ExtractJsonMetadata;
    $reflection = new ReflectionClass($extractor);
    $method = $reflection->getMethod('extractJsonMetadata');
    $method->setAccessible(true);

    $message = <<<'MESSAGE'
Response with bad JSON.

<<<JSON_METADATA>>>
{
    "tags": ["test",
    "broken": json
}
<<<END_JSON_METADATA>>>
MESSAGE;

    $result = $method->invokeArgs($extractor, [$message]);

    expect($result['found'])->toBeTrue();
    expect($result['tags'])->toBeNull();
    expect($result['metadata'])->toBeNull();
    expect($result['links'])->toBeNull();
    expect($result['cleaned_message'])->toBe('Response with bad JSON.');
});

test('extractJsonMetadata returns correct result when no JSON block present', function () {
    $extractor = new ExtractJsonMetadata;
    $reflection = new ReflectionClass($extractor);
    $method = $reflection->getMethod('extractJsonMetadata');
    $method->setAccessible(true);

    $message = 'This is a normal response without any JSON metadata.';

    $result = $method->invokeArgs($extractor, [$message]);

    expect($result['found'])->toBeFalse();
    expect($result['tags'])->toBeNull();
    expect($result['metadata'])->toBeNull();
    expect($result['links'])->toBeNull();
    expect($result['cleaned_message'])->toBe('This is a normal response without any JSON metadata.');
});

test('extractJsonMetadata handles empty JSON object', function () {
    $extractor = new ExtractJsonMetadata;
    $reflection = new ReflectionClass($extractor);
    $method = $reflection->getMethod('extractJsonMetadata');
    $method->setAccessible(true);

    $message = <<<'MESSAGE'
Response with empty JSON.

<<<JSON_METADATA>>>
{}
<<<END_JSON_METADATA>>>
MESSAGE;

    $result = $method->invokeArgs($extractor, [$message]);

    expect($result['found'])->toBeTrue();
    expect($result['tags'])->toBeNull();
    expect($result['metadata'])->toBeNull();
    expect($result['links'])->toBeNull();
    expect($result['cleaned_message'])->toBe('Response with empty JSON.');
});

test('extractJsonMetadata handles partial JSON fields', function () {
    $extractor = new ExtractJsonMetadata;
    $reflection = new ReflectionClass($extractor);
    $method = $reflection->getMethod('extractJsonMetadata');
    $method->setAccessible(true);

    $message = <<<'MESSAGE'
Response with only tags.

<<<JSON_METADATA>>>
{
    "tags": ["only-tags"]
}
<<<END_JSON_METADATA>>>
MESSAGE;

    $result = $method->invokeArgs($extractor, [$message]);

    expect($result['found'])->toBeTrue();
    expect($result['tags'])->toBe(['only-tags']);
    expect($result['metadata'])->toBeNull();
    expect($result['links'])->toBeNull();
    expect($result['cleaned_message'])->toBe('Response with only tags.');
});

test('extractJsonMetadata processes multiple JSON blocks (first match)', function () {
    $extractor = new ExtractJsonMetadata;
    $reflection = new ReflectionClass($extractor);
    $method = $reflection->getMethod('extractJsonMetadata');
    $method->setAccessible(true);

    $message = <<<'MESSAGE'
First block.

<<<JSON_METADATA>>>
{
    "tags": ["first"]
}
<<<END_JSON_METADATA>>>

Second block.

<<<JSON_METADATA>>>
{
    "tags": ["second"]
}
<<<END_JSON_METADATA>>>
MESSAGE;

    $result = $method->invokeArgs($extractor, [$message]);

    expect($result['found'])->toBeTrue();
    expect($result['tags'])->toBe(['first']); // Should process first match
    expect($result['cleaned_message'])->not->toContain('<<<JSON_METADATA>>>');
});
