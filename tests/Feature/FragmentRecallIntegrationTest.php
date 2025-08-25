<?php

namespace Tests\Feature;

use App\Actions\ExtractMetadataEntities;
use App\Actions\GenerateAutoTitle;
use App\Actions\SearchFragments;
use App\Filament\Resources\FragmentResource\Pages\ChatInterface;
use App\Jobs\ProcessFragmentJob;
use App\Models\Fragment;
use App\Models\RecallDecision;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class FragmentRecallIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
    }

    public function test_complete_fragment_to_recall_pipeline(): void
    {
        // Step 1: Create and process a fragment
        $fragment = Fragment::factory()->create([
            'message' => 'Important client meeting with @john.doe on Friday. Need to prepare presentation and send follow-up email about project status.',
            'type' => 'note',
            'title' => null, // Will be auto-generated
            'tags' => ['work'],
            'parsed_entities' => null, // Will be extracted
        ]);

        // Step 2: Process through entity extraction and title generation
        $extractEntities = app(ExtractMetadataEntities::class);
        $generateTitle = app(GenerateAutoTitle::class);

        $enrichedFragment = $extractEntities($fragment);
        $titledFragment = $generateTitle($enrichedFragment);

        // Verify entities were extracted
        $this->assertNotNull($titledFragment->parsed_entities);
        $this->assertArrayHasKey('people', $titledFragment->parsed_entities);
        $this->assertContains('john.doe', $titledFragment->parsed_entities['people']);

        // Verify title was generated
        $this->assertNotNull($titledFragment->title);
        $this->assertNotEmpty($titledFragment->title);

        // Step 3: Create additional searchable fragments
        $fragments = [
            Fragment::factory()->create([
                'message' => 'Meeting notes from client discussion',
                'type' => 'meeting',
                'title' => 'Client Discussion',
                'tags' => ['client', 'meeting'],
            ]),
            Fragment::factory()->create([
                'message' => 'Follow up on project deadline',
                'type' => 'task',
                'title' => 'Project Follow-up',
                'tags' => ['project', 'deadline'],
            ]),
            Fragment::factory()->create([
                'message' => 'Presentation slides for Friday meeting',
                'type' => 'note',
                'title' => 'Presentation Prep',
                'tags' => ['presentation', 'meeting'],
            ]),
        ];

        // Step 4: Test search functionality
        $searchAction = app(SearchFragments::class);
        $searchResults = $searchAction('meeting client');

        $this->assertGreaterThan(0, count($searchResults));
        
        // Should find fragments with meeting or client keywords
        $foundTitles = collect($searchResults)->pluck('title')->toArray();
        $this->assertContains('Client Discussion', $foundTitles);

        // Step 5: Test recall palette integration
        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'meeting client')
            ->call('performRecallSearch');

        $results = $component->get('recallResults');
        $this->assertGreaterThan(0, count($results));

        // Step 6: Test decision logging through selection
        $initialDecisionCount = RecallDecision::count();
        
        $component->call('selectRecallResult', 0);

        // Verify decision was logged
        $this->assertEquals($initialDecisionCount + 1, RecallDecision::count());
        
        $decision = RecallDecision::latest()->first();
        $this->assertEquals('meeting client', $decision->query);
        $this->assertEquals('select', $decision->action);
        $this->assertEquals(0, $decision->selected_index);
        $this->assertNotNull($decision->context);

        // Step 7: Test grammar-based search
        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'type:meeting #client')
            ->call('performRecallSearch');

        $grammarResults = $component->get('recallResults');
        $this->assertGreaterThan(0, count($grammarResults));

        // Should find meeting-type fragments with client tag
        $meetingResults = collect($grammarResults)->where('type', 'meeting');
        $this->assertGreaterThan(0, $meetingResults->count());
    }

    public function test_fragment_processing_job_integration(): void
    {
        Queue::fake();

        $fragment = Fragment::factory()->create([
            'message' => 'Complex fragment with @user mentions and multiple tasks: 1) Call client 2) Update documentation 3) Schedule meeting',
            'type' => 'note',
            'title' => null,
            'parsed_entities' => null,
        ]);

        // Process the fragment through the job
        $job = new ProcessFragmentJob($fragment);
        $result = $job->handle();

        // Verify the job completed successfully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('fragments', $result);

        // Verify fragment was enriched
        $fragment->refresh();
        $this->assertNotNull($fragment->parsed_entities);
        $this->assertNotNull($fragment->title);

        // Verify entities were extracted
        $entities = $fragment->parsed_entities;
        $this->assertArrayHasKey('people', $entities);
        $this->assertContains('user', $entities['people']);
    }

    public function test_search_ranking_with_selection_stats(): void
    {
        // Create fragments
        $popularFragment = Fragment::factory()->create([
            'title' => 'Popular Document',
            'message' => 'This document is frequently accessed',
            'type' => 'note',
            'selection_stats' => [
                'total_selections' => 10,
                'last_selected_at' => now()->subHour()->toISOString(),
                'search_patterns' => [
                    'document' => 5,
                    'popular' => 3,
                ],
                'position_stats' => [
                    'total_clicks' => 10,
                    'average_position' => 2.1,
                ],
            ],
        ]);

        $unpopularFragment = Fragment::factory()->create([
            'title' => 'Unpopular Document',
            'message' => 'This document is rarely accessed',
            'type' => 'note',
            'selection_stats' => [
                'total_selections' => 1,
                'last_selected_at' => now()->subWeek()->toISOString(),
            ],
        ]);

        // Search for fragments
        $searchAction = app(SearchFragments::class);
        $results = $searchAction('document');

        $this->assertGreaterThan(0, count($results));

        // Popular fragment should generally rank higher due to selection stats
        // (though exact ranking depends on the scoring algorithm)
        $resultTitles = collect($results)->pluck('title')->toArray();
        $this->assertContains('Popular Document', $resultTitles);
        $this->assertContains('Unpopular Document', $resultTitles);
    }

    public function test_recall_palette_suggestions_and_autocomplete(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette');

        // Test initial suggestions
        $suggestions = $component->get('recallSuggestions');
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Test autocomplete
        $component->set('recallQuery', 'type:')
            ->call('performRecallSearch');

        $autocomplete = $component->get('recallAutocomplete');
        $this->assertIsArray($autocomplete);
        $this->assertNotEmpty($autocomplete);

        // Should contain type options
        $typeOptions = collect($autocomplete)->where('category', 'Types');
        $this->assertGreaterThan(0, $typeOptions->count());
    }

    public function test_entity_extraction_accuracy(): void
    {
        $fragment = Fragment::factory()->create([
            'message' => 'Email john.doe@company.com and call +1-555-123-4567. Meeting with @alice scheduled for 2024-12-01. Check https://example.com/docs',
            'parsed_entities' => null,
        ]);

        $extractAction = app(ExtractMetadataEntities::class);
        $enrichedFragment = $extractAction($fragment);

        $entities = $enrichedFragment->parsed_entities;

        // Test email extraction
        $this->assertArrayHasKey('emails', $entities);
        $this->assertContains('john.doe@company.com', $entities['emails']);

        // Test phone extraction
        $this->assertArrayHasKey('phones', $entities);
        $this->assertContains('+1-555-123-4567', $entities['phones']);

        // Test people extraction
        $this->assertArrayHasKey('people', $entities);
        $this->assertContains('alice', $entities['people']);

        // Test URL extraction
        $this->assertArrayHasKey('urls', $entities);
        $this->assertContains('https://example.com/docs', $entities['urls']);

        // Test date extraction
        $this->assertArrayHasKey('dates', $entities);
        $this->assertContains('2024-12-01', $entities['dates']);
    }

    public function test_auto_title_generation_strategies(): void
    {
        $generateTitle = app(GenerateAutoTitle::class);

        // Test first line strategy
        $fragment1 = Fragment::factory()->create([
            'message' => "Important Meeting Notes\n\nThis was a crucial discussion about project timeline.",
            'title' => null,
            'type' => 'note',
        ]);

        $titled1 = $generateTitle($fragment1);
        $this->assertEquals('Important Meeting Notes', $titled1->title);

        // Test keyword strategy
        $fragment2 = Fragment::factory()->create([
            'message' => 'Need to call client about urgent project deadline',
            'title' => null,
            'type' => 'task',
            'tags' => ['urgent', 'client'],
        ]);

        $titled2 = $generateTitle($fragment2);
        $this->assertNotNull($titled2->title);
        $this->assertStringContainsString('Task', $titled2->title); // Should include type

        // Test with existing title (should not overwrite)
        $fragment3 = Fragment::factory()->create([
            'message' => 'Some content here',
            'title' => 'Existing Title',
            'type' => 'note',
        ]);

        $titled3 = $generateTitle($fragment3);
        $this->assertEquals('Existing Title', $titled3->title);
    }
}