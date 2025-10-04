<?php

namespace Tests\Unit\DSL\Steps;

use App\Models\Bookmark;
use App\Models\ChatSession;
use App\Models\Fragment;
use App\Services\Commands\DSL\Steps\ModelQueryStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelQueryStepTest extends TestCase
{
    use RefreshDatabase;

    protected ModelQueryStep $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->step = new ModelQueryStep;
    }

    public function test_get_type()
    {
        $this->assertEquals('model.query', $this->step->getType());
    }

    public function test_validate_requires_model()
    {
        $this->assertFalse($this->step->validate([]));
        $this->assertFalse($this->step->validate(['with' => []]));
        $this->assertTrue($this->step->validate(['with' => ['model' => 'fragment']]));
    }

    public function test_validate_requires_valid_model()
    {
        $this->assertFalse($this->step->validate(['with' => ['model' => 'invalid_model']]));
        $this->assertTrue($this->step->validate(['with' => ['model' => 'fragment']]));
        $this->assertTrue($this->step->validate(['with' => ['model' => 'chat_session']]));
        $this->assertTrue($this->step->validate(['with' => ['model' => 'bookmark']]));
    }

    public function test_dry_run_execution()
    {
        $config = [
            'with' => [
                'model' => 'fragment',
                'conditions' => [['field' => 'type', 'value' => 'note']],
                'limit' => 10,
            ],
        ];

        $result = $this->step->execute($config, [], true);

        $this->assertTrue($result['dry_run']);
        $this->assertEquals('fragment', $result['would_query']);
        $this->assertEquals([['field' => 'type', 'value' => 'note']], $result['conditions']);
        $this->assertEquals(10, $result['limit']);
    }

    public function test_query_fragments_basic()
    {
        // Create test fragments
        $fragment1 = Fragment::factory()->create(['message' => 'Test fragment 1', 'type' => 'note']);
        $fragment2 = Fragment::factory()->create(['message' => 'Test fragment 2', 'type' => 'todo']);

        $config = [
            'with' => [
                'model' => 'fragment',
                'limit' => 10,
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals('fragment', $result['model']);
        $this->assertCount(2, $result['results']);
    }

    public function test_query_fragments_with_conditions()
    {
        // Create test fragments
        Fragment::factory()->create(['message' => 'Note fragment', 'type' => 'note']);
        Fragment::factory()->create(['message' => 'Todo fragment', 'type' => 'todo']);

        $config = [
            'with' => [
                'model' => 'fragment',
                'conditions' => [
                    ['field' => 'type', 'operator' => '=', 'value' => 'note'],
                ],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertCount(1, $result['results']);
        $this->assertEquals('note', $result['results'][0]['type']);
    }

    public function test_query_fragments_with_search()
    {
        // Create test fragments
        Fragment::factory()->create(['message' => 'This is a searchable fragment', 'title' => 'Test']);
        Fragment::factory()->create(['message' => 'Another fragment', 'title' => 'Different']);

        $config = [
            'with' => [
                'model' => 'fragment',
                'search' => 'searchable',
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertCount(1, $result['results']);
        $this->assertStringContainsString('searchable', $result['results'][0]['message']);
    }

    public function test_query_with_ordering()
    {
        // Create test fragments with different creation times
        $fragment1 = Fragment::factory()->create(['message' => 'First fragment']);
        sleep(1);
        $fragment2 = Fragment::factory()->create(['message' => 'Second fragment']);

        $config = [
            'with' => [
                'model' => 'fragment',
                'order' => ['field' => 'created_at', 'direction' => 'asc'],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertCount(2, $result['results']);
        $this->assertEquals($fragment1->id, $result['results'][0]['id']);
        $this->assertEquals($fragment2->id, $result['results'][1]['id']);
    }

    public function test_query_with_limit_and_offset()
    {
        // Create multiple fragments
        Fragment::factory()->count(5)->create();

        $config = [
            'with' => [
                'model' => 'fragment',
                'limit' => 2,
                'offset' => 1,
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertCount(2, $result['results']);
        $this->assertEquals(2, $result['filters_applied']['limit']);
        $this->assertEquals(1, $result['filters_applied']['offset']);
    }

    public function test_query_chat_sessions()
    {
        // Create test chat sessions
        $session = ChatSession::factory()->create(['title' => 'Test Chat', 'vault_id' => 1]);

        $config = [
            'with' => [
                'model' => 'chat_session',
                'search' => 'Test',
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertCount(1, $result['results']);
        $this->assertEquals('Test Chat', $result['results'][0]['title']);
        $this->assertArrayHasKey('display_title', $result['results'][0]);
    }

    public function test_query_bookmarks()
    {
        // Create test bookmarks
        $bookmark = Bookmark::factory()->create(['name' => 'Test Bookmark', 'vault_id' => 1]);

        $config = [
            'with' => [
                'model' => 'bookmark',
                'search' => 'Test',
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertCount(1, $result['results']);
        $this->assertEquals('Test Bookmark', $result['results'][0]['name']);
        $this->assertArrayHasKey('fragment_count', $result['results'][0]);
    }

    public function test_invalid_field_name_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid field name');

        $config = [
            'with' => [
                'model' => 'fragment',
                'conditions' => [
                    ['field' => 'DROP TABLE users;', 'value' => 'test'],
                ],
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_invalid_operator_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator');

        $config = [
            'with' => [
                'model' => 'fragment',
                'conditions' => [
                    ['field' => 'type', 'operator' => 'UNSAFE_OPERATOR', 'value' => 'test'],
                ],
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_unknown_model_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown model');

        $config = [
            'with' => [
                'model' => 'unknown_model',
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_json_path_conditions()
    {
        // Create fragment with JSON state
        Fragment::factory()->create([
            'message' => 'Test fragment',
            'state' => ['status' => 'active', 'priority' => 'high'],
        ]);

        $config = [
            'with' => [
                'model' => 'fragment',
                'conditions' => [
                    ['field' => 'state.status', 'value' => 'active'],
                ],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertCount(1, $result['results']);
    }
}
