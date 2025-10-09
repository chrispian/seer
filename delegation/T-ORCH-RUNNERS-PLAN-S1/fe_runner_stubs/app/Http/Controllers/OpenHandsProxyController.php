<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OpenHandsProxyController extends Controller
{
    public function attach(Request $request, string $conversationId)
    {
        // TODO: upgrade to WS or stream SSE as a proxy to OpenHands session
        return response()->json(['status'=>'stub']);
    }

    public function action(Request $request, string $conversationId)
    {
        // TODO: accept actions and forward to runner
        return response()->json(['status'=>'stub']);
    }
}
