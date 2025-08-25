<?php

namespace Tests\Feature;

use App\Actions\SearchFragments;
use App\Models\Fragment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFragmentsTest extends TestCase
{
    use RefreshDatabase;

    private SearchFragments $searchAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchAction = app(SearchFragments::class);
    }

    public function test_can_search_by_text_content(): void
    {
        // Create test fragments
        Fragment::create([
            'message' => 'Meeting with John about project alpha',
            'type' => 'meeting',
            'title' => 'Project Alpha Meeting',
        ]);

        Fragment::create([
            'message' => 'Review beta documentation',
            'type' => 'task',
            'title' => 'Beta Docs Review',
        ]);

        Fragment::create([
            'message' => 'Alpha release notes need updating',
            'type' => 'todo',
            'title' => 'Update Alpha Notes',
        ]);

        // Search for "alpha"
        $results = $this->searchAction->__invoke('alpha');

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('message')->contains('Meeting with John about project alpha'));
        $this->assertTrue($results->pluck('message')->contains('Alpha release notes need updating'));
    }

    public function test_can_search_with_type_filter(): void
    {
        Fragment::create([
            'message' => 'Complete the report',
            'type' => 'todo',
        ]);

        Fragment::create([
            'message' => 'Complete the presentation',
            'type' => 'task',
        ]);

        Fragment::create([
            'message' => 'Complete the review',
            'type' => 'todo',
        ]);

        // Search with type filter
        $results = $this->searchAction->__invoke('type:todo complete');

        $this->assertCount(2, $results);
        foreach ($results as $fragment) {
            $this->assertEquals('todo', $fragment->type->value);
        }
    }

    public function test_can_search_with_tag_filter(): void
    {
        Fragment::create([
            'message' => 'Important meeting',
            'type' => 'meeting',
            'tags' => ['urgent', 'client'],
        ]);

        Fragment::create([
            'message' => 'Regular meeting',
            'type' => 'meeting',
            'tags' => ['team'],
        ]);

        Fragment::create([
            'message' => 'Another urgent task',
            'type' => 'task',
            'tags' => ['urgent'],
        ]);

        // Search with tag filter
        $results = $this->searchAction->__invoke('#urgent meeting');

        $this->assertCount(1, $results);
        $this->assertEquals('Important meeting', $results->first()->message);
    }

    public function test_can_search_with_mention_filter(): void
    {
        Fragment::create([
            'message' => 'Meeting notes',
            'type' => 'note',
            'parsed_entities' => ['people' => ['john.doe', 'jane.smith']],
        ]);

        Fragment::create([
            'message' => 'Task assignment',
            'type' => 'task',
            'parsed_entities' => ['people' => ['jane.smith']],
        ]);

        Fragment::create([
            'message' => 'Random note',
            'type' => 'note',
            'parsed_entities' => ['people' => []],
        ]);

        // Search with mention filter
        $results = $this->searchAction->__invoke('@john.doe');

        $this->assertCount(1, $results);
        $this->assertEquals('Meeting notes', $results->first()->message);
    }

    public function test_can_search_with_has_link_filter(): void
    {
        Fragment::create([
            'message' => 'Check the documentation',
            'type' => 'note',
            'parsed_entities' => ['urls' => ['https://docs.example.com']],
        ]);

        Fragment::create([
            'message' => 'Simple note',
            'type' => 'note',
            'parsed_entities' => ['urls' => []],
        ]);

        // Search with has:link filter
        $results = $this->searchAction->__invoke('has:link documentation');

        $this->assertCount(1, $results);
        $this->assertEquals('Check the documentation', $results->first()->message);
    }

    public function test_can_search_with_date_filters(): void
    {
        Fragment::create([
            'message' => 'Old fragment',
            'type' => 'note',
            'created_at' => now()->subDays(10),
        ]);

        Fragment::create([
            'message' => 'Recent fragment',
            'type' => 'note',
            'created_at' => now()->subDays(2),
        ]);

        Fragment::create([
            'message' => 'Today fragment',
            'type' => 'note',
            'created_at' => now(),
        ]);

        // Search with after date filter
        $afterDate = now()->subDays(5)->format('Y-m-d');
        $results = $this->searchAction->__invoke("after:{$afterDate} fragment");

        $this->assertCount(2, $results);
        $this->assertFalse($results->pluck('message')->contains('Old fragment'));
    }

    public function test_search_with_complex_grammar(): void
    {
        // Create diverse test data
        Fragment::create([
            'message' => 'Urgent meeting with client about project',
            'type' => 'meeting',
            'tags' => ['urgent', 'client', 'project-alpha'],
            'parsed_entities' => [
                'people' => ['john.doe'],
                'urls' => ['https://project.com'],
            ],
            'created_at' => now()->subDays(1),
        ]);

        Fragment::create([
            'message' => 'Regular team standup',
            'type' => 'meeting',
            'tags' => ['team', 'daily'],
            'parsed_entities' => ['people' => [], 'urls' => []],
            'created_at' => now()->subDays(10),
        ]);

        // Complex search query
        $afterDate = now()->subDays(3)->format('Y-m-d');
        $results = $this->searchAction->__invoke(
            "type:meeting #urgent @john.doe has:link after:{$afterDate} project"
        );

        $this->assertCount(1, $results);
        $fragment = $results->first();
        $this->assertEquals('meeting', $fragment->type->value);
        $this->assertContains('urgent', $fragment->tags);
        $this->assertContains('john.doe', $fragment->parsed_entities['people']);
    }

    public function test_search_ranking_prioritizes_recent_items(): void
    {
        // Create fragments with different ages
        $old = Fragment::create([
            'message' => 'Search test content',
            'type' => 'note',
            'title' => 'Search Test',
            'created_at' => now()->subYear(),
        ]);

        $recent = Fragment::create([
            'message' => 'Search test content',
            'type' => 'note',
            'title' => 'Search Test',
            'created_at' => now(),
        ]);

        $results = $this->searchAction->__invoke('search test');

        // Recent item should rank higher
        $this->assertEquals($recent->id, $results->first()->id);
    }

    public function test_search_ranking_prioritizes_title_matches(): void
    {
        Fragment::create([
            'message' => 'Some content with the word test buried in it',
            'type' => 'note',
            'title' => 'Random Title',
        ]);

        Fragment::create([
            'message' => 'Different content here',
            'type' => 'note',
            'title' => 'Test Document',
        ]);

        $results = $this->searchAction->__invoke('test');

        // Title match should rank higher
        $this->assertEquals('Test Document', $results->first()->title);
    }

    public function test_vault_and_project_filtering(): void
    {
        Fragment::create([
            'message' => 'Vault A content',
            'type' => 'note',
            'vault' => 'vault-a',
            'project_id' => 1,
        ]);

        Fragment::create([
            'message' => 'Vault B content',
            'type' => 'note',
            'vault' => 'vault-b',
            'project_id' => 1,
        ]);

        Fragment::create([
            'message' => 'Vault A different project',
            'type' => 'note',
            'vault' => 'vault-a',
            'project_id' => 2,
        ]);

        // Search with vault and project filters
        $results = $this->searchAction->__invoke('content', 'vault-a', 1);

        $this->assertCount(1, $results);
        $this->assertEquals('Vault A content', $results->first()->message);
    }
}
