<?php

use App\Services\ChatImports\ChatGptConversationParser;
use Illuminate\Support\Facades\File;

it('parses active branch messages in order', function () {
    $payload = json_decode(File::get(base_path('tests/Fixtures/chatgpt/conversations.json')), true, flags: JSON_THROW_ON_ERROR);
    $conversation = $payload[0];

    $parser = new ChatGptConversationParser;
    $parsed = $parser->parse($conversation);

    expect($parsed)->not()->toBeNull();
    expect($parsed->conversationId)->toBe('conv-1');
    expect($parsed->messages)->toHaveCount(2);
    expect($parsed->messages[0]->role)->toBe('user');
    expect($parsed->messages[0]->text)->toBe('Hello ChatGPT');
    expect($parsed->messages[1]->role)->toBe('assistant');
    expect($parsed->messages[1]->text)->toContain('Hi there!');
});

it('normalises multimodal and code content', function () {
    $payload = json_decode(File::get(base_path('tests/Fixtures/chatgpt/conversations.json')), true, flags: JSON_THROW_ON_ERROR);
    $conversation = $payload[1];

    $parser = new ChatGptConversationParser;
    $parsed = $parser->parse($conversation);

    expect($parsed)->not()->toBeNull();
    expect($parsed->messages)->toHaveCount(2);
    expect($parsed->messages[0]->text)->toContain('echo text in PHP');
    expect($parsed->messages[1]->text)->toContain('```php');
    expect($parsed->messages[1]->text)->toContain('echo "Hello"');
});
