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

        // Return the page config directly (layout_tree_json contains the PageConfig structure)
        $config = $page->layout_tree_json ?? $page->config ?? [];
        
        // Add metadata to the config
        $config['_meta'] = [
            'page_id' => $page->id,
            'hash' => $page->hash,
            'version' => $page->version,
            'enabled' => $page->enabled ?? true,
            'module_key' => $page->module_key,
            'timestamp' => $page->updated_at->toIso8601String(),
        ];

        return response()->json($config);
    }
}
