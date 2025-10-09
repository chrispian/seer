<?php

use App\Models\OrchestrationArtifact;
use App\Models\WorkItem;

beforeEach(function () {
    $this->task = WorkItem::factory()->create();
});

test('can create artifact', function () {
    $response = $this->postJson("/api/orchestration/tasks/{$this->task->id}/artifacts", [
        'content' => 'Test artifact content',
        'filename' => 'test.txt',
        'mime_type' => 'text/plain',
        'metadata' => ['source' => 'test'],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'artifact' => ['id', 'hash', 'filename', 'fe_uri', 'size_bytes'],
        ]);

    expect(OrchestrationArtifact::count())->toBe(1);
});

test('can list task artifacts', function () {
    OrchestrationArtifact::factory()->count(3)->create([
        'task_id' => $this->task->id,
    ]);

    $response = $this->getJson("/api/orchestration/tasks/{$this->task->id}/artifacts");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta'])
        ->assertJsonPath('meta.count', 3);
});

test('can download artifact', function () {
    $artifact = OrchestrationArtifact::factory()->create([
        'task_id' => $this->task->id,
    ]);

    $response = $this->get("/api/orchestration/artifacts/{$artifact->id}/download");

    $response->assertStatus(200)
        ->assertHeader('X-Artifact-Hash', $artifact->hash);

    expect($response->headers->get('Content-Type'))->toContain($artifact->mime_type);
});

test('deduplicates artifacts by content hash', function () {
    $content = 'Duplicate content test';

    $this->postJson("/api/orchestration/tasks/{$this->task->id}/artifacts", [
        'content' => $content,
        'filename' => 'file1.txt',
    ]);

    $this->postJson("/api/orchestration/tasks/{$this->task->id}/artifacts", [
        'content' => $content,
        'filename' => 'file2.txt',
    ]);

    $artifacts = OrchestrationArtifact::where('task_id', $this->task->id)->get();

    expect($artifacts)->toHaveCount(2)
        ->and($artifacts[0]->hash)->toBe($artifacts[1]->hash);
});

test('includes fe uri in response', function () {
    $response = $this->postJson("/api/orchestration/tasks/{$this->task->id}/artifacts", [
        'content' => 'FE URI test',
        'filename' => 'test.txt',
    ]);

    $feUri = $response->json('artifact.fe_uri');

    expect($feUri)->toStartWith('fe://artifacts/by-task/');
});
