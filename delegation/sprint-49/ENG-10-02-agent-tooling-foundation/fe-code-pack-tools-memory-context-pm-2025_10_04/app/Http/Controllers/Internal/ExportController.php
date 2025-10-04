<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Tools\ExportGenerateTool;

class ExportController extends Controller
{
    public function __construct(protected ExportGenerateTool $tool) {}

    public function generate(Request $request)
    {
        $payload = $request->validate([
            'entity' => 'nullable|string',
            'query_ref' => 'nullable|string',
            'template' => 'nullable|string',
            'format' => 'required|string|in:md,txt,json,csv,xlsx,pdf',
            'params' => 'array',
        ]);
        return response()->json($this->tool->run($payload));
    }
}
