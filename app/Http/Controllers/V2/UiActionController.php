<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\V2\ActionAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UiActionController extends Controller
{
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:command,navigate',
            'command' => 'required_if:type,command|string',
            'params' => 'array',
            'url' => 'required_if:type,navigate|string',
        ]);

        $adapter = new ActionAdapter;

        try {
            $result = $adapter->execute($request->all());

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Action execution failed', [
                'type' => $request->input('type'),
                'command' => $request->input('command'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Action execution failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
