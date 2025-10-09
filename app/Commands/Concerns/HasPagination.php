<?php

namespace App\Commands\Concerns;

trait HasPagination
{
    protected int $page = 1;
    protected int $perPage = 50;
    protected string $sortBy = 'created_at';
    protected string $sortDir = 'desc';
    
    /**
     * Set pagination parameters from request or defaults
     */
    protected function setPaginationParams(array $params = []): void
    {
        $this->page = (int) ($params['page'] ?? request()->get('page', 1));
        $this->perPage = (int) ($params['per_page'] ?? request()->get('per_page', 50));
        $this->sortBy = $params['sort_by'] ?? request()->get('sort_by', 'created_at');
        $this->sortDir = $params['sort_dir'] ?? request()->get('sort_dir', 'desc');
        
        // Validate and sanitize
        $this->page = max(1, $this->page);
        $this->perPage = min(500, max(10, $this->perPage)); // Min 10, max 500
        $this->sortDir = in_array($this->sortDir, ['asc', 'desc']) ? $this->sortDir : 'desc';
    }
    
    /**
     * Apply pagination to a query builder
     */
    protected function applyPagination($query)
    {
        // Apply sorting
        if ($this->sortBy && method_exists($query->getModel(), $this->sortBy) === false) {
            $query->orderBy($this->sortBy, $this->sortDir);
        }
        
        // Get total count before pagination
        $total = $query->count();
        
        // Apply pagination
        $offset = ($this->page - 1) * $this->perPage;
        $items = $query->skip($offset)->take($this->perPage)->get();
        
        return [
            'items' => $items,
            'total' => $total,
        ];
    }
    
    /**
     * Build pagination metadata for response
     */
    protected function buildPaginationMeta(int $total): array
    {
        $lastPage = (int) ceil($total / $this->perPage);
        
        return [
            'current_page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_more' => $this->page < $lastPage,
            'from' => (($this->page - 1) * $this->perPage) + 1,
            'to' => min($this->page * $this->perPage, $total),
        ];
    }
    
    /**
     * Format paginated response
     */
    protected function paginatedResponse(array $items, int $total, string $component): array
    {
        return [
            'type' => 'paginated',
            'component' => $component,
            'data' => $items,
            'pagination' => $this->buildPaginationMeta($total),
        ];
    }
}
