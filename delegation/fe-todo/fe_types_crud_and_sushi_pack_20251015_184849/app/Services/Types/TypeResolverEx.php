<?php

namespace App\Services\Types;

use App\Services\Types\TypeRegistry;

class TypeResolverEx
{
    public function __construct(protected TypeRegistry $registry, protected AdapterManager $adapters) {}

    public function query(string $alias, array $params = []): array
    {
        $schema = $this->registry->get($alias);
        $adapter = $this->adapters->for($alias, $schema?->options ?? []);
        $res = $adapter->query($alias, $params);
        if ($schema) $res['schema']['fields'] = array_map(fn($f)=>['name'=>$f->name,'type'=>$f->type], $schema->fields);
        return $res;
    }

    public function find(string $alias, $id): ?array
    {
        $schema = $this->registry->get($alias);
        $adapter = $this->adapters->for($alias, $schema?->options ?? []);
        return $adapter->find($alias, $id);
    }
}
