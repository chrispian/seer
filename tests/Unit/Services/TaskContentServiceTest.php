<?php

use App\Models\WorkItem;
use App\Services\Orchestration\Artifacts\ContentStore;
use App\Services\Orchestration\TaskContentService;

beforeEach(function () {
    $this->contentStore = Mockery::mock(ContentStore::class);
    $this->service = new TaskContentService($this->contentStore);
});

it('stores small content directly in database', function () {
    $task = WorkItem::factory()->create();
    $content = 'Small content';

    $this->service->updateContent($task, 'plan_content', $content);

    expect($task->fresh()->plan_content)->toBe($content);
});

it('stores large content as artifact', function () {
    $task = WorkItem::factory()->create();
    $largeContent = str_repeat('x', 11 * 1024 * 1024); // 11MB

    $this->contentStore->shouldReceive('put')
        ->once()
        ->with($largeContent)
        ->andReturn('abc123hash');

    $this->contentStore->shouldReceive('formatUri')
        ->once()
        ->andReturn('fe://artifacts/by-task/task-id/plan_content.txt');

    $this->contentStore->shouldReceive('getHashPath')
        ->once()
        ->andReturn('orchestration/cas/ab/abc123hash');

    $this->service->updateContent($task, 'plan_content', $largeContent);

    expect($task->fresh()->plan_content)->toStartWith('fe://');
});

it('retrieves content from artifact reference', function () {
    $task = WorkItem::factory()->create([
        'plan_content' => 'fe://artifacts/by-hash/ab/abc123',
    ]);

    $this->contentStore->shouldReceive('parseUri')
        ->once()
        ->with('fe://artifacts/by-hash/ab/abc123')
        ->andReturn(['type' => 'hash', 'hash' => 'abc123']);

    $this->contentStore->shouldReceive('get')
        ->once()
        ->with('abc123')
        ->andReturn('Retrieved content');

    $content = $this->service->getContent($task, 'plan_content');

    expect($content)->toBe('Retrieved content');
});

it('detects artifact references', function () {
    expect($this->service->isArtifactReference('fe://artifacts/by-hash/ab/abc123'))->toBeTrue();
    expect($this->service->isArtifactReference('Regular content'))->toBeFalse();
});

it('migrates large existing content to artifact', function () {
    $largeContent = str_repeat('x', 11 * 1024 * 1024);
    $task = WorkItem::factory()->create([
        'plan_content' => $largeContent,
    ]);

    $this->contentStore->shouldReceive('put')->once()->andReturn('hash123');
    $this->contentStore->shouldReceive('formatUri')->once()->andReturn('fe://artifacts/...');
    $this->contentStore->shouldReceive('getHashPath')->once()->andReturn('path');

    $migrated = $this->service->migrateToArtifactIfNeeded($task, 'plan_content');

    expect($migrated)->toBeTrue();
    expect($task->fresh()->plan_content)->toStartWith('fe://');
});

it('skips migration for small content', function () {
    $task = WorkItem::factory()->create([
        'plan_content' => 'Small content',
    ]);

    $migrated = $this->service->migrateToArtifactIfNeeded($task, 'plan_content');

    expect($migrated)->toBeFalse();
    expect($task->fresh()->plan_content)->toBe('Small content');
});

it('throws exception for invalid field', function () {
    $task = WorkItem::factory()->create();

    $this->service->updateContent($task, 'invalid_field', 'content');
})->throws(\InvalidArgumentException::class);
