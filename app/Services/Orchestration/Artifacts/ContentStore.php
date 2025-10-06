<?php

namespace App\Services\Orchestration\Artifacts;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ContentStore
{
    private string $disk;
    private string $root;

    public function __construct()
    {
        $this->disk = config('orchestration.artifacts.disk', 'local');
        $this->root = config('orchestration.artifacts.root', 'orchestration/artifacts');
    }

    public function put(string $content, array $metadata = []): string
    {
        $hash = hash('sha256', $content);
        $path = $this->getHashPath($hash);

        if (!$this->exists($hash)) {
            Storage::disk($this->disk)->put($path, $content);
        }

        return $hash;
    }

    public function get(string $hash): ?string
    {
        if (!$this->isValidHash($hash)) {
            throw new InvalidArgumentException('Invalid SHA256 hash format');
        }

        $path = $this->getHashPath($hash);

        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->get($path);
    }

    public function exists(string $hash): bool
    {
        if (!$this->isValidHash($hash)) {
            return false;
        }

        return Storage::disk($this->disk)->exists($this->getHashPath($hash));
    }

    public function getPath(string $hash): string
    {
        if (!$this->isValidHash($hash)) {
            throw new InvalidArgumentException('Invalid SHA256 hash format');
        }

        return Storage::disk($this->disk)->path($this->getHashPath($hash));
    }

    public function getHashPath(string $hash): string
    {
        $prefix = substr($hash, 0, 2);
        return "{$this->root}/by-hash/{$prefix}/{$hash}";
    }

    public function getTaskPath(string $taskId, string $filename): string
    {
        return "{$this->root}/by-task/{$taskId}/{$filename}";
    }

    public function formatUri(string $hash, ?string $taskId = null, ?string $filename = null): string
    {
        if ($taskId && $filename) {
            return "fe://artifacts/by-task/{$taskId}/{$filename}";
        }

        $prefix = substr($hash, 0, 2);
        return "fe://artifacts/by-hash/{$prefix}/{$hash}";
    }

    public function parseUri(string $uri): ?array
    {
        if (!str_starts_with($uri, 'fe://artifacts/')) {
            return null;
        }

        $path = str_replace('fe://artifacts/', '', $uri);

        if (preg_match('#^by-hash/([a-f0-9]{2})/([a-f0-9]{64})$#', $path, $matches)) {
            return [
                'type' => 'hash',
                'hash' => $matches[2],
            ];
        }

        if (preg_match('#^by-task/([^/]+)/(.+)$#', $path, $matches)) {
            return [
                'type' => 'task',
                'task_id' => $matches[1],
                'filename' => $matches[2],
            ];
        }

        return null;
    }

    public function size(string $hash): ?int
    {
        if (!$this->exists($hash)) {
            return null;
        }

        return Storage::disk($this->disk)->size($this->getHashPath($hash));
    }

    private function isValidHash(string $hash): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $hash);
    }
}
