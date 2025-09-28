<?php

use App\Actions\ExtractJsonMetadata;
use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('extracts valid JSON metadata from assistant response', function () {
    $fragment = Fragment::factory()->create(['message' => 'Initial message']);

    $action = new ExtractJsonMetadata;
    $payload = [
        'fragment' => $fragment,
        'data' => [
            'message' => 'Here is my response.

<<<JSON_METADATA>>>
{
    "facets": {"intent": "helpful_response"},
    "tags": ["helpful", "response"],
    "links": [{"url": "https://example.com", "title": "Example"}]
}
<<<END_JSON_METADATA>>>',
        ],
    ];

    $result = $action->handle($payload, fn ($p) => $p);

    expect($result['json_metadata']['found'])->toBeTrue();
    expect($result['json_metadata']['metadata'])->toBe(['intent' => 'helpful_response']);
    expect($result['json_metadata']['tags'])->toBe(['helpful', 'response']);
    expect($result['json_metadata']['links'])->toBe([['url' => 'https://example.com', 'title' => 'Example']]);

    // Fragment should be updated with cleaned message
    $fragment->refresh();
    expect($fragment->message)->toBe('Here is my response.');
});

test('handles malformed JSON metadata gracefully', function () {
    $fragment = Fragment::factory()->create(['message' => 'Initial message']);

    $action = new ExtractJsonMetadata;
    $payload = [
        'fragment' => $fragment,
        'data' => [
            'message' => 'Response with bad JSON.

<<<JSON_METADATA>>>
{invalid json here
<<<END_JSON_METADATA>>>',
        ],
    ];

    $result = $action->handle($payload, fn ($p) => $p);

    expect($result['json_metadata']['found'])->toBeTrue();
    expect($result['json_metadata']['metadata'])->toBeNull();
    expect($result['json_metadata']['tags'])->toBeNull();
    expect($result['json_metadata']['links'])->toBeNull();

    // Fragment should still be updated with cleaned message
    $fragment->refresh();
    expect($fragment->message)->toBe('Response with bad JSON.');
});

test('handles missing JSON metadata block', function () {
    $fragment = Fragment::factory()->create(['message' => 'Initial message']);

    $action = new ExtractJsonMetadata;
    $payload = [
        'fragment' => $fragment,
        'data' => [
            'message' => 'Regular response with no metadata.',
        ],
    ];

    $result = $action->handle($payload, fn ($p) => $p);

    expect($result['json_metadata']['found'])->toBeFalse();

    // Fragment message should remain unchanged
    $fragment->refresh();
    expect($fragment->message)->toBe('Initial message');
});

test('handles empty JSON metadata block', function () {
    $fragment = Fragment::factory()->create(['message' => 'Initial message']);

    $action = new ExtractJsonMetadata;
    $payload = [
        'fragment' => $fragment,
        'data' => [
            'message' => 'Response with empty block.

<<<JSON_METADATA>>>
<<<END_JSON_METADATA>>>',
        ],
    ];

    $result = $action->handle($payload, fn ($p) => $p);

    expect($result['json_metadata']['found'])->toBeTrue();
    expect($result['json_metadata']['metadata'])->toBeNull();

    $fragment->refresh();
    expect($fragment->message)->toBe('Response with empty block.');
});

test('handles partial JSON metadata', function () {
    $fragment = Fragment::factory()->create(['message' => 'Initial message']);

    $action = new ExtractJsonMetadata;
    $payload = [
        'fragment' => $fragment,
        'data' => [
            'message' => 'Response with partial data.

<<<JSON_METADATA>>>
{"tags": ["only-tags"]}
<<<END_JSON_METADATA>>>',
        ],
    ];

    $result = $action->handle($payload, fn ($p) => $p);

    expect($result['json_metadata']['found'])->toBeTrue();
    expect($result['json_metadata']['metadata'])->toBeNull();
    expect($result['json_metadata']['tags'])->toBe(['only-tags']);
    expect($result['json_metadata']['links'])->toBeNull();
});

test('handles multiple JSON metadata blocks by taking first', function () {
    $fragment = Fragment::factory()->create(['message' => 'Initial message']);

    $action = new ExtractJsonMetadata;
    $payload = [
        'fragment' => $fragment,
        'data' => [
            'message' => 'Response with multiple blocks.

<<<JSON_METADATA>>>
{"tags": ["first"]}
<<<END_JSON_METADATA>>>

Some text.

<<<JSON_METADATA>>>
{"tags": ["second"]}
<<<END_JSON_METADATA>>>',
        ],
    ];

    $result = $action->handle($payload, fn ($p) => $p);

    expect($result['json_metadata']['found'])->toBeTrue();
    expect($result['json_metadata']['tags'])->toBe(['first']);

    // Should remove all JSON blocks
    $fragment->refresh();
    expect($fragment->message)->toBe('Response with multiple blocks.



Some text.');
});
