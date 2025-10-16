<?php

namespace Modules\UiBuilder\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeUiDatasource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UiDataSourceController extends Controller
{
    public function query(Request $request, string $alias): JsonResponse
    {
        $datasource = FeUiDatasource::where('alias', $alias)->first();

        if (! $datasource) {
            return response()->json([
                'error' => 'DataSource not found',
                'message' => "DataSource with alias '{$alias}' does not exist",
            ], 404);
        }

        $resolverClass = $datasource->resolver_class;

        if (! class_exists($resolverClass)) {
            return response()->json([
                'error' => 'Resolver not found',
                'message' => "Resolver class '{$resolverClass}' does not exist",
            ], 500);
        }

        $resolver = new $resolverClass;

        $params = [
            'search' => $request->input('search'),
            'filters' => $request->input('filters', []),
            'sort' => $request->input('sort', []),
            'pagination' => [
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 15),
            ],
        ];

        try {
            $result = $resolver->query($params);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('DataSource query failed', [
                'alias' => $alias,
                'resolver' => $resolverClass,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Query failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
