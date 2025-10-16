<?php

namespace App\Console\Commands;

use Modules\UiBuilder\app\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeUiPageCommand extends Command
{
    protected $signature = 'fe:make:ui-page {name : The name of the page}
                            {--datasource= : The model/datasource to use (e.g., Agent, Project)}
                            {--with=table : Comma-separated component types (table,search,filters)}
                            {--overlay=modal : The overlay type (modal, drawer, page)}';

    protected $description = 'Generate a new UI Builder page configuration';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $datasource = $this->option('datasource');
        $components = $this->option('with') ?: 'table';
        $overlay = $this->option('overlay') ?: 'modal';

        if (! $datasource) {
            $this->error('The --datasource option is required (e.g., --datasource=Agent)');
            return 1;
        }

        if (! $this->validateDatasource($datasource)) {
            $this->error("Model {$datasource} does not exist in app/Models/");
            return 1;
        }

        $kebabName = Str::kebab($name);
        $pageKey = "page.{$kebabName}";

        if (Page::where('key', $pageKey)->exists()) {
            $this->error("Page with key '{$pageKey}' already exists in database!");
            return 1;
        }

        $componentTypes = array_map('trim', explode(',', $components));
        
        $config = $this->generateConfig($pageKey, $name, $datasource, $componentTypes, $overlay);

        $filePath = resource_path("schemas/ui-builder/pages/{$kebabName}.json");
        
        if ($this->files->exists($filePath)) {
            $this->error("File already exists: {$filePath}");
            return 1;
        }

        $this->files->put($filePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $page = Page::create([
            'key' => $pageKey,
            'config' => $config,
        ]);

        $this->info("✓ Created page configuration: {$filePath}");
        $this->info("✓ Inserted into database: {$pageKey} (v{$page->version})");
        $this->info("✓ Hash: {$page->hash}");
        $this->newLine();
        $this->info("Access your page at:");
        $this->line("  → /v2/pages/{$pageKey}");
        
        return 0;
    }

    protected function validateDatasource(string $datasource): bool
    {
        $modelPath = app_path("Models/{$datasource}.php");
        return $this->files->exists($modelPath);
    }

    protected function generateConfig(
        string $pageKey,
        string $name,
        string $datasource,
        array $componentTypes,
        string $overlay
    ): array {
        $config = [
            'id' => $pageKey,
            'overlay' => $overlay,
            'title' => Str::title(str_replace(['-', '_'], ' ', Str::kebab($name))),
            'components' => [],
        ];

        $hasSearch = in_array('search', $componentTypes);
        $hasTable = in_array('table', $componentTypes);
        $hasFilters = in_array('filters', $componentTypes);

        if ($hasSearch) {
            $config['components'][] = $this->generateSearchComponent($pageKey, $datasource, $hasTable);
        }

        if ($hasTable) {
            $config['components'][] = $this->generateTableComponent($pageKey, $datasource, $hasFilters);
        }

        return $config;
    }

    protected function generateSearchComponent(string $pageKey, string $datasource, bool $hasTable): array
    {
        $searchId = str_replace('page.', 'component.search.bar.', $pageKey);
        
        $component = [
            'id' => $searchId,
            'type' => 'search.bar',
            'dataSource' => $datasource,
            'resolver' => 'DataSourceResolver::class',
            'submit' => false,
        ];

        if ($hasTable) {
            $tableId = str_replace('page.', 'component.table.', $pageKey);
            $component['result'] = [
                'target' => $tableId,
                'open' => 'inline',
            ];
        }

        return $component;
    }

    protected function generateTableComponent(string $pageKey, string $datasource, bool $hasFilters): array
    {
        $tableId = str_replace('page.', 'component.table.', $pageKey);
        $kebabDatasource = Str::kebab($datasource);
        
        $columns = $this->inferColumns($datasource, $hasFilters);

        $component = [
            'id' => $tableId,
            'type' => 'table',
            'dataSource' => $datasource,
            'columns' => $columns,
            'rowAction' => [
                'type' => 'command',
                'command' => "/{$kebabDatasource}",
                'params' => ['id' => '{{row.id}}'],
            ],
            'toolbar' => [
                [
                    'id' => str_replace('table.', 'button.icon.add-', $tableId),
                    'type' => 'button.icon',
                    'props' => [
                        'icon' => 'plus',
                        'label' => "New {$datasource}",
                    ],
                    'actions' => [
                        'click' => [
                            'type' => 'command',
                            'command' => "/{$kebabDatasource}-new",
                        ],
                    ],
                ],
            ],
        ];

        return $component;
    }

    protected function inferColumns(string $datasource, bool $hasFilters): array
    {
        $commonColumns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'filterable' => $hasFilters],
            ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
            ['key' => 'updated_at', 'label' => 'Updated', 'sortable' => true],
        ];

        $modelSpecificColumns = match($datasource) {
            'Agent' => [
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'role', 'label' => 'Role', 'filterable' => $hasFilters],
                ['key' => 'provider', 'label' => 'Provider', 'filterable' => $hasFilters],
                ['key' => 'model', 'label' => 'Model', 'filterable' => $hasFilters],
                ['key' => 'status', 'label' => 'Status', 'filterable' => $hasFilters],
                ['key' => 'updated_at', 'label' => 'Updated', 'sortable' => true],
            ],
            'Project' => [
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'filterable' => $hasFilters],
                ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
            ],
            default => $commonColumns,
        };

        return $modelSpecificColumns;
    }
}
