<?php

namespace Tests\Unit;

use App\Actions\LogRecallDecision;
use App\Models\Fragment;
use App\Models\RecallDecision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogRecallDecisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually create the minimal tables needed for this test
        Schema::create('test_recall_decisions', function ($table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('query', 512);
            $table->json('parsed_query')->nullable();
            $table->integer('total_results');
            $table->bigInteger('selected_fragment_id')->nullable();
            $table->integer('selected_index')->nullable();
            $table->string('action', 32)->default('select');
            $table->json('context')->nullable();
            $table->datetime('decided_at');
            $table->timestamps();
        });

        // Mock the RecallDecision model to use the test table
        $this->app->bind(RecallDecision::class, function () {
            $model = new class extends RecallDecision
            {
                protected $table = 'test_recall_decisions';
            };

            return $model;
        });

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_logs_basic_selection_decision(): void
    {
        $results = [
            ['id' => 1, 'title' => 'Fragment 1', 'type' => 'note'],
            ['id' => 2, 'title' => 'Fragment 2', 'type' => 'task'],
        ];

        $logDecision = app(LogRecallDecision::class);
        $decision = $logDecision(
            query: 'test query',
            results: $results,
            selectedFragment: null, // Skip actual fragment for unit test
            selectedIndex: 0,
            action: 'select'
        );

        $this->assertEquals('test query', $decision->query);
        $this->assertEquals(0, $decision->selected_index);
        $this->assertEquals('select', $decision->action);
        $this->assertEquals(2, $decision->total_results);
        $this->assertNotNull($decision->context);
        $this->assertNotNull($decision->decided_at);
    }

    public function test_logs_dismissal_decision(): void
    {
        $results = [
            ['id' => 1, 'title' => 'Fragment 1', 'type' => 'note'],
        ];

        $logDecision = app(LogRecallDecision::class);
        $decision = $logDecision(
            query: 'dismissed query',
            results: $results,
            selectedFragment: null,
            selectedIndex: null,
            action: 'dismiss'
        );

        $this->assertEquals('dismissed query', $decision->query);
        $this->assertNull($decision->selected_fragment_id);
        $this->assertNull($decision->selected_index);
        $this->assertEquals('dismiss', $decision->action);
        $this->assertEquals(1, $decision->total_results);
    }

    public function test_parses_query_grammar(): void
    {
        $results = [['id' => 1, 'title' => 'Test']];

        $logDecision = app(LogRecallDecision::class);
        $decision = $logDecision(
            query: 'type:meeting #urgent client notes',
            results: $results,
            selectedFragment: null,
            selectedIndex: 0,
            action: 'select'
        );

        $context = $decision->context;
        $parsed = $context['parsed_query'];

        $this->assertArrayHasKey('search_terms', $parsed);
        $this->assertArrayHasKey('filters', $parsed);
        $this->assertNotEmpty($parsed['filters']);

        // Verify filters were parsed
        $filterTypes = array_column($parsed['filters'], 'type');
        $this->assertContains('type', $filterTypes);
        $this->assertContains('tag', $filterTypes);
    }

    public function test_includes_position_metrics_in_context(): void
    {
        $results = [
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
            ['id' => 3, 'title' => 'Third'],
        ];

        $logDecision = app(LogRecallDecision::class);
        $decision = $logDecision(
            query: 'test',
            results: $results,
            selectedFragment: null,
            selectedIndex: 2, // Third position (0-indexed)
            action: 'select'
        );

        $context = $decision->context;

        $this->assertEquals(3, $context['click_depth']); // 1-indexed position
        $this->assertEquals(3, $context['total_results']);
        $this->assertFalse($context['clicked_in_top_n']['top_1']);
        $this->assertFalse($context['clicked_in_top_n']['top_3']);
        $this->assertTrue($context['clicked_in_top_n']['top_5']);
    }
}
