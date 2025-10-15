<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\V2\GenericDataSourceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataSourceController extends Controller
{
    public function __construct(
        private GenericDataSourceResolver $resolver
    ) {}

    public function query(Request $request, string $alias): JsonResponse
    {
        try {
            $params = [
                'search' => $request->input('search'),
                'filters' => $request->input('filters', []),
                'sort' => $request->input('sort'),
                'pagination' => [
                    'page' => $request->input('page', 1),
                    'per_page' => $request->input('per_page', 15),
                ],
            ];

            $result = $this->resolver->query($alias, $params);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function capabilities(string $alias): JsonResponse
    {
        try {
            $capabilities = $this->resolver->getCapabilities($alias);

            return response()->json([
                'capabilities' => $capabilities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
