<?php

use App\Jobs\Postmaster\ProcessParcel;
use App\Models\AgentProfile;
use App\Models\Message;
use App\Models\OrchestrationArtifact;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->agent = AgentProfile::factory()->create();
    $this->task = WorkItem::factory()->create();

    Storage::fake('local');
});

test('full postmaster workflow processes parcel', function () {
    $parcel = [
        'to_agent_id' => $this->agent->id,
        'task_id' => $this->task->id,
        'stream' => 'command.delegation',
        'type' => 'postmaster.delivery',
        'headers' => [
            'from' => 'orchestration-pm',
            'priority' => 'normal',
        ],
        'envelope' => [
            'body' => 'Please review the attached artifacts',
        ],
        'attachments' => [
            'test_file' => [
                'content' => 'Test artifact content',
                'filename' => 'test.txt',
                'mime_type' => 'text/plain',
            ],
        ],
    ];

    Queue::fake();

    ProcessParcel::dispatch($parcel, $this->task->id);

    Queue::assertPushed(ProcessParcel::class);
});

test('process parcel creates message and artifacts', function () {
    $content = 'Secret content: AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE';

    $parcel = [
        'to_agent_id' => $this->agent->id,
        'task_id' => $this->task->id,
        'stream' => 'command.delegation',
        'type' => 'postmaster.delivery',
        'headers' => [
            'from' => 'orchestration-pm',
        ],
        'envelope' => [
            'body' => 'Artifacts attached',
        ],
        'attachments' => [
            'config' => [
                'content' => $content,
                'filename' => 'config.txt',
                'mime_type' => 'text/plain',
            ],
        ],
    ];

    ProcessParcel::dispatchSync($parcel, $this->task->id);

    expect(Message::count())->toBe(1)
        ->and(OrchestrationArtifact::count())->toBe(2);

    $message = Message::first();
    expect($message->to_agent_id)->toBe($this->agent->id)
        ->and($message->task_id)->toBe($this->task->id)
        ->and($message->stream)->toBe('command.delegation')
        ->and($message->type)->toBe('postmaster.delivery');

    $artifact = OrchestrationArtifact::first();
    expect($artifact->task_id)->toBe($this->task->id)
        ->and($artifact->filename)->toBe('config.txt')
        ->and($artifact->mime_type)->toBe('text/plain');

    expect($message->envelope['attachments']['config']['fe_uri'])->toStartWith('fe://artifacts/by-task/');

    $artifactContent = $artifact->content;
    expect($artifactContent)->toContain('[REDACTED:AWS_ACCESS_KEY]')
        ->and($artifactContent)->not->toContain('AKIAIOSFODNN7EXAMPLE');
});

test('postmaster workflow handles multiple artifacts', function () {
    $parcel = [
        'to_agent_id' => $this->agent->id,
        'task_id' => $this->task->id,
        'stream' => 'command.delegation',
        'type' => 'postmaster.delivery',
        'headers' => [],
        'envelope' => [
            'body' => 'Multiple files',
        ],
        'attachments' => [
            'file1' => ['content' => 'File 1', 'filename' => 'file1.txt'],
            'file2' => ['content' => 'File 2', 'filename' => 'file2.txt'],
            'file3' => ['content' => 'File 3', 'filename' => 'file3.txt'],
        ],
    ];

    ProcessParcel::dispatchSync($parcel, $this->task->id);

    expect(OrchestrationArtifact::count())->toBe(4);

    $message = Message::first();
    expect($message->envelope['attachments'])->toHaveCount(3);

    foreach ($message->envelope['attachments'] as $key => $artifactRef) {
        expect($artifactRef)->toHaveKey('fe_uri')
            ->and($artifactRef)->toHaveKey('hash')
            ->and($artifactRef)->toHaveKey('size_bytes');
    }
});

test('agent can retrieve messages and artifacts', function () {
    $parcel = [
        'to_agent_id' => $this->agent->id,
        'task_id' => $this->task->id,
        'stream' => 'command.delegation',
        'type' => 'postmaster.delivery',
        'headers' => [],
        'envelope' => [
            'body' => 'Task assignment',
        ],
        'attachments' => [
            'instructions' => ['content' => 'Instructions', 'filename' => 'readme.md'],
        ],
    ];

    ProcessParcel::dispatchSync($parcel, $this->task->id);

    $response = $this->getJson("/api/orchestration/agents/{$this->agent->id}/inbox");

    $response->assertStatus(200)
        ->assertJsonPath('meta.unread_count', 1);

    $messageData = $response->json('data.0');
    expect($messageData['envelope']['attachments'])->toHaveKey('instructions');

    $feUri = $messageData['envelope']['attachments']['instructions']['fe_uri'];
    expect($feUri)->toStartWith('fe://artifacts/by-task/');

    $artifact = OrchestrationArtifact::first();
    $downloadResponse = $this->get("/api/orchestration/artifacts/{$artifact->id}/download");

    $downloadResponse->assertStatus(200)
        ->assertHeader('X-Artifact-Hash', $artifact->hash);
});

test('message read workflow', function () {
    $parcel = [
        'to_agent_id' => $this->agent->id,
        'task_id' => $this->task->id,
        'stream' => 'command.delegation',
        'type' => 'postmaster.delivery',
        'headers' => [],
        'envelope' => ['body' => 'Test message'],
    ];

    ProcessParcel::dispatchSync($parcel, $this->task->id);

    $message = Message::first();
    expect($message->isUnread())->toBeTrue();

    $response = $this->postJson("/api/orchestration/messages/{$message->id}/read");

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $message->refresh();
    expect($message->isRead())->toBeTrue();
});
