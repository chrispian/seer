<?php

namespace Tests\Feature\Console;

use App\Models\FeUiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeUiPageCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->ensureDirectoryExists(resource_path('schemas/ui-builder/pages'));
    }

    protected function tearDown(): void
    {
        $testFiles = [
            resource_path('schemas/ui-builder/pages/test-page.json'),
            resource_path('schemas/ui-builder/pages/agents-test.json'),
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    public function test_generates_valid_page_config(): void
    {
        $this->artisan('fe:make:ui-page', [
            'name' => 'TestPage',
            '--datasource' => 'Agent',
            '--with' => 'table',
        ])
            ->expectsOutput('✓ Created page configuration: ' . resource_path('schemas/ui-builder/pages/test-page.json'))
            ->assertExitCode(0);

        $filePath = resource_path('schemas/ui-builder/pages/test-page.json');
        
        expect(File::exists($filePath))->toBeTrue();
        
        $config = json_decode(File::get($filePath), true);
        expect($config)->toBeArray();
        expect($config['id'])->toBe('page.test-page');
        expect($config['overlay'])->toBe('modal');
        expect($config['title'])->toBe('Test Page');
        expect($config['components'])->toBeArray();
        expect($config['components'])->toHaveCount(1);
    }

    public function test_inserts_config_into_database(): void
    {
        $this->artisan('fe:make:ui-page', [
            'name' => 'AgentsTest',
            '--datasource' => 'Agent',
            '--with' => 'table,search',
        ])->assertExitCode(0);

        $page = FeUiPage::where('key', 'page.agents-test')->first();
        
        expect($page)->not->toBeNull();
        expect($page->config)->toBeArray();
        expect($page->version)->toBe(1);
        expect($page->hash)->not->toBeEmpty();
    }

    public function test_generates_table_component(): void
    {
        $this->artisan('fe:make:ui-page', [
            'name' => 'TestPage',
            '--datasource' => 'Agent',
            '--with' => 'table',
        ])->assertExitCode(0);

        $config = json_decode(File::get(resource_path('schemas/ui-builder/pages/test-page.json')), true);
        $table = collect($config['components'])->firstWhere('type', 'table');
        
        expect($table)->not->toBeNull();
        expect($table['dataSource'])->toBe('Agent');
        expect($table['columns'])->toBeArray();
        expect($table['rowAction'])->toBeArray();
        expect($table['toolbar'])->toBeArray();
    }

    public function test_generates_search_and_table(): void
    {
        $this->artisan('fe:make:ui-page', [
            'name' => 'TestPage',
            '--datasource' => 'Agent',
            '--with' => 'table,search',
        ])->assertExitCode(0);

        $config = json_decode(File::get(resource_path('schemas/ui-builder/pages/test-page.json')), true);
        
        expect($config['components'])->toHaveCount(2);
        
        $search = collect($config['components'])->firstWhere('type', 'search.bar');
        $table = collect($config['components'])->firstWhere('type', 'table');
        
        expect($search)->not->toBeNull();
        expect($table)->not->toBeNull();
        expect($search['result']['target'])->toBe($table['id']);
    }

    public function test_fails_without_datasource(): void
    {
        $this->artisan('fe:make:ui-page', [
            'name' => 'TestPage',
        ])
            ->expectsOutput('The --datasource option is required (e.g., --datasource=Agent)')
            ->assertExitCode(1);
    }

    public function test_fails_with_invalid_datasource(): void
    {
        $this->artisan('fe:make:ui-page', [
            'name' => 'TestPage',
            '--datasource' => 'NonExistentModel',
        ])
            ->expectsOutput('Model NonExistentModel does not exist in app/Models/')
            ->assertExitCode(1);
    }

    public function test_fails_when_page_already_exists(): void
    {
        FeUiPage::create([
            'key' => 'page.test-page',
            'config' => ['id' => 'page.test-page'],
        ]);

        $this->artisan('fe:make:ui-page', [
            'name' => 'TestPage',
            '--datasource' => 'Agent',
        ])
            ->expectsOutput("Page with key 'page.test-page' already exists in database!")
            ->assertExitCode(1);
    }

    public function test_custom_overlay_option(): void
    {
        $this->artisan('fe:make:ui-page', [
            'name' => 'TestPage',
            '--datasource' => 'Agent',
            '--with' => 'table',
            '--overlay' => 'drawer',
        ])->assertExitCode(0);

        $config = json_decode(File::get(resource_path('schemas/ui-builder/pages/test-page.json')), true);
        expect($config['overlay'])->toBe('drawer');
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }
}
