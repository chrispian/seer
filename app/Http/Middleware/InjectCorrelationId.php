<?php

namespace App\Http\Middleware;

use App\Services\Telemetry\CorrelationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class InjectCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or extract correlation ID
        $correlationId = $this->getOrGenerateCorrelationId($request);

        // Store in correlation context for the request lifecycle
        CorrelationContext::set($correlationId);

        // Add to request for downstream access
        $request->headers->set('X-Correlation-ID', $correlationId);

        // Process request with correlation context
        $response = $next($request);

        // Add correlation ID to response headers
        if ($response instanceof Response) {
            $response->headers->set('X-Correlation-ID', $correlationId);
        }

        return $response;
    }

    private function getOrGenerateCorrelationId(Request $request): string
    {
        // Check if client provided correlation ID
        $clientCorrelationId = $request->header('X-Correlation-ID');

        if ($clientCorrelationId && $this->isValidCorrelationId($clientCorrelationId)) {
            return $clientCorrelationId;
        }

        // Generate new correlation ID
        return (string) Str::uuid();
    }

    private function isValidCorrelationId(string $id): bool
    {
        // Validate UUID format and reasonable length
        return Str::isUuid($id) || (strlen($id) >= 8 && strlen($id) <= 128);
    }
}
