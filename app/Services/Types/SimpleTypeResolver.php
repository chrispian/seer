<?php

namespace App\Services\Types;

class SimpleTypeResolver extends TypeResolver
{
    protected $modelMap = [
        'Agent' => \App\Models\Agent::class,
        'Model' => \App\Models\AiModel::class,
        'Task' => \App\Models\OrchestrationTask::class,
        'Sprint' => \App\Models\OrchestrationSprint::class,
    ];

    public function show(string $alias, mixed $id): ?array
    {
        if (!isset($this->modelMap[$alias])) {
            throw new \Exception("Type alias '{$alias}' not found");
        }

        $modelClass = $this->modelMap[$alias];
        $model = $modelClass::find($id);

        return $model ? $model->toArray() : null;
    }

    public function query(string $alias, array $params = []): array
    {
        if (!isset($this->modelMap[$alias])) {
            throw new \Exception("Type alias '{$alias}' not found");
        }

        $modelClass = $this->modelMap[$alias];
        $query = $modelClass::query();

        // Apply search
        if (!empty($params['search'])) {
            $query->where('name', 'like', "%{$params['search']}%");
        }

        // Apply sorting
        $sortField = $params['sort'] ?? 'updated_at';
        $sortDirection = $params['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $params['per_page'] ?? 15;
        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }
}