<?php

namespace Modules\UiBuilder\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\AIModel;

class ModelDataSourceResolver
{
    public function query(array $params = []): array
    {
        $query = AIModel::query();

        if (isset($params['search']) && ! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model_id', 'like', "%{$search}%");
            });
        }

        if (isset($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $field => $value) {
                if (in_array($field, ['status', 'model_id']) && ! empty($value)) {
                    $query->where($field, $value);
                }
            }
        }

        if (isset($params['sort']) && is_array($params['sort'])) {
            $sortField = $params['sort']['field'] ?? 'updated_at';
            $sortDirection = $params['sort']['direction'] ?? 'desc';

            if (in_array($sortField, ['name', 'updated_at'])) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $perPage = $params['pagination']['per_page'] ?? 15;
        $page = $params['pagination']['page'] ?? 1;

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginated->items();

        $transformedData = array_map(function ($model) {

            return [
                'id' => $model->id,
                'name' => $model->name,
                'model_id' => $model->model_id,
            ];
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

    public function getCapabilities(): array
    {
        return [
            'searchable' => ['name', 'model_id'],
            'filterable' => ['status', 'model_id'],
            'sortable' => ['name', 'updated_at', 'model_id'],
        ];
    }
}
