<?php

declare(strict_types=1);

namespace App\Services\Security\Guards;

use App\Services\Security\PolicyRegistry;
use App\Services\Security\RiskScorer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Network request security validation and execution guard.
 *
 * Validates HTTP requests through multiple security layers:
 * - URL parsing and validation
 * - SSRF detection (private IPs, localhost)
 * - Domain allowlist enforcement
 * - HTTPS requirement checking
 * - Risk assessment (method, body, headers)
 * - Request/response size limits (1MB request, 10MB response)
 *
 * Can validate-only or execute requests with enforced guards.
 *
 * Example:
 *     $guard = new NetworkGuard($policyRegistry, $riskScorer);
 *     $result = $guard->executeRequest('https://api.example.com/data', [
 *         'method' => 'POST',
 *         'json' => ['key' => 'value']
 *     ]);
 *
 * @see PolicyRegistry For domain allowlist policies
 * @see RiskScorer For network operation risk assessment
 */
class NetworkGuard
{
    private const MAX_REQUEST_SIZE = 1 * 1024 * 1024; // 1MB

    private const MAX_RESPONSE_SIZE = 10 * 1024 * 1024; // 10MB

    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

    /**
     * Validate a network request through multiple security layers.
     *
     * Validation pipeline:
     * 1. URL parsing (scheme, host, port, path, query)
     * 2. SSRF detection (private IPs, localhost blocking)
     * 3. Domain allowlist check
     * 4. HTTPS enforcement (with warnings)
     * 5. Risk assessment (method, body, headers)
     * 6. Request size validation (1MB limit)
     *
     * @param string $url Request URL (e.g., 'https://api.example.com/data')
     * @param array<string, mixed> $options Request options:
     *        - 'method' (string): HTTP method (GET, POST, PUT, DELETE, PATCH)
     *        - 'headers' (array): Request headers
     *        - 'body' (string): Raw request body
     *        - 'json' (array): JSON request body
     *        - 'allow_http' (bool): Allow HTTP in local environment
     * @return array{
     *     allowed: bool,
     *     parsed_url: array|null,
     *     policy_decision: array|null,
     *     risk_assessment: array|null,
     *     violations: string[],
     *     warnings: string[]
     * } Validation result
     *
     * Example - Allowed request:
     *     $result = $guard->validateRequest('https://api.github.com/repos');
     *     // ['allowed' => true, 'parsed_url' => [...], ...]
     *
     * Example - SSRF blocked:
     *     $result = $guard->validateRequest('http://127.0.0.1:8080/admin');
     *     // ['allowed' => false, 'violations' => ['Localhost access blocked (SSRF prevention)'], ...]
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
     * Execute HTTP request with security guards enforced.
     *
     * Validates request first, then executes with:
     * - Timeout limits (max 60s)
     * - Redirect limits (max 3)
     * - SSL verification enforced
     * - Response size limits (10MB, truncates if exceeded)
     *
     * @param string $url Request URL
     * @param array<string, mixed> $options Request options (see validateRequest)
     * @return array{
     *     success: bool,
     *     blocked?: bool,
     *     violations?: string[],
     *     status: int,
     *     headers?: array,
     *     body?: string,
     *     warnings?: string[],
     *     error?: string
     * } Execution result
     *
     * Example - Successful request:
     *     $result = $guard->executeRequest('https://api.github.com/repos', ['method' => 'GET']);
     *     // ['success' => true, 'status' => 200, 'body' => '...', ...]
     *
     * Example - Blocked request:
     *     $result = $guard->executeRequest('http://localhost/admin');
     *     // ['success' => false, 'blocked' => true, 'violations' => [...], 'status' => 0]
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
     * Parse and validate URL structure.
     *
     * Extracts URL components with defaults:
     * - scheme: defaults to 'http'
     * - host: required
     * - port: optional
     * - path: defaults to '/'
     * - query: optional
     *
     * @param string $url URL to parse
     * @return array{valid: bool, scheme?: string, host?: string, port?: int|null, path?: string, query?: string|null, error?: string}
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
     * Detect SSRF (Server-Side Request Forgery) attacks.
     *
     * Blocks access to:
     * - Localhost variations (localhost, 127.0.0.1, ::1, 0.0.0.0)
     * - Private IP ranges (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, etc.)
     * - Domains that resolve to private IPs
     *
     * Uses PHP's FILTER_FLAG_NO_PRIV_RANGE and FILTER_FLAG_NO_RES_RANGE.
     *
     * @param array{host: string} $parsed Parsed URL components
     * @return array{safe: bool, reason?: string} Safety result
     *
     * Example - Localhost blocked:
     *     detectSSRF(['host' => '127.0.0.1']);
     *     // ['safe' => false, 'reason' => 'Localhost access blocked (SSRF prevention)']
     *
     * Example - Private IP blocked:
     *     detectSSRF(['host' => '192.168.1.1']);
     *     // ['safe' => false, 'reason' => 'Private IP address blocked (SSRF prevention)']
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
     * Check if URL scheme is secure (HTTPS).
     *
     * Allows:
     * - HTTPS (always)
     * - HTTP in local environment if 'allow_http' option set
     *
     * @param string $scheme URL scheme ('http' or 'https')
     * @param array<string, mixed> $options Request options (see validateRequest)
     * @return bool True if secure
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
     * Validate symlink target against path policies.
     *
     * NOTE: This method appears to be unused in NetworkGuard context.
     * Likely copied from FilesystemGuard. Consider removing.
     *
     * @param string $symlinkPath Original symlink path
     * @param string $targetPath Symlink target path
     * @return array{safe: bool, reason?: string} Safety result
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
     * Detect path traversal attempts.
     *
     * NOTE: This method appears to be unused in NetworkGuard context.
     * Likely copied from FilesystemGuard. Consider removing.
     *
     * @param string $normalized Normalized path
     * @param string $original Original path input
     * @return array{safe: bool, reason?: string} Safety result
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
