<?php

namespace App\Services\Types\Adapters;

interface TypesAdapterInterface
{
    public function query(string $alias, array $params = []): array;
    public function find(string $alias, $id): ?array;
}
