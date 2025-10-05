<?php

namespace App\Http\Middleware;

use App\Services\Telemetry\CorrelationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatTelemetryMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Add chat-specific context for telemetry
        if ($request->routeIs('api.*') && str_contains($request->path(), 'messages')) {
            CorrelationContext::addContext('service', 'chat');
            CorrelationContext::addContext('endpoint', $request->path());
            CorrelationContext::addContext('method', $request->method());

            // Add user context if available (privacy-safe)
            if ($request->user()) {
                CorrelationContext::addContext('user_id', $request->user()->id);
            }

            // Add IP hash for debugging without exposing actual IP
            if ($request->ip()) {
                CorrelationContext::addContext('ip_hash', hash('sha256', $request->ip()));
            }

            // Add user agent hash for client debugging
            if ($request->userAgent()) {
                CorrelationContext::addContext('user_agent_hash', hash('sha256', $request->userAgent()));
            }
        }

        return $next($request);
    }
}
