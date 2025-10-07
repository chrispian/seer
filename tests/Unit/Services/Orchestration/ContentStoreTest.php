<?php

use App\Services\Orchestration\Artifacts\ContentStore;
use Illuminate\Support\Facades\Storage;

test('puts content and returns hash', function () {
    $store = new ContentStore();
    $content = 'Test content for ContentStore';
    
    $hash = $store->put($content);
    
    expect($hash)->toBeString()
        ->and(strlen($hash))->toBe(64)
        ->and($hash)->toMatch('/^[a-f0-9]{64}$/');
});

test('gets content by hash', function () {
    $store = new ContentStore();
    $content = 'Retrievable test content';
    
    $hash = $store->put($content);
    $retrieved = $store->get($hash);
    
    expect($retrieved)->toBe($content);
});

test('deduplicates identical content', function () {
    $store = new ContentStore();
    $content = 'Duplicate content test';
    
    $hash1 = $store->put($content);
    $hash2 = $store->put($content);
    
    expect($hash1)->toBe($hash2);
});

test('exists returns true for stored content', function () {
    $store = new ContentStore();
    $content = 'Existence check';
    
    $hash = $store->put($content);
    
    expect($store->exists($hash))->toBeTrue()
        ->and($store->exists('0000000000000000000000000000000000000000000000000000000000000000'))->toBeFalse();
});

test('formats fe uri correctly', function () {
    $store = new ContentStore();
    $hash = str_repeat('a', 64);
    
    $hashUri = $store->formatUri($hash);
    $taskUri = $store->formatUri($hash, 'T-123', 'context.yaml');
    
    expect($hashUri)->toBe('fe://artifacts/by-hash/aa/' . $hash)
        ->and($taskUri)->toBe('fe://artifacts/by-task/T-123/context.yaml');
});

test('parses fe uri correctly', function () {
    $store = new ContentStore();
    $hash = str_repeat('b', 64);
    
    $hashUri = "fe://artifacts/by-hash/bb/{$hash}";
    $taskUri = "fe://artifacts/by-task/T-456/plan.md";
    
    $parsedHash = $store->parseUri($hashUri);
    $parsedTask = $store->parseUri($taskUri);
    
    expect($parsedHash)->toBe(['type' => 'hash', 'hash' => $hash])
        ->and($parsedTask)->toBe(['type' => 'task', 'task_id' => 'T-456', 'filename' => 'plan.md']);
});

test('returns file size', function () {
    $store = new ContentStore();
    $content = 'Size test content';
    
    $hash = $store->put($content);
    $size = $store->size($hash);
    
    expect($size)->toBe(strlen($content));
});

test('returns null for non-existent hash', function () {
    $store = new ContentStore();
    $fakeHash = str_repeat('0', 64);
    
    $content = $store->get($fakeHash);
    
    expect($content)->toBeNull();
});
