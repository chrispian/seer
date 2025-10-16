<?php

namespace App\Console\Commands;

use Modules\UiBuilder\app\Models\Datasource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeUiDataSourceCommand extends Command
{
    protected $signature = 'fe:make:datasource 
                            {alias : The datasource alias (e.g., Agent, Project)}
                            {--model= : The model class (e.g., App\Models\Agent)}
                            {--searchable=* : Searchable fields}
                            {--filterable=* : Filterable fields}
                            {--sortable=* : Sortable fields}
                            {--with=* : Relationships to eager load}';

    protected $description = 'Create a new UI datasource configuration';

    public function handle(): int
    {
        $alias = $this->argument('alias');
        $modelClass = $this->option('model');

        if (!$modelClass) {
            $modelClass = $this->ask('Model class (e.g., App\Models\Agent)');
        }

        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");

            return 1;
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            $this->error("Class {$modelClass} must extend Illuminate\Database\Eloquent\Model.");

            return 1;
        }

        $model = new $modelClass;
        $table = $model->getTable();

        $columns = Schema::getColumnListing($table);
        $textColumns = array_filter($columns, function ($col) use ($table) {
            $type = Schema::getColumnType($table, $col);

            return in_array($type, ['string', 'text']);
        });

        $searchable = $this->option('searchable');
        if (empty($searchable)) {
            $searchable = $this->choice(
                'Select searchable fields (comma-separated)',
                $textColumns,
                null,
                null,
                true
            );
        }

        $filterable = $this->option('filterable');
        if (empty($filterable)) {
            $filterable = $this->choice(
                'Select filterable fields (comma-separated)',
                $columns,
                null,
                null,
                true
            );
        }

        $sortable = $this->option('sortable');
        if (empty($sortable)) {
            $defaultSortable = array_intersect($columns, ['name', 'created_at', 'updated_at']);
            $sortable = $this->choice(
                'Select sortable fields (comma-separated)',
                $columns,
                $defaultSortable,
                null,
                true
            );
        }

        $with = $this->option('with');
        if (empty($with) && $this->confirm('Add eager-loaded relationships?', false)) {
            $with = explode(',', $this->ask('Relationships (comma-separated)'));
        }

        $transform = [];
        foreach ($columns as $column) {
            if (in_array($column, ['password', 'remember_token'])) {
                continue;
            }

            if (Str::endsWith($column, '_at')) {
                $transform[$column] = ['source' => $column, 'format' => 'iso8601'];
            } else {
                $transform[$column] = $column;
            }
        }

        $datasource = [
            'alias' => $alias,
            'handler' => $modelClass,
            'model_class' => $modelClass,
            'resolver_class' => 'App\Services\V2\GenericDataSourceResolver',
            'default_params_json' => [
                'with' => $with,
                'scopes' => [],
                'default_sort' => in_array('updated_at', $columns) ? ['updated_at', 'desc'] : ['id', 'desc'],
            ],
            'capabilities_json' => [
                'supports' => ['list', 'detail', 'search', 'paginate'],
                'searchable' => $searchable,
                'filterable' => $filterable,
                'sortable' => $sortable,
            ],
            'schema_json' => [
                'transform' => $transform,
            ],
            'capabilities' => [
                'searchable' => $searchable,
                'filterable' => $filterable,
                'sortable' => $sortable,
            ],
        ];

        $existing = Datasource::where('alias', $alias)->first();
        if ($existing && !$this->confirm("Datasource '{$alias}' already exists. Overwrite?", false)) {
            $this->info('Aborted.');

            return 0;
        }

        Datasource::updateOrCreate(
            ['alias' => $alias],
            $datasource
        );

        $this->info("✓ Datasource '{$alias}' created successfully!");
        $this->newLine();
        $this->line('Configuration:');
        $this->line('  Alias: '.$alias);
        $this->line('  Model: '.$modelClass);
        $this->line('  Searchable: '.implode(', ', $searchable));
        $this->line('  Filterable: '.implode(', ', $filterable));
        $this->line('  Sortable: '.implode(', ', $sortable));
        if (!empty($with)) {
            $this->line('  Eager load: '.implode(', ', $with));
        }

        $this->newLine();
        $this->comment('You can now use this datasource in your UI components.');
        $this->comment("Example: GET /api/v2/ui/datasources/{$alias}");

        return 0;
    }
}
