<?php

namespace Tests\Unit\DSL\Steps;

use App\Models\Bookmark;
use App\Models\ChatSession;
use App\Models\Fragment;
use App\Services\Commands\DSL\Steps\ModelCreateStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelCreateStepTest extends TestCase
{
    use RefreshDatabase;

    protected ModelCreateStep $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->step = new ModelCreateStep;
    }

    public function test_get_type()
    {
        $this->assertEquals('model.create', $this->step->getType());
    }

    public function test_validate_requires_model_and_data()
    {
        $this->assertFalse($this->step->validate([]));
        $this->assertFalse($this->step->validate(['with' => []]));
        $this->assertFalse($this->step->validate(['with' => ['model' => 'fragment']]));
        $this->assertFalse($this->step->validate(['with' => ['data' => []]]));
        $this->assertTrue($this->step->validate(['with' => ['model' => 'fragment', 'data' => []]]));
    }

    public function test_dry_run_execution()
    {
        $config = [
            'with' => [
                'model' => 'fragment',
                'data' => ['message' => 'Test fragment'],
            ],
        ];

        $result = $this->step->execute($config, [], true);

        $this->assertTrue($result['dry_run']);
        $this->assertEquals('fragment', $result['would_create']);
        $this->assertEquals(['message' => 'Test fragment'], $result['data']);
        $this->assertArrayHasKey('validation', $result);
    }

    public function test_create_fragment_success()
    {
        $config = [
            'with' => [
                'model' => 'fragment',
                'data' => [
                    'message' => 'Test fragment message',
                    'title' => 'Test Fragment',
                    'type' => 'note',
                    'tags' => ['test', 'dsl'],
                ],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertTrue($result['success']);
        $this->assertEquals('fragment', $result['model']);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('record', $result);

        // Verify fragment was created
        $fragment = Fragment::find($result['id']);
        $this->assertNotNull($fragment);
        $this->assertEquals('Test fragment message', $fragment->message);
        $this->assertEquals('Test Fragment', $fragment->title);
        $this->assertEquals('note', $fragment->type);
        $this->assertEquals(['test', 'dsl'], $fragment->tags);
    }

    public function test_create_fragment_with_defaults()
    {
        $config = [
            'with' => [
                'model' => 'fragment',
                'data' => [
                    'message' => 'Test fragment message',
                ],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertTrue($result['success']);

        $fragment = Fragment::find($result['id']);
        $this->assertEquals('note', $fragment->type);
        $this->assertEquals('pending', $fragment->inbox_status);
        $this->assertEquals([], $fragment->tags);
        $this->assertEquals([], $fragment->state);
    }

    public function test_create_chat_session_success()
    {
        $config = [
            'with' => [
                'model' => 'chat_session',
                'data' => [
                    'vault_id' => 1,
                    'title' => 'Test Chat Session',
                    'custom_name' => 'test-chat',
                ],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertTrue($result['success']);
        $this->assertEquals('chat_session', $result['model']);

        $session = ChatSession::find($result['id']);
        $this->assertNotNull($session);
        $this->assertEquals(1, $session->vault_id);
        $this->assertEquals('Test Chat Session', $session->title);
        $this->assertEquals('test-chat', $session->custom_name);
        $this->assertTrue($session->is_active);
        $this->assertFalse($session->is_pinned);
    }

    public function test_create_bookmark_success()
    {
        $config = [
            'with' => [
                'model' => 'bookmark',
                'data' => [
                    'name' => 'Test Bookmark',
                    'vault_id' => 1,
                    'fragment_ids' => [1, 2, 3],
                ],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertTrue($result['success']);
        $this->assertEquals('bookmark', $result['model']);

        $bookmark = Bookmark::find($result['id']);
        $this->assertNotNull($bookmark);
        $this->assertEquals('Test Bookmark', $bookmark->name);
        $this->assertEquals(1, $bookmark->vault_id);
        $this->assertEquals([1, 2, 3], $bookmark->fragment_ids);
    }

    public function test_validation_failure_missing_required_field()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $config = [
            'with' => [
                'model' => 'fragment',
                'data' => [
                    'title' => 'Test Fragment',
                    // Missing required 'message' field
                ],
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_validation_failure_invalid_fragment_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $config = [
            'with' => [
                'model' => 'fragment',
                'data' => [
                    'message' => 'Test fragment',
                    'type' => 'invalid_type',
                ],
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_validation_failure_invalid_importance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $config = [
            'with' => [
                'model' => 'fragment',
                'data' => [
                    'message' => 'Test fragment',
                    'importance' => 10, // Must be 1-5
                ],
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_filters_only_fillable_fields()
    {
        $config = [
            'with' => [
                'model' => 'fragment',
                'data' => [
                    'message' => 'Test fragment',
                    'id' => 999, // Should be filtered out
                    'created_at' => '2023-01-01', // Should be filtered out
                    'some_unsafe_field' => 'value', // Should be filtered out
                ],
            ],
        ];

        $result = $this->step->execute($config, []);

        $this->assertTrue($result['success']);

        $fragment = Fragment::find($result['id']);
        $this->assertEquals('Test fragment', $fragment->message);
        // ID should be auto-generated, not 999
        $this->assertNotEquals(999, $fragment->id);
    }

    public function test_unknown_model_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown model');

        $config = [
            'with' => [
                'model' => 'unknown_model',
                'data' => ['test' => 'data'],
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_chat_session_validation()
    {
        // Test missing required field
        $this->expectException(\InvalidArgumentException::class);

        $config = [
            'with' => [
                'model' => 'chat_session',
                'data' => [
                    'title' => 'Test Chat',
                    // Missing required vault_id
                ],
            ],
        ];

        $this->step->execute($config, []);
    }

    public function test_bookmark_validation()
    {
        // Test missing required field
        $this->expectException(\InvalidArgumentException::class);

        $config = [
            'with' => [
                'model' => 'bookmark',
                'data' => [
                    'vault_id' => 1,
                    // Missing required name
                ],
            ],
        ];

        $this->step->execute($config, []);
    }
}
