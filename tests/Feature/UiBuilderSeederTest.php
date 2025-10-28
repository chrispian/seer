<?php

namespace Tests\Feature;

use HollisLabs\UiBuilder\Models\Page;
use HollisLabs\UiBuilder\database\seeders\UiBuilderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiBuilderSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_inserts_demo_page(): void
    {
        $this->seed(UiBuilderSeeder::class);

        $page = Page::where('key', 'page.agent.table.modal')->first();
        
        expect($page)->not->toBeNull();
        expect($page->config)->toBeArray();
        expect($page->config['id'])->toBe('page.agent.table.modal');
        expect($page->version)->toBe(1);
        expect($page->hash)->not->toBeEmpty();
    }

    public function test_seeder_computes_hash_correctly(): void
    {
        $this->seed(UiBuilderSeeder::class);

        $page = Page::where('key', 'page.agent.table.modal')->first();
        $expectedHash = hash('sha256', json_encode($page->config));
        
        expect($page->hash)->toBe($expectedHash);
    }

    public function test_seeder_includes_layout(): void
    {
        $this->seed(UiBuilderSeeder::class);

        $page = Page::where('key', 'page.agent.table.modal')->first();
        
        expect($page->config['layout'])->toBeArray();
        expect($page->config['layout']['children'])->not->toBeEmpty();
        
        $componentTypes = collect($page->config['layout']['children'])->pluck('type')->toArray();
        expect($componentTypes)->toContain('search.bar');
        expect($componentTypes)->toContain('data-table');
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(UiBuilderSeeder::class);
        $firstPage = FeUiPage::where('key', 'page.agent.table.modal')->first();
        
        $this->seed(UiBuilderSeeder::class);
        $secondPage = FeUiPage::where('key', 'page.agent.table.modal')->first();
        
        expect($secondPage->id)->toBe($firstPage->id);
        expect($secondPage->hash)->toBe($firstPage->hash);
        expect(Page::count())->toBe(2); // agent and model pages
    }

    public function test_page_config_is_valid_json(): void
    {
        $this->seed(UiBuilderSeeder::class);

        $page = Page::where('key', 'page.agent.table.modal')->first();
        $jsonString = json_encode($page->config);
        
        expect($jsonString)->not->toBe(false);
        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    }
}
