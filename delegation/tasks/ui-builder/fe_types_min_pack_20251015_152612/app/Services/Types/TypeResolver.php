<?php

namespace App\Services\Types;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class TypeResolver
{
    /**
     * Resolve list/search for a given type alias.
     * Replace this demo with real sources: DB/Eloquent, Sushi, or external API.
     */
    public function query(string $alias, array $params = []): array
    {
        // DEMO DATA (replace)
        $data = match ($alias) {
            'Invoice' => [
                ['id'=>1,'number'=>'INV-1001','amount'=>199.99,'status'=>'paid','issued_at'=>'2025-01-02'],
                ['id'=>2,'number'=>'INV-1002','amount'=>55.50,'status'=>'pending','issued_at'=>'2025-01-05'],
                ['id'=>3,'number'=>'INV-1003','amount'=>1200.00,'status'=>'void','issued_at'=>'2025-01-10'],
            ],
            default => [],
        };

        // Simple filtering
        if ($q = ($params['q'] ?? null)) {
            $data = array_values(array_filter($data, fn($row) => stripos(json_encode($row), $q) !== false));
        }

        // Pagination
        $page = max(1, (int)($params['page'] ?? 1));
        $per  = max(1, min(100, (int)($params['per_page'] ?? 10)));
        $total = count($data);
        $chunks = array_chunk($data, $per);
        $pageData = $chunks[$page-1] ?? [];

        return [
            'data' => $pageData,
            'meta' => ['total'=>$total, 'page'=>$page, 'per_page'=>$per],
            'schema' => ['alias' => $alias] // Optionally embed the type schema
        ];
    }

    /** Resolve detail by id (demo) */
    public function find(string $alias, $id): ?array
    {
        $result = $this->query($alias, ['page'=>1,'per_page'=>1000])['data'] ?? [];
        foreach ($result as $row) {
            if ((string)($row['id'] ?? '') === (string)$id) return $row;
        }
        return null;
    }
}
