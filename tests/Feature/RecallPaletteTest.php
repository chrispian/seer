<?php

namespace Tests\Feature;

use App\Filament\Resources\FragmentResource\Pages\ChatInterface;
use App\Models\Fragment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecallPaletteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel('app');
    }

    public function test_can_open_recall_palette(): void
    {
        Livewire::test(ChatInterface::class)
            ->assertSet('showRecallPalette', false)
            ->call('openRecallPalette')
            ->assertSet('showRecallPalette', true)
            ->assertSet('recallQuery', '')
            ->assertSet('selectedRecallIndex', 0);
    }

    public function test_can_close_recall_palette(): void
    {
        Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->assertSet('showRecallPalette', true)
            ->call('closeRecallPalette')
            ->assertSet('showRecallPalette', false);
    }

    public function test_can_search_fragments_via_recall_palette(): void
    {
        // Create test fragments
        Fragment::create([
            'message' => 'Important meeting with client',
            'type' => 'meeting',
            'title' => 'Client Meeting',
            'tags' => ['urgent', 'client'],
        ]);

        Fragment::create([
            'message' => 'Review the project documentation',
            'type' => 'task',
            'title' => 'Doc Review',
            'tags' => ['project'],
        ]);

        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'meeting client')
            ->call('performRecallSearch');

        $component->assertSet('recallQuery', 'meeting client');
        
        // Should find the meeting fragment
        $results = $component->get('recallResults');
        $this->assertCount(1, $results);
        $this->assertEquals('Client Meeting', $results[0]['title']);
        $this->assertEquals('meeting', $results[0]['type']);
    }

    public function test_can_search_with_grammar_filters(): void
    {
        Fragment::create([
            'message' => 'Urgent meeting notes',
            'type' => 'meeting',
            'title' => 'Team Meeting',
            'tags' => ['urgent', 'team'],
        ]);

        Fragment::create([
            'message' => 'Regular task item',
            'type' => 'task',
            'title' => 'Regular Task',
            'tags' => ['normal'],
        ]);

        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'type:meeting #urgent')
            ->call('performRecallSearch');

        $results = $component->get('recallResults');
        $this->assertCount(1, $results);
        $this->assertEquals('Team Meeting', $results[0]['title']);
    }

    public function test_can_navigate_search_results(): void
    {
        // Create multiple fragments
        Fragment::create([
            'message' => 'First result',
            'type' => 'note',
            'title' => 'First Note',
        ]);

        Fragment::create([
            'message' => 'Second result',
            'type' => 'note',
            'title' => 'Second Note',
        ]);

        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'result')
            ->call('performRecallSearch')
            ->assertSet('selectedRecallIndex', 0);

        // Move selection down
        $component->call('moveRecallSelection', 'down')
            ->assertSet('selectedRecallIndex', 1);

        // Move selection up
        $component->call('moveRecallSelection', 'up')
            ->assertSet('selectedRecallIndex', 0);

        // Try to move up from first item (should stay at 0)
        $component->call('moveRecallSelection', 'up')
            ->assertSet('selectedRecallIndex', 0);
    }

    public function test_can_select_search_result(): void
    {
        $fragment = Fragment::create([
            'message' => 'Test fragment content',
            'type' => 'note',
            'title' => 'Test Fragment',
        ]);

        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'test fragment')
            ->call('performRecallSearch')
            ->call('selectRecallResult', 0);

        // Should close the palette and set the selected fragment
        $component->assertSet('showRecallPalette', false);
        
        // Should trigger fragment selection (implementation may vary)
        $results = $component->get('recallResults');
        $this->assertCount(1, $results);
    }

    public function test_generates_suggestions_for_empty_query(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette');

        $suggestions = $component->get('recallSuggestions');
        $this->assertNotEmpty($suggestions);
        
        // Should contain common filter suggestions
        $suggestionTexts = collect($suggestions)->pluck('text');
        $this->assertContains('type:todo', $suggestionTexts);
        $this->assertContains('#urgent', $suggestionTexts);
        $this->assertContains('has:link', $suggestionTexts);
    }

    public function test_generates_autocomplete_options(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'type:')
            ->call('performRecallSearch');

        $autocomplete = $component->get('recallAutocomplete');
        $this->assertNotEmpty($autocomplete);
        
        // Should contain fragment types
        $typeOptions = collect($autocomplete)->where('category', 'Types');
        $this->assertGreaterThan(0, $typeOptions->count());
    }

    public function test_can_apply_suggestion(): void
    {
        $suggestion = [
            'type' => 'filter',
            'text' => 'type:todo',
            'description' => 'Filter by fragment type',
            'category' => 'filters',
        ];

        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->call('applySuggestion', $suggestion);

        $component->assertSet('recallQuery', 'type:todo');
    }

    public function test_handles_empty_search_results(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->call('openRecallPalette')
            ->set('recallQuery', 'nonexistent query that will not match anything')
            ->call('performRecallSearch');

        $results = $component->get('recallResults');
        $this->assertCount(0, $results);
        
        // Should show no results message in UI
        $component->assertSet('recallQuery', 'nonexistent query that will not match anything');
    }
}