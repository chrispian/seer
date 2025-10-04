<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileSystemTool implements Tool
{
    public function slug(): string 
    { 
        return 'fs'; 
    }

    public function capabilities(): array 
    { 
        return ['read', 'write', 'list', 'file_operations']; 
    }

    public function isEnabled(): bool
    {
        return Config::get('fragments.tools.fs.enabled', false);
    }

    public function getConfigSchema(): array
    {
        return [
            'required' => ['op'],
            'properties' => [
                'op' => ['type' => 'string', 'enum' => ['list', 'read', 'write', 'exists'], 'description' => 'File operation'],
                'path' => ['type' => 'string', 'description' => 'File path relative to root'],
                'content' => ['type' => 'string', 'description' => 'Content to write (for write operation)'],
            ]
        ];
    }

    protected function getRoot(): string 
    { 
        return Config::get('fragments.tools.fs.root', storage_path('app/tools')); 
    }

    public function call(array $args, array $context = []): array
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('FileSystem tool is disabled');
        }

        $op = $args['op'] ?? 'list';
        $path = $this->sanitizePath($args['path'] ?? '');

        switch ($op) {
            case 'list':
                return $this->listFiles($path);
                
            case 'read':
                return $this->readFile($path);
                
            case 'write':
                $content = $args['content'] ?? '';
                return $this->writeFile($path, $content);
                
            case 'exists':
                return $this->fileExists($path);
                
            default:
                throw new \InvalidArgumentException("Unknown operation: {$op}");
        }
    }

    protected function listFiles(string $path): array
    {
        $fullPath = $this->getRoot() . '/' . $path;
        
        if (!File::isDirectory($fullPath)) {
            return ['files' => [], 'directories' => []];
        }

        $files = [];
        $directories = [];

        foreach (File::allFiles($fullPath) as $file) {
            $files[] = $file->getFilename();
        }

        foreach (File::directories($fullPath) as $dir) {
            $directories[] = basename($dir);
        }

        return [
            'files' => $files,
            'directories' => $directories,
            'path' => $path,
        ];
    }

    protected function readFile(string $path): array
    {
        $fullPath = $this->getRoot() . '/' . $path;
        
        if (!File::exists($fullPath)) {
            return ['content' => null, 'exists' => false];
        }

        // Security: limit file size
        $maxSize = Config::get('fragments.tools.fs.max_file_size', 1024 * 1024); // 1MB default
        if (File::size($fullPath) > $maxSize) {
            throw new \RuntimeException('File too large to read');
        }

        return [
            'content' => File::get($fullPath),
            'exists' => true,
            'size' => File::size($fullPath),
            'modified' => File::lastModified($fullPath),
        ];
    }

    protected function writeFile(string $path, string $content): array
    {
        $fullPath = $this->getRoot() . '/' . $path;
        
        // Security: limit content size
        $maxSize = Config::get('fragments.tools.fs.max_write_size', 1024 * 1024); // 1MB default
        if (strlen($content) > $maxSize) {
            throw new \RuntimeException('Content too large to write');
        }

        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $content);

        return [
            'success' => true,
            'path' => $path,
            'size' => strlen($content),
        ];
    }

    protected function fileExists(string $path): array
    {
        $fullPath = $this->getRoot() . '/' . $path;
        
        return [
            'exists' => File::exists($fullPath),
            'is_file' => File::isFile($fullPath),
            'is_directory' => File::isDirectory($fullPath),
        ];
    }

    protected function sanitizePath(string $path): string
    {
        // Remove leading slash and resolve path traversal attempts
        $path = Str::of($path)
            ->ltrim('/')
            ->replace('..', '')
            ->replace('//', '/')
            ->toString();

        return $path;
    }
}