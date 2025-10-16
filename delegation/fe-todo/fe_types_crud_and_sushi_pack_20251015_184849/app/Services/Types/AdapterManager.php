<?php

namespace App\Services\Types;

use App\Services\Types\Adapters\{TypesAdapterInterface, SushiAdapter};

class AdapterManager
{
    public function for(string $alias, array $options): TypesAdapterInterface
    {
        $adapter = $options['adapter'] ?? 'demo';
        if ($adapter === 'sushi') {
            $model = $options['model'] ?? null;
            abort_unless($model && class_exists($model), 500, 'Sushi adapter missing model class.');
            return new SushiAdapter($model);
        }
        return new class implements TypesAdapterInterface {
            public function query(string $alias, array $params = []): array {
                return ['data'=>[], 'meta'=>['total'=>0,'page'=>1,'per_page'=>10], 'schema'=>['alias'=>$alias,'adapter'=>'demo']];
            }
            public function find(string $alias, $id): ?array { return null; }
        };
    }
}
