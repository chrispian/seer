<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Types\TypeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypesController extends Controller
{
    public function __construct(
        private TypeResolver $resolver
    ) {}

    public function query(Request $request, string $alias): JsonResponse
    {
        try {
            $params = $request->only(['search', 'sort', 'direction', 'filters', 'per_page']);
            $result = $this->resolver->query($alias, $params);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function show(string $alias, mixed $id): JsonResponse
    {
        try {
            $result = $this->resolver->show($alias, $id);

            if (!$result) {
                return response()->json([
                    'error' => 'Record not found',
                ], 404);
            }

            return response()->json([
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
