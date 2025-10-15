<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\FeUiPage;
use Illuminate\Http\JsonResponse;

class UiPageController extends Controller
{
    public function show(string $key): JsonResponse
    {

        $page = FeUiPage::where('key', $key)->first();

        if (! $page) {
            return response()->json([
                'error' => 'Page not found',
                'message' => "Page with key '{$key}' does not exist",
            ], 404);
        }

        return response()->json([
            'id' => $page->id,
            'key' => $page->key,
            'config' => $page->config,
            'hash' => $page->hash,
            'version' => $page->version,
            'timestamp' => $page->updated_at->toIso8601String(),
        ]);
    }
}
