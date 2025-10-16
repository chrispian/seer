<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Modules\UiBuilder\app\Models\Page;
use Illuminate\Http\JsonResponse;

class UiPageController extends Controller
{
    public function show(string $key): JsonResponse
    {
        $page = Page::where('key', $key)->firstOrFail();

        return response()->json([
            'id' => $page->id,
            'key' => $page->key,
            'layout_tree_json' => $page->layout_tree_json ?? $page->config ?? [],
            'hash' => $page->hash,
            'version' => $page->version,
            'enabled' => $page->enabled ?? true,
            'module_key' => $page->module_key,
            'timestamp' => $page->updated_at->toIso8601String(),
        ]);
    }
}
