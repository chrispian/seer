<?php

namespace Tests\Unit\Tools;

use App\Models\AgentNote;
use App\Tools\MemoryWriteTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryWriteToolTest extends TestCase
{
    use RefreshDatabase;

    private MemoryWriteTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = app(MemoryWriteTool::class);
    }

    public function test_it_validates_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: kind, scope, body');

        $this->tool->run(['topic' => 'Test Topic']);
    }

    public function test_it_validates_empty_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: topic');

        $this->tool->run([
            'topic' => '',
            'body' => 'Test Body',
            'kind' => 'context',
            'scope' => 'task',
        ]);
    }

    public function test_it_validates_kind_enum_values()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid kind value. Must be one of: decision, observation, risk, todo, context');

        $this->tool->run([
            'topic' => 'Test Topic',
            'body' => 'Test Body',
            'kind' => 'invalid_kind',
            'scope' => 'task',
        ]);
    }

    public function test_it_validates_scope_enum_values()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scope value. Must be one of: task, project, global');

        $this->tool->run([
            'topic' => 'Test Topic',
            'body' => 'Test Body',
            'kind' => 'context',
            'scope' => 'invalid_scope',
        ]);
    }

    public function test_it_creates_note_with_valid_payload()
    {
        $payload = [
            'topic' => 'Test Topic',
            'body' => 'Test Body',
            'kind' => 'context',
            'scope' => 'task',
            'tags' => ['test'],
            'links' => ['http://example.com'],
            'ttl_days' => 30,
        ];

        $result = $this->tool->run($payload);

        $this->assertArrayHasKey('note_id', $result);
        $this->assertIsString($result['note_id']);

        $note = AgentNote::find($result['note_id']);
        $this->assertNotNull($note);
        $this->assertEquals('Test Topic', $note->topic);
        $this->assertEquals('Test Body', $note->body);
        $this->assertEquals('context', $note->kind);
        $this->assertEquals('task', $note->scope);
        $this->assertEquals(['test'], $note->tags);
        $this->assertEquals(['http://example.com'], $note->links);
        $this->assertNotNull($note->ttl_at);
    }

    public function test_it_creates_note_with_minimal_payload()
    {
        $payload = [
            'topic' => 'Minimal Test',
            'body' => 'Minimal Body',
            'kind' => 'observation',
            'scope' => 'global',
        ];

        $result = $this->tool->run($payload);

        $this->assertArrayHasKey('note_id', $result);

        $note = AgentNote::find($result['note_id']);
        $this->assertNotNull($note);
        $this->assertEquals('Minimal Test', $note->topic);
        $this->assertEquals('Minimal Body', $note->body);
        $this->assertEquals('observation', $note->kind);
        $this->assertEquals('global', $note->scope);
        $this->assertEquals([], $note->tags);
        $this->assertEquals([], $note->links);
        $this->assertNull($note->ttl_at);
    }
}
