<?php

namespace HollisLabs\UiBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use HollisLabs\UiBuilder\Services\DataSourceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataSourceController extends Controller
{
    public function __construct(
        private DataSourceResolver $resolver
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

    public function store(Request $request, string $alias): JsonResponse
    {
        try {
            $data = $request->all();
            $result = $this->resolver->create($alias, $data);

            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
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

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, string $alias, mixed $id): JsonResponse
    {
        try {
            $data = $request->all();
            $result = $this->resolver->update($alias, $id, $data);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function destroy(string $alias, mixed $id): JsonResponse
    {
        try {
            $this->resolver->delete($alias, $id);

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
