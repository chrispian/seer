<?php

namespace Modules\UiBuilder\Services;

use Modules\UiBuilder\Models\FeUiDatasource;
use Illuminate\Support\Facades\Cache;

class GenericDataSourceResolver
{
    public function query(string $alias, array $params = []): array
    {
        $config = $this->getConfig($alias);

        $modelClass = $config['model'];
        $query = $modelClass::query();

        if (!empty($config['with'])) {
            $query->with($config['with']);
        }

        if (!empty($config['scopes'])) {
            foreach ($config['scopes'] as $scope) {
                $query->{$scope}();
            }
        }

        if (isset($params['search']) && !empty($params['search'])) {
            $search = $params['search'];
            $searchable = $config['capabilities']['searchable'] ?? [];

            if (!empty($searchable)) {
                $query->where(function ($q) use ($searchable, $search) {
                    foreach ($searchable as $field) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                });
            }
        }

        if (isset($params['filters']) && is_array($params['filters'])) {
            $filterable = $config['capabilities']['filterable'] ?? [];

            foreach ($params['filters'] as $field => $value) {
                if (in_array($field, $filterable) && !empty($value)) {
                    $query->where($field, $value);
                }
            }
        }

        if (isset($params['sort']) && is_array($params['sort'])) {
            $sortField = $params['sort']['field'] ?? 'updated_at';
            $sortDirection = $params['sort']['direction'] ?? 'desc';
            $sortable = $config['capabilities']['sortable'] ?? [];

            if (in_array($sortField, $sortable)) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $defaultSort = $config['default_sort'] ?? ['updated_at', 'desc'];
            $query->orderBy($defaultSort[0], $defaultSort[1]);
        }

        $perPage = $params['pagination']['per_page'] ?? 15;
        $page = $params['pagination']['page'] ?? 1;
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginated->items();
        $transformedData = array_map(function ($item) use ($config) {
            return $this->transformItem($item, $config['transform']);
        }, $data);

        return [
            'data' => $transformedData,
            'meta' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'last_page' => $paginated->lastPage(),
            ],
            'hash' => hash('sha256', json_encode($transformedData)),
        ];
    }

    protected function transformItem($item, array $transform): array
    {
        $result = [];

        foreach ($transform as $outputKey => $config) {
            if (is_string($config)) {
                $result[$outputKey] = data_get($item, $config);
            } elseif (is_array($config)) {
                $source = $config['source'] ?? $outputKey;
                $value = data_get($item, $source);

                if (isset($config['format'])) {
                    $value = $this->formatValue($value, $config['format']);
                }

                $result[$outputKey] = $value;
            }
        }

        return $result;
    }

    protected function formatValue($value, string $format)
    {
        return match ($format) {
            'iso8601' => $value?->toIso8601String(),
            'date' => $value?->toDateString(),
            'avatar_url' => $value ? asset("storage/{$value}") : null,
            default => $value,
        };
    }

    protected function getConfig(string $alias): array
    {
        return Cache::remember("datasource.{$alias}", 3600, function () use ($alias) {
            $datasource = FeUiDatasource::where('alias', $alias)->firstOrFail();

            return [
                'model' => $datasource->handler ?? $datasource->model_class,
                'with' => $datasource->default_params_json['with'] ?? [],
                'scopes' => $datasource->default_params_json['scopes'] ?? [],
                'default_sort' => $datasource->default_params_json['default_sort'] ?? ['updated_at', 'desc'],
                'capabilities' => $datasource->capabilities_json ?? [],
                'transform' => $datasource->schema_json['transform'] ?? [],
            ];
        });
    }

    public function getCapabilities(string $alias): array
    {
        $config = $this->getConfig($alias);

        return $config['capabilities'];
    }

    public function create(string $alias, array $data): array
    {
        $config = $this->getConfig($alias);
        $modelClass = $config['model'];

        $instance = $modelClass::create($data);

        return $this->transformItem($instance, $config['transform']);
    }

    public function clearCache(string $alias): void
    {
        Cache::forget("datasource.{$alias}");
    }
}
