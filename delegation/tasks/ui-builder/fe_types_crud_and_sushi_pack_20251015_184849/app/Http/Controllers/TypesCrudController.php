<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Types\GeneratedTypeLocator;
use Symfony\Component\HttpFoundation\Response;

class TypesCrudController
{
    public function __construct(protected GeneratedTypeLocator $locator) {}

    public function index(Request $request, string $alias)
    {
        [$modelClass, $resourceClass] = $this->locator->resolveClasses($alias);
        $query = $modelClass::query();

        if ($q = $request->get('q')) {
            $query->where(function ($qq) use ($modelClass, $q) {
                // naive, try common columns
                foreach (['name','title','number'] as $col) {
                    if (\Schema::hasColumn((new $modelClass)->getTable(), $col)) {
                        $qq->orWhere($col, 'like', "%$q%");
                    }
                }
            });
        }

        if ($sort = $request->get('sort')) {
            $query->orderBy($sort, $request->get('dir','asc'));
        } else {
            $query->latest('id');
        }

        $per = max(1, min(100, (int) $request->get('per_page', 15)));
        $page = $query->paginate($per);

        return $resourceClass::collection($page);
    }

    public function store(string $alias)
    {
        [$modelClass, $resourceClass, $createRequestClass] = $this->locator->resolveClasses($alias, true);
        $request = app($createRequestClass);
        $data = $request->validated();

        $model = new $modelClass();
        $model->fill($data)->save();

        return new $resourceClass($model);
    }

    public function show(string $alias, $id)
    {
        [$modelClass, $resourceClass] = $this->locator->resolveClasses($alias);
        $model = $modelClass::findOrFail($id);
        return new $resourceClass($model);
    }

    public function update(string $alias, $id)
    {
        [$modelClass, $resourceClass, $_, $updateRequestClass] = $this->locator->resolveClasses($alias, true);
        $request = app($updateRequestClass);
        $data = $request->validated();

        $model = $modelClass::findOrFail($id);
        $model->fill($data)->save();

        return new $resourceClass($model);
    }

    public function destroy(string $alias, $id)
    {
        [$modelClass] = $this->locator->resolveClasses($alias);
        $model = $modelClass::findOrFail($id);
        $model->delete();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
