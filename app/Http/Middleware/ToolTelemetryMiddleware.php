<?php

namespace App\Http\Middleware;

use App\Services\Telemetry\CorrelationContext;
use App\Services\Telemetry\ToolTelemetry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ToolTelemetryMiddleware
{
    public function __construct(private ToolTelemetry $telemetry) {}

    public function handle(Request $request, Closure $next)
    {
        // Set up correlation context for tool calls
        if (!CorrelationContext::hasContext()) {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
            CorrelationContext::set($correlationId);
        }

        // Add request context
        CorrelationContext::addContext('request_method', $request->method());
        CorrelationContext::addContext('request_path', $request->path());
        CorrelationContext::addContext('user_agent', $request->userAgent());
        
        if ($request->ip()) {
            CorrelationContext::addContext('client_ip', $request->ip());
        }

        // Add tool-specific context based on route
        if (str_starts_with($request->path(), 'internal/')) {
            CorrelationContext::addContext('execution_context', 'internal_api');
            CorrelationContext::addContext('tool_invocation_type', 'direct');
        }

        $response = $next($request);

        // Add correlation ID to response headers for tracing
        if (CorrelationContext::hasContext()) {
            $response->headers->set('X-Correlation-ID', CorrelationContext::get());
        }

        return $response;
    }
}