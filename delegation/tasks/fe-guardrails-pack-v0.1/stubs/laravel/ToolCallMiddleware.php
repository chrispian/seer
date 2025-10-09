<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Security\PolicyRegistry;
use App\Services\Audit\AuditLog;

class ToolCallMiddleware
{
    public function __construct(
        private PolicyRegistry $policies,
        private AuditLog $audit
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $tool = $request->input('tool.name');
        $payload = $request->input('tool.payload', []);

        $decision = $this->policies->evaluate($tool, $payload);

        $this->audit->write([
            'event' => 'tool.preflight',
            'tool' => $tool,
            'decision' => $decision,
            'ts' => now()->toIso8601String(),
        ]);

        if ($decision['action'] === 'deny') {
            abort(403, 'Policy denied: ' . $decision['reason']);
        }

        if ($decision['action'] === 'approve_required') {
            // Emit event for UI approval and halt with 202 + token reference
            return response()->json(['status' => 'pending_approval', 'token' => $decision['token']], 202);
        }

        $response = $next($request);

        $this->audit->write([
            'event' => 'tool.post',
            'tool' => $tool,
            'status' => $response->status(),
            'ts' => now()->toIso8601String(),
        ]);

        return $response;
    }
}
