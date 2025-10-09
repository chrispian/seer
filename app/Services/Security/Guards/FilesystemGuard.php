<?php

declare(strict_types=1);

namespace App\Services\Security\Guards;

use App\Services\Security\PolicyRegistry;
use App\Services\Security\RiskScorer;
use Illuminate\Support\Facades\Log;

/**
 * Filesystem path security validation and sanitization guard.
 *
 * Validates filesystem operations through multiple security layers:
 * - Path normalization (tilde expansion, realpath resolution)
 * - Path traversal detection (../, null bytes)
 * - Symlink validation (target path policy checking)
 * - Policy enforcement (path allowlist checking)
 * - Risk assessment (operation risk scoring)
 * - File size limits (write operation size checks)
 *
 * Example:
 *     $guard = new FilesystemGuard($policyRegistry, $riskScorer);
 *     $result = $guard->validateOperation('/var/log/app.log', 'read');
 *     if ($result['allowed']) {
 *         $content = file_get_contents($result['normalized_path']);
 *     }
 *
 * @see PolicyRegistry For path allowlist policies
 * @see RiskScorer For file operation risk assessment
 */
class FilesystemGuard
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB default

    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

    /**
     * Validate a filesystem operation through multiple security layers.
     *
     * Validation pipeline:
     * 1. Path normalization (tilde expansion, realpath)
     * 2. Path traversal detection (../, null bytes)
     * 3. Symlink validation (if path is symlink)
     * 4. Policy check (path allowlist)
     * 5. Risk assessment (may require approval)
     * 6. File size check (write operations only)
     *
     * @param string $path Filesystem path to validate (e.g., '/var/log/app.log', '~/config.json')
     * @param string $operation Operation type ('read', 'write', 'delete', etc.)
     * @param array<string, mixed> $context Validation context:
     *        - 'size' (int): File size for write operations
     *        - 'max_size' (int): Override default MAX_FILE_SIZE (10MB)
     *        - Additional risk scoring context
     * @return array{
     *     allowed: bool,
     *     normalized_path: string|null,
     *     policy_decision: array|null,
     *     risk_assessment: array|null,
     *     violations: string[],
     *     warnings: string[]
     * } Validation result
     *
     * Example - Read operation:
     *     $result = $guard->validateOperation('/var/log/app.log', 'read');
     *     // ['allowed' => true, 'normalized_path' => '/var/log/app.log', ...]
     *
     * Example - Write with size check:
     *     $result = $guard->validateOperation('~/data.json', 'write', ['size' => 5000000]);
     *     // ['allowed' => true, ...] (under 10MB limit)
     *
     * Example - Path traversal blocked:
     *     $result = $guard->validateOperation('../../../etc/passwd', 'read');
     *     // ['allowed' => false, 'violations' => ['Path traversal patterns (..) not allowed'], ...]
     */
    public function validateOperation(string $path, string $operation, array $context = []): array
    {
        $validation = [
            'allowed' => false,
            'normalized_path' => null,
            'policy_decision' => null,
            'risk_assessment' => null,
            'violations' => [],
            'warnings' => [],
        ];

        $normalized = $this->normalizePath($path);
        if (! $normalized['valid']) {
            $validation['violations'][] = $normalized['error'];

            return $validation;
        }

        $validation['normalized_path'] = $normalized['path'];

        $traversalCheck = $this->detectPathTraversal($normalized['real_path'], $path);
        if (! $traversalCheck['safe']) {
            $validation['violations'][] = $traversalCheck['reason'];

            return $validation;
        }

        if ($normalized['is_symlink']) {
            $symlinkCheck = $this->validateSymlink($normalized['path'], $normalized['real_path']);
            if (! $symlinkCheck['safe']) {
                $validation['violations'][] = $symlinkCheck['reason'];

                return $validation;
            }
            $validation['warnings'][] = 'Path is a symlink';
        }

        $policyDecision = $this->policyRegistry->isPathAllowed($normalized['real_path'], $operation);
        $validation['policy_decision'] = $policyDecision;

        if (! $policyDecision['allowed']) {
            $validation['violations'][] = $policyDecision['reason'];

            return $validation;
        }

        $risk = $this->riskScorer->scoreFileOperation($normalized['real_path'], $operation, $context);
        $validation['risk_assessment'] = $risk;

        if ($risk['requires_approval']) {
            $validation['violations'][] = "Operation requires approval (risk: {$risk['score']})";

            return $validation;
        }

        if ($operation === 'write' && isset($context['size'])) {
            $maxSize = $context['max_size'] ?? self::MAX_FILE_SIZE;
            if ($context['size'] > $maxSize) {
                $validation['violations'][] = 'File size exceeds limit';

                return $validation;
            }
        }

        $validation['allowed'] = true;

        return $validation;
    }

    /**
     * Normalize filesystem path with tilde expansion and realpath resolution.
     *
     * Processing:
     * - Expand ~ to home directory ($HOME or /home)
     * - Resolve symlinks and relative paths (if exists)
     * - Manual normalization for non-existent paths
     *
     * @param string $path Raw filesystem path
     * @return array{
     *     valid: bool,
     *     path: string,
     *     real_path: string|false,
     *     exists: bool,
     *     is_symlink: bool
     * } Normalized path information
     */
    private function normalizePath(string $path): array
    {
        if (str_starts_with($path, '~')) {
            $home = $_SERVER['HOME'] ?? '/home';
            $path = str_replace('~', $home, $path);
        }

        $exists = file_exists($path);
        $isSymlink = is_link($path);
        $realPath = $exists ? realpath($path) : $this->manualNormalize($path);

        return [
            'valid' => true,
            'path' => $path,
            'real_path' => $realPath,
            'exists' => $exists,
            'is_symlink' => $isSymlink,
        ];
    }

    /**
     * Manually normalize path when realpath() unavailable (non-existent paths).
     *
     * Processes:
     * - Remove empty segments and current directory (.)
     * - Resolve parent directory references (..)
     *
     * @param string $path Path to normalize
     * @return string Normalized absolute path
     */
    private function manualNormalize(string $path): string
    {
        $parts = explode('/', $path);
        $normalized = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($normalized);
            } else {
                $normalized[] = $part;
            }
        }

        return '/'.implode('/', $normalized);
    }

    /**
     * Detect path traversal attempts in original path string.
     *
     * Checks for:
     * - Path traversal patterns (../ or ..\)
     * - Null byte injection (\0)
     *
     * Logs warnings for traversal attempts.
     *
     * @param string $normalized Normalized path (for logging)
     * @param string $original Original path input
     * @return array{safe: bool, reason?: string} Safety result
     */
    private function detectPathTraversal(string $normalized, string $original): array
    {
        if (str_contains($original, '../') || str_contains($original, '..\\')) {
            Log::warning('Path traversal attempt', ['original' => $original, 'normalized' => $normalized]);

            return ['safe' => false, 'reason' => 'Path traversal patterns (..) not allowed'];
        }

        if (str_contains($original, "\0")) {
            return ['safe' => false, 'reason' => 'Null byte in path'];
        }

        return ['safe' => true];
    }

    /**
     * Validate symlink target against path policies.
     *
     * Ensures symlink target is allowed for read operations via policy registry.
     *
     * @param string $symlinkPath Original symlink path (unused, reserved for future validation)
     * @param string $targetPath Symlink target path
     * @return array{safe: bool, reason?: string} Safety result
     */
    private function validateSymlink(string $symlinkPath, string $targetPath): array
    {
        $targetPolicy = $this->policyRegistry->isPathAllowed($targetPath, 'read');

        if (! $targetPolicy['allowed']) {
            return ['safe' => false, 'reason' => "Symlink target not allowed: {$targetPath}"];
        }

        return ['safe' => true];
    }
}
