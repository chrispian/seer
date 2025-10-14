<?php

namespace App\Services\Tools\Providers;

use App\Models\ChatSession;
use App\Services\Tools\Contracts\Tool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProjectFileSystemTool implements Tool
{
    public function slug(): string
    {
        return 'project_fs';
    }

    public function capabilities(): array
    {
        return ['read', 'write', 'list', 'exists', 'search', 'file_operations'];
    }

    public function isEnabled(): bool
    {
        return Config::get('fragments.tools.project_fs.enabled', false);
    }

    public function getConfigSchema(): array
    {
        return [
            'required' => ['op', 'path'],
            'properties' => [
                'op' => [
                    'type' => 'string',
                    'enum' => ['read', 'write', 'list', 'exists', 'search'],
                    'description' => 'File operation to perform',
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'File or directory path (relative to project paths)',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Content to write (for write operation)',
                ],
                'pattern' => [
                    'type' => 'string',
                    'description' => 'Search pattern (for search operation)',
                ],
            ],
        ];
    }

    public function call(array $args, array $context = []): array
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('ProjectFileSystem tool is disabled. Enable with FRAGMENT_TOOLS_PROJECT_FS_ENABLED=true');
        }

        $sessionId = $context['session_id'] ?? null;
        if (! $sessionId) {
            throw new \RuntimeException('No session context provided');
        }

        $session = ChatSession::find($sessionId);
        if (! $session) {
            throw new \RuntimeException('Chat session not found');
        }

        $op = $args['op'] ?? null;
        $path = $args['path'] ?? null;

        if (! $op || ! $path) {
            throw new \InvalidArgumentException('Missing required parameters: op and path');
        }

        $allowedPaths = $session->getAllAccessiblePaths();
        if (empty($allowedPaths)) {
            return [
                'success' => false,
                'error' => 'No project paths configured. Please select a project or add additional paths.',
            ];
        }

        $resolvedPath = $this->resolvePath($path, $allowedPaths);
        if (! $resolvedPath) {
            return [
                'success' => false,
                'error' => "Path '{$path}' is not accessible. Available paths: ".implode(', ', array_column($allowedPaths, 'path')),
            ];
        }

        $this->checkSecurityRestrictions($resolvedPath);

        switch ($op) {
            case 'read':
                return $this->readFile($resolvedPath);

            case 'write':
                $content = $args['content'] ?? '';
                
                if ($this->requiresApprovalForWrite($resolvedPath, $context)) {
                    return $this->requestApproval('write', $resolvedPath, $context, ['content' => $content]);
                }

                return $this->writeFile($resolvedPath, $content);

            case 'list':
                return $this->listDirectory($resolvedPath);

            case 'exists':
                return $this->fileExists($resolvedPath);

            case 'search':
                $pattern = $args['pattern'] ?? '';
                return $this->searchFiles($resolvedPath, $pattern);

            default:
                throw new \InvalidArgumentException("Unknown operation: {$op}");
        }
    }

    protected function resolvePath(string $requestedPath, array $allowedPaths): ?string
    {
        $requestedPath = $this->sanitizePath($requestedPath);

        foreach ($allowedPaths as $pathInfo) {
            $basePath = rtrim($pathInfo['path'], '/');
            
            if (str_starts_with($requestedPath, '/')) {
                $fullPath = $requestedPath;
            } else {
                $fullPath = $basePath.'/'.$requestedPath;
            }

            $fullPath = realpath($fullPath) ?: $fullPath;

            if (str_starts_with($fullPath, $basePath) || $fullPath === $basePath) {
                return $fullPath;
            }
        }

        return null;
    }

    protected function sanitizePath(string $path): string
    {
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $path = preg_replace('#/+#', '/', $path);

        return trim($path);
    }

    protected function checkSecurityRestrictions(string $path): void
    {
        $blacklist = [
            '.env',
            '.env.local',
            '.env.production',
            'id_rsa',
            'id_dsa',
            '.pem',
            '.key',
            '.p12',
            '.pfx',
            'secrets/',
            '.ssh/',
            '.aws/',
        ];

        foreach ($blacklist as $pattern) {
            if (str_contains(strtolower($path), strtolower($pattern))) {
                throw new \RuntimeException("Access to sensitive file '{$path}' is blocked for security reasons");
            }
        }
    }

    protected function readFile(string $path): array
    {
        if (! File::exists($path)) {
            return [
                'success' => false,
                'error' => 'File not found',
            ];
        }

        if (! File::isFile($path)) {
            return [
                'success' => false,
                'error' => 'Path is not a file',
            ];
        }

        $maxSize = Config::get('fragments.tools.project_fs.max_file_size', 10 * 1024 * 1024);
        if (File::size($path) > $maxSize) {
            return [
                'success' => false,
                'error' => 'File too large to read (max: '.($maxSize / 1024 / 1024).' MB)',
            ];
        }

        return [
            'success' => true,
            'content' => File::get($path),
            'path' => $path,
            'size' => File::size($path),
            'modified' => File::lastModified($path),
        ];
    }

    protected function writeFile(string $path, string $content): array
    {
        $maxSize = Config::get('fragments.tools.project_fs.max_write_size', 10 * 1024 * 1024);
        if (strlen($content) > $maxSize) {
            return [
                'success' => false,
                'error' => 'Content too large to write (max: '.($maxSize / 1024 / 1024).' MB)',
            ];
        }

        try {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);

            return [
                'success' => true,
                'path' => $path,
                'size' => strlen($content),
                'message' => 'File written successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to write file: '.$e->getMessage(),
            ];
        }
    }

    protected function listDirectory(string $path): array
    {
        if (! File::exists($path)) {
            return [
                'success' => false,
                'error' => 'Directory not found',
            ];
        }

        if (! File::isDirectory($path)) {
            return [
                'success' => false,
                'error' => 'Path is not a directory',
            ];
        }

        $files = [];
        $directories = [];

        try {
            foreach (File::files($path) as $file) {
                $files[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }

            foreach (File::directories($path) as $dir) {
                $directories[] = basename($dir);
            }

            return [
                'success' => true,
                'path' => $path,
                'files' => $files,
                'directories' => $directories,
                'count' => count($files) + count($directories),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to list directory: '.$e->getMessage(),
            ];
        }
    }

    protected function fileExists(string $path): array
    {
        return [
            'success' => true,
            'exists' => File::exists($path),
            'is_file' => File::isFile($path),
            'is_directory' => File::isDirectory($path),
            'path' => $path,
        ];
    }

    protected function searchFiles(string $path, string $pattern): array
    {
        if (! File::exists($path)) {
            return [
                'success' => false,
                'error' => 'Directory not found',
            ];
        }

        if (! File::isDirectory($path)) {
            return [
                'success' => false,
                'error' => 'Path is not a directory',
            ];
        }

        $results = [];
        $maxResults = 100;

        try {
            foreach (File::allFiles($path) as $file) {
                if (count($results) >= $maxResults) {
                    break;
                }

                if (str_contains($file->getFilename(), $pattern)) {
                    $results[] = [
                        'path' => $file->getPathname(),
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results,
                'count' => count($results),
                'limited' => count($results) >= $maxResults,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Search failed: '.$e->getMessage(),
            ];
        }
    }

    protected function requiresApprovalForWrite(string $path, array $context): bool
    {
        if (! Config::get('fragments.tools.project_fs.require_approval_for_writes', true)) {
            return false;
        }

        return true;
    }

    protected function requestApproval(string $operation, string $path, array $context, array $additionalData = []): array
    {
        $approvalManager = app(\App\Services\Security\ApprovalManager::class);
        $conversationId = $context['conversation_id'] ?? null;
        $messageId = $context['message_id'] ?? null;

        if (! $conversationId) {
            throw new \RuntimeException('No conversation context for approval request');
        }

        $approvalRequest = $approvalManager->createApprovalRequest([
            'type' => 'file_operation',
            'operation' => $operation,
            'path' => $path,
            'summary' => ucfirst($operation)." file: {$path}",
            'context' => array_merge($context, $additionalData),
        ], $conversationId, $messageId);

        return [
            'success' => false,
            'requires_approval' => true,
            'approval_request' => $approvalRequest,
            'message' => "This file operation requires your approval: {$operation} {$path}",
        ];
    }
}
