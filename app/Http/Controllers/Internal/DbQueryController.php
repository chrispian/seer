<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DbQueryService;

class DbQueryController extends Controller
{
    public function __construct(protected DbQueryService $svc) {}

    public function query(Request $request)
    {
        $payload = $request->validate([
            'entity' => 'required|string',
            'filters' => 'array',
            'search' => 'nullable|string',
            'order_by' => 'array',
            'limit' => 'integer|min:1|max:500',
            'offset' => 'integer|min:0',
        ]);
        return response()->json($this->svc->run($payload));
    }
}
