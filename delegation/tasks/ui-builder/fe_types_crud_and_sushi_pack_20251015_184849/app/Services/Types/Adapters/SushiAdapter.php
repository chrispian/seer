<?php

namespace App\Services\Types\Adapters;

use Illuminate\Database\Eloquent\Model;

class SushiAdapter implements TypesAdapterInterface
{
    public function __construct(protected string $modelClass) {}

    public function query(string $alias, array $params = []): array
    {
        /** @var Model $model */
        $model = new $this->modelClass;
        $q = $model->newQuery();

        if ($search = ($params['q'] ?? null)) {
            $cols = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
            $cols = array_filter($cols, fn($c) => !in_array($c, ['id','created_at','updated_at']));
            $q->where(function ($qq) use ($cols, $search) { foreach ($cols as $c) $qq->orWhere($c, 'like', "%$search%"); });
        }

        if ($sort = ($params['sort'] ?? null)) {
            $q->orderBy($sort, $params['dir'] ?? 'asc');
        }

        $per = max(1, min(100, (int)($params['per_page'] ?? 10)));
        $page = $q->paginate($per);

        return [
            'data' => array_map(fn($m)=>$m->toArray(), $page->items()),
            'meta' => ['total'=>$page->total(),'page'=>$page->currentPage(),'per_page'=>$page->perPage()],
            'schema' => ['alias'=>$alias,'adapter'=>'sushi','model'=>$this->modelClass]
        ];
    }

    public function find(string $alias, $id): ?array
    {
        $row = $this->modelClass::query()->find($id);
        return $row ? $row->toArray() : null;
    }
}
