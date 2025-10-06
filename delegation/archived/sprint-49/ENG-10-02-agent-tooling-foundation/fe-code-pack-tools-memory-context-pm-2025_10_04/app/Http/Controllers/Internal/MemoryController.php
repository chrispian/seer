<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Tools\MemorySearchTool;
use App\Tools\MemoryWriteTool;
use Illuminate\Http\Request;

class MemoryController extends Controller
{
    public function __construct(
        protected MemoryWriteTool $write,
        protected MemorySearchTool $search
    ) {}

    public function write(Request $request)
    {
        $payload = $request->validate([
            'agent_id' => 'nullable|string',
            'topic' => 'required|string',
            'body' => 'required|string',
            'kind' => 'required|string|in:decision,observation,risk,todo,context',
            'scope' => 'required|string|in:task,project,global',
            'ttl_days' => 'nullable|integer|min:1|max:365',
            'links' => 'array',
            'tags' => 'array',
        ]);

        return response()->json($this->write->run($payload));
    }

    public function search(Request $request)
    {
        $payload = $request->validate([
            'q' => 'nullable|string',
            'kinds' => 'array',
            'scope' => 'nullable|string|in:any,task,project,global',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        return response()->json($this->search->run($payload));
    }
}
