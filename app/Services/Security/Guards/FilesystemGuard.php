<?php

namespace App\Services\Security\Guards;

use App\Services\Security\PolicyRegistry;
use App\Services\Security\RiskScorer;
use Illuminate\Support\Facades\Log;

class FilesystemGuard
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB default

    public function __construct(
        private PolicyRegistry $policyRegistry,
        private RiskScorer $riskScorer
    ) {}

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

    private function validateSymlink(string $symlinkPath, string $targetPath): array
    {
        $targetPolicy = $this->policyRegistry->isPathAllowed($targetPath, 'read');

        if (! $targetPolicy['allowed']) {
            return ['safe' => false, 'reason' => "Symlink target not allowed: {$targetPath}"];
        }

        return ['safe' => true];
    }
}
