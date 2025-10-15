<?php

namespace App\Services\V2;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Builder;

class AgentDataSourceResolver
{
    public function query(array $params = []): array
    {
        $query = Agent::query()->with('agentProfile');

        if (isset($params['search']) && ! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if (isset($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $field => $value) {
                if (in_array($field, ['status', 'agent_profile_id']) && ! empty($value)) {
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

        $transformedData = array_map(function ($agent) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'role' => $agent->designation,
                'provider' => $agent->agentProfile->provider ?? null,
                'model' => $agent->agentProfile->model ?? null,
                'status' => $agent->status,
                'updated_at' => $agent->updated_at->toIso8601String(),
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
            'searchable' => ['name', 'designation'],
            'filterable' => ['status', 'agent_profile_id'],
            'sortable' => ['name', 'updated_at'],
        ];
    }
}
