<?php

namespace App\Commands\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait HandlesModelQueries
{
    protected function queryModel(string $modelClass, ?int $limit = null, ?array $orderBy = null): Collection
    {
        $query = $modelClass::query();
        
        $orderBy = $orderBy ?? $this->command?->default_sort;
        if ($orderBy) {
            $query->orderBy(
                $orderBy['field'] ?? 'created_at',
                $orderBy['direction'] ?? 'desc'
            );
        }
        
        $limit = $limit ?? $this->command?->pagination_default ?? 50;
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
    
    protected function queryModelWithFilters(
        string $modelClass,
        array $filters = [],
        ?int $limit = null,
        ?array $orderBy = null
    ): Collection {
        $query = $modelClass::query();
        
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        $orderBy = $orderBy ?? $this->command?->default_sort;
        if ($orderBy) {
            $query->orderBy(
                $orderBy['field'] ?? 'created_at',
                $orderBy['direction'] ?? 'desc'
            );
        }
        
        $limit = $limit ?? $this->command?->pagination_default ?? 50;
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
}
