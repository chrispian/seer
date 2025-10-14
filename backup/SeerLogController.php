<?php

namespace App\Http\Controllers;

use App\Models\SeerLog;
use Illuminate\Http\Request;

class SeerLogController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'type' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'relationships' => ['nullable', 'array'],
        ]);

        $log = SeerLog::create([
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'obs',
            'tags' => $validated['tags'] ?? [],
            'relationships' => $validated['relationships'] ?? [],
        ]);

        return response()->json($log);
    }
}
