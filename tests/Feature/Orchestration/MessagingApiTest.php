<?php

use App\Models\AgentProfile;
use App\Models\Message;
use App\Models\WorkItem;

beforeEach(function () {
    $this->agent = AgentProfile::factory()->create();
    $this->task = WorkItem::factory()->create();
});

test('can send message to agent inbox', function () {
    $response = $this->postJson("/api/orchestration/agents/{$this->agent->id}/inbox", [
        'stream' => 'test.stream',
        'type' => 'context_pack',
        'task_id' => $this->task->id,
        'envelope' => ['test' => 'data'],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'message_id', 'stream', 'created_at']);

    expect(Message::count())->toBe(1);
});

test('can list agent inbox messages', function () {
    Message::factory()->count(3)->create([
        'to_agent_id' => $this->agent->id,
        'read_at' => null,
    ]);

    $response = $this->getJson("/api/orchestration/agents/{$this->agent->id}/inbox?status=unread");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta'])
        ->assertJsonPath('meta.unread_count', 3);
});

test('can mark message as read', function () {
    $message = Message::factory()->create([
        'to_agent_id' => $this->agent->id,
        'read_at' => null,
    ]);

    $response = $this->postJson("/api/orchestration/messages/{$message->id}/read");

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('can broadcast to project', function () {
    AgentProfile::factory()->count(3)->create(['status' => 'active']);

    $response = $this->postJson('/api/orchestration/projects/test-project-123/broadcast', [
        'type' => 'announcement',
        'envelope' => ['message' => 'Important update'],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'broadcast_count', 'message_ids']);

    expect(Message::count())->toBeGreaterThanOrEqual(3);
});

test('filters inbox by status', function () {
    Message::factory()->count(2)->create([
        'to_agent_id' => $this->agent->id,
        'read_at' => null,
    ]);

    Message::factory()->count(1)->create([
        'to_agent_id' => $this->agent->id,
        'read_at' => now(),
    ]);

    $unreadResponse = $this->getJson("/api/orchestration/agents/{$this->agent->id}/inbox?status=unread");
    $allResponse = $this->getJson("/api/orchestration/agents/{$this->agent->id}/inbox?status=all");

    $unreadData = $unreadResponse->json('data');
    $allData = $allResponse->json('data');

    expect($unreadData)->toBeArray()->toHaveCount(2)
        ->and($allData)->toBeArray()->toHaveCount(3);
});
