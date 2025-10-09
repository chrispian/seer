<?php

namespace App\Services\Security\Guards;

use App\Services\Security\PolicyRegistry;
use App\Services\Security\RiskScorer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetworkGuard
{
    private const MAX_REQUEST_SIZE = 1 * 1024 * 1024; // 1MB

    private const MAX_RESPONSE_SIZE = 10 * 1024 * 1024; // 10MB

    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

    /**
     * Validate network request
     */
    public function validateRequest(string $url, array $options = []): array
    {
        $validation = [
            'allowed' => false,
            'parsed_url' => null,
            'policy_decision' => null,
            'risk_assessment' => null,
            'violations' => [],
            'warnings' => [],
        ];

        // 1. Parse URL
        $parsed = $this->parseUrl($url);
        if (! $parsed['valid']) {
            $validation['violations'][] = $parsed['error'];

            return $validation;
        }

        $validation['parsed_url'] = $parsed;

        // 2. Check for SSRF (private IPs)
        $ssrfCheck = $this->detectSSRF($parsed);
        if (! $ssrfCheck['safe']) {
            $validation['violations'][] = $ssrfCheck['reason'];

            return $validation;
        }

        // 3. Policy check (domain allowlist)
        $policyDecision = $this->policyRegistry->isDomainAllowed($parsed['host']);
        $validation['policy_decision'] = $policyDecision;

        if (! $policyDecision['allowed']) {
            $validation['violations'][] = $policyDecision['reason'];

            return $validation;
        }

        // 4. Enforce HTTPS for sensitive operations
        if (! $this->isSecureScheme($parsed['scheme'], $options)) {
            $validation['warnings'][] = 'Using non-HTTPS connection';
        }

        // 5. Risk assessment
        $context = [
            'method' => $options['method'] ?? 'GET',
            'has_body' => isset($options['body']) || isset($options['json']),
            'headers' => $options['headers'] ?? [],
        ];

        $risk = $this->riskScorer->scoreNetworkOperation($parsed['host'], $context);
        $validation['risk_assessment'] = $risk;

        if ($risk['requires_approval']) {
            $validation['violations'][] = "Request requires approval (risk: {$risk['score']})";

            return $validation;
        }

        // 6. Validate request/response size
        if (isset($options['body'])) {
            $bodySize = strlen($options['body']);
            if ($bodySize > self::MAX_REQUEST_SIZE) {
                $validation['violations'][] = "Request body exceeds limit: {$bodySize} > ".self::MAX_REQUEST_SIZE;

                return $validation;
            }
        }

        $validation['allowed'] = true;

        return $validation;
    }

    /**
     * Execute HTTP request with guards
     */
    public function executeRequest(string $url, array $options = []): array
    {
        $validation = $this->validateRequest($url, $options);

        if (! $validation['allowed']) {
            return [
                'success' => false,
                'blocked' => true,
                'violations' => $validation['violations'],
                'status' => 0,
            ];
        }

        try {
            $method = strtoupper($options['method'] ?? 'GET');
            $timeout = min($options['timeout'] ?? 30, 60); // Max 60s

            // Build HTTP client with limits
            $http = Http::timeout($timeout)
                ->withOptions([
                    'max_redirects' => 3,
                    'verify' => true, // Enforce SSL verification
                ]);

            // Add headers if provided
            if (isset($options['headers'])) {
                $http = $http->withHeaders($options['headers']);
            }

            // Execute request
            $response = match ($method) {
                'GET' => $http->get($url),
                'POST' => $http->post($url, $options['json'] ?? $options['body'] ?? []),
                'PUT' => $http->put($url, $options['json'] ?? $options['body'] ?? []),
                'DELETE' => $http->delete($url),
                'PATCH' => $http->patch($url, $options['json'] ?? $options['body'] ?? []),
                default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            // Check response size
            $body = $response->body();
            if (strlen($body) > self::MAX_RESPONSE_SIZE) {
                Log::warning('Response size exceeds limit', [
                    'url' => $url,
                    'size' => strlen($body),
                    'limit' => self::MAX_RESPONSE_SIZE,
                ]);
                $body = substr($body, 0, self::MAX_RESPONSE_SIZE);
            }

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $body,
                'warnings' => $validation['warnings'],
            ];

        } catch (\Exception $e) {
            Log::error('Network request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse and validate URL
     */
    private function parseUrl(string $url): array
    {
        $parsed = parse_url($url);

        if ($parsed === false || ! isset($parsed['host'])) {
            return [
                'valid' => false,
                'error' => 'Invalid URL format',
            ];
        }

        return [
            'valid' => true,
            'scheme' => $parsed['scheme'] ?? 'http',
            'host' => $parsed['host'],
            'port' => $parsed['port'] ?? null,
            'path' => $parsed['path'] ?? '/',
            'query' => $parsed['query'] ?? null,
        ];
    }

    /**
     * Detect SSRF attacks (private IPs, localhost)
     */
    private function detectSSRF(array $parsed): array
    {
        $host = $parsed['host'];

        // Check localhost variations
        $localhostPatterns = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
        if (in_array($host, $localhostPatterns)) {
            return [
                'safe' => false,
                'reason' => 'Localhost access blocked (SSRF prevention)',
            ];
        }

        // Check if it's an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Check for private IP ranges
            if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return [
                    'safe' => false,
                    'reason' => 'Private IP address blocked (SSRF prevention)',
                ];
            }
        } else {
            // Resolve domain to IP and check
            $ip = gethostbyname($host);
            if ($ip !== $host && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return [
                    'safe' => false,
                    'reason' => "Domain resolves to private IP (SSRF prevention): {$ip}",
                ];
            }
        }

        return ['safe' => true];
    }

    /**
     * Check if scheme is secure
     */
    private function isSecureScheme(string $scheme, array $options): bool
    {
        if ($scheme === 'https') {
            return true;
        }

        // Allow HTTP for localhost in development
        if (app()->environment('local') && isset($options['allow_http'])) {
            return true;
        }

        return false;
    }

    /**
     * Validate symlink target
     */
    private function validateSymlink(string $symlinkPath, string $targetPath): array
    {
        // Ensure symlink doesn't escape allowed directories
        $targetPolicy = $this->policyRegistry->isPathAllowed($targetPath, 'read');

        if (! $targetPolicy['allowed']) {
            return [
                'safe' => false,
                'reason' => "Symlink target outside allowed paths: {$targetPath}",
            ];
        }

        return ['safe' => true];
    }

    /**
     * Detect path traversal in original vs normalized path
     */
    private function detectPathTraversal(string $normalized, string $original): array
    {
        // If original contains ../ but normalized doesn't start with allowed path
        if (str_contains($original, '../') || str_contains($original, '..\\')) {
            Log::warning('Path traversal attempt detected', [
                'original' => $original,
                'normalized' => $normalized,
            ]);

            // Check if normalized path is allowed
            $policy = $this->policyRegistry->isPathAllowed($normalized, 'read');
            if (! $policy['allowed']) {
                return [
                    'safe' => false,
                    'reason' => 'Path traversal to restricted directory',
                ];
            }
        }

        return ['safe' => true];
    }
}
