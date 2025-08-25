<?php

namespace Tests\Feature;

use App\Actions\LogRecallDecision;
use App\Models\Fragment;
use App\Models\RecallDecision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecallDecisionLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_logs_fragment_selection(): void
    {
        $fragment = Fragment::factory()->create([
            'title' => 'Test Fragment',
            'message' => 'Test content',
            'type' => 'note',
        ]);

        $results = [
            ['id' => $fragment->id, 'title' => 'Test Fragment', 'type' => 'note'],
            ['id' => 999, 'title' => 'Other Fragment', 'type' => 'task'],
        ];

        $logDecision = app(LogRecallDecision::class);
        $decision = $logDecision(
            query: 'test query',
            results: $results,
            selectedFragment: $fragment,
            selectedIndex: 0,
            action: 'select'
        );

        $this->assertInstanceOf(RecallDecision::class, $decision);
        $this->assertEquals('test query', $decision->query);
        $this->assertEquals($fragment->id, $decision->selected_fragment_id);
        $this->assertEquals(0, $decision->selected_index);
        $this->assertEquals('select', $decision->action);
        $this->assertEquals(2, $decision->total_results);
        $this->assertNotNull($decision->context);
        
        // Check that fragment stats were updated
        $fragment->refresh();
        $stats = $fragment->selection_stats;
        $this->assertEquals(1, $stats['total_selections']);
        $this->assertNotNull($stats['last_selected_at']);
    }

    public function test_logs_search_dismissal(): void
    {
        $results = [
            ['id' => 1, 'title' => 'Fragment 1', 'type' => 'note'],
            ['id' => 2, 'title' => 'Fragment 2', 'type' => 'task'],
        ];

        $logDecision = app(LogRecallDecision::class);
        $decision = $logDecision(
            query: 'dismissed query',
            results: $results,
            selectedFragment: null,
            selectedIndex: null,
            action: 'dismiss'
        );

        $this->assertInstanceOf(RecallDecision::class, $decision);
        $this->assertEquals('dismissed query', $decision->query);
        $this->assertNull($decision->selected_fragment_id);
        $this->assertNull($decision->selected_index);
        $this->assertEquals('dismiss', $decision->action);
        $this->assertEquals(2, $decision->total_results);
    }

    public function test_tracks_search_patterns(): void
    {
        $fragment = Fragment::factory()->create();
        
        $logDecision = app(LogRecallDecision::class);
        
        // Log multiple selections for the same fragment with different search terms
        $logDecision('meeting notes', [['id' => $fragment->id]], $fragment, 0, 'select');
        $logDecision('client meeting', [['id' => $fragment->id]], $fragment, 0, 'select');
        $logDecision('meeting notes', [['id' => $fragment->id]], $fragment, 0, 'select');
        
        $fragment->refresh();
        $stats = $fragment->selection_stats;
        
        $this->assertEquals(3, $stats['total_selections']);
        $this->assertEquals(2, $stats['search_patterns']['meeting notes']);
        $this->assertEquals(1, $stats['search_patterns']['client meeting']);
    }

    public function test_tracks_position_based_selection(): void
    {
        $fragment = Fragment::factory()->create();
        
        $results = [
            ['id' => 999, 'title' => 'Other'],
            ['id' => 998, 'title' => 'Another'],
            ['id' => $fragment->id, 'title' => 'Target'],
        ];

        $logDecision = app(LogRecallDecision::class);
        $logDecision('test', $results, $fragment, 2, 'select'); // Position 3 (index 2)
        
        $fragment->refresh();
        $stats = $fragment->selection_stats;
        
        $this->assertEquals(1, $stats['position_stats']['total_clicks']);
        $this->assertEquals(3, $stats['position_stats']['average_position']); // Position is 1-indexed
    }

    public function test_parses_query_grammar_for_context(): void
    {
        $fragment = Fragment::factory()->create();
        
        $logDecision = app(LogRecallDecision::class);
        $decision = $logDecision(
            query: 'type:meeting #urgent client notes',
            results: [['id' => $fragment->id]],
            selectedFragment: $fragment,
            selectedIndex: 0,
            action: 'select'
        );

        $context = $decision->context;
        $parsed = $context['parsed_query'];
        
        $this->assertEquals('client notes', trim($parsed['search_terms']));
        $this->assertCount(2, $parsed['filters']); // type and tag filters
        $this->assertContains('type', array_column($parsed['filters'], 'type'));
        $this->assertContains('tag', array_column($parsed['filters'], 'type'));
    }
}