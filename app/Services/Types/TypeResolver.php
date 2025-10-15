<?php

namespace App\Services\Types;

use App\DTOs\Types\TypeSchema;
use Illuminate\Support\Facades\DB;

class TypeResolver
{
    public function __construct(
        private TypeRegistry $registry
    ) {}

    public function query(string $alias, array $params = []): array
    {
        $schema = $this->registry->get($alias);
        
        if (!$schema) {
            throw new \Exception("Type '{$alias}' not found");
        }

        return match ($schema->sourceType) {
            'eloquent' => $this->queryEloquent($schema, $params),
            'database' => $this->queryDatabase($schema, $params),
            'sushi' => $this->querySushi($schema, $params),
            'api' => $this->queryApi($schema, $params),
            default => throw new \Exception("Unsupported source type: {$schema->sourceType}")
        };
    }

    public function show(string $alias, mixed $id): ?array
    {
        $schema = $this->registry->get($alias);
        
        if (!$schema) {
            throw new \Exception("Type '{$alias}' not found");
        }

        return match ($schema->sourceType) {
            'eloquent' => $this->showEloquent($schema, $id),
            'database' => $this->showDatabase($schema, $id),
            'sushi' => $this->showSushi($schema, $id),
            'api' => $this->showApi($schema, $id),
            default => throw new \Exception("Unsupported source type: {$schema->sourceType}")
        };
    }

    private function queryEloquent(TypeSchema $schema, array $params): array
    {
        $modelClass = $schema->config['model'] ?? null;
        
        if (!$modelClass || !class_exists($modelClass)) {
            throw new \Exception("Model class not found: {$modelClass}");
        }

        $query = $modelClass::query();

        if (isset($params['search']) && $params['search']) {
            $searchableFields = collect($schema->fields)
                ->filter(fn($f) => $f->searchable ?? false)
                ->pluck('name')
                ->toArray();

            if ($searchableFields) {
                $query->where(function ($q) use ($searchableFields, $params) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$params['search']}%");
                    }
                });
            }
        }

        if (isset($params['sort']) && $params['sort']) {
            $direction = $params['direction'] ?? 'asc';
            $query->orderBy($params['sort'], $direction);
        }

        if (isset($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }

        $perPage = $params['per_page'] ?? 15;
        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }

    private function showEloquent(TypeSchema $schema, mixed $id): ?array
    {
        $modelClass = $schema->config['model'] ?? null;
        
        if (!$modelClass || !class_exists($modelClass)) {
            throw new \Exception("Model class not found: {$modelClass}");
        }

        $model = $modelClass::find($id);

        return $model ? $model->toArray() : null;
    }

    private function queryDatabase(TypeSchema $schema, array $params): array
    {
        $table = $schema->config['table'] ?? null;
        
        if (!$table) {
            throw new \Exception("Table not specified in config");
        }

        $query = DB::table($table);

        if (isset($params['search']) && $params['search']) {
            $searchableFields = collect($schema->fields)
                ->filter(fn($f) => $f->searchable ?? false)
                ->pluck('name')
                ->toArray();

            if ($searchableFields) {
                $query->where(function ($q) use ($searchableFields, $params) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$params['search']}%");
                    }
                });
            }
        }

        if (isset($params['sort']) && $params['sort']) {
            $direction = $params['direction'] ?? 'asc';
            $query->orderBy($params['sort'], $direction);
        }

        $perPage = $params['per_page'] ?? 15;
        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }

    private function showDatabase(TypeSchema $schema, mixed $id): ?array
    {
        $table = $schema->config['table'] ?? null;
        
        if (!$table) {
            throw new \Exception("Table not specified in config");
        }

        $primaryKey = $schema->config['primary_key'] ?? 'id';
        $record = DB::table($table)->where($primaryKey, $id)->first();

        return $record ? (array) $record : null;
    }

    private function querySushi(TypeSchema $schema, array $params): array
    {
        return [];
    }

    private function showSushi(TypeSchema $schema, mixed $id): ?array
    {
        return null;
    }

    private function queryApi(TypeSchema $schema, array $params): array
    {
        return [];
    }

    private function showApi(TypeSchema $schema, mixed $id): ?array
    {
        return null;
    }
}
