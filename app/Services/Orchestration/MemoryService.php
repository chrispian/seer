<?php

namespace App\Services\Orchestration;

use Illuminate\Support\Facades\Cache;

class MemoryService
{
    protected const DURABLE_PREFIXES = [
        'mem:task:{task_id}:boot',
        'mem:task:{task_id}:notes',
        'mem:task:{task_id}:postop',
    ];

    protected const EPHEMERAL_PREFIX = 'mem:task:{task_id}:scratch:';

    protected const EPHEMERAL_TTL = 86400; // 24 hours

    public function setDurable(string $taskId, string $key, $value): bool
    {
        $cacheKey = $this->resolveDurableKey($taskId, $key);

        return Cache::forever($cacheKey, [
            'value' => $value,
            'stored_at' => now()->toIso8601String(),
            'task_id' => $taskId,
        ]);
    }

    public function getDurable(string $taskId, string $key, $default = null)
    {
        $cacheKey = $this->resolveDurableKey($taskId, $key);
        $data = Cache::get($cacheKey);

        return $data ? $data['value'] : $default;
    }

    public function setEphemeral(string $taskId, string $key, $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->resolveEphemeralKey($taskId, $key);
        $ttl = $ttl ?? self::EPHEMERAL_TTL;

        // Track this key in the ephemeral keys registry
        $this->trackEphemeralKey($taskId, $key);

        return Cache::put($cacheKey, [
            'value' => $value,
            'stored_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds($ttl)->toIso8601String(),
            'task_id' => $taskId,
        ], $ttl);
    }

    protected function trackEphemeralKey(string $taskId, string $key): void
    {
        $registryKey = "mem:task:{$taskId}:scratch:_registry";
        $keys = Cache::get($registryKey, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put($registryKey, $keys, self::EPHEMERAL_TTL);
        }
    }

    public function getEphemeral(string $taskId, string $key, $default = null)
    {
        $cacheKey = $this->resolveEphemeralKey($taskId, $key);
        $data = Cache::get($cacheKey);

        return $data ? $data['value'] : $default;
    }

    public function getBoot(string $taskId, $default = null)
    {
        return $this->getDurable($taskId, 'boot', $default);
    }

    public function setBoot(string $taskId, $value): bool
    {
        return $this->setDurable($taskId, 'boot', $value);
    }

    public function getNotes(string $taskId, $default = null)
    {
        return $this->getDurable($taskId, 'notes', $default);
    }

    public function setNotes(string $taskId, $value): bool
    {
        return $this->setDurable($taskId, 'notes', $value);
    }

    public function getPostop(string $taskId, $default = null)
    {
        return $this->getDurable($taskId, 'postop', $default);
    }

    public function setPostop(string $taskId, $value): bool
    {
        return $this->setDurable($taskId, 'postop', $value);
    }

    public function compactEphemeral(string $taskId): array
    {
        $ephemeralKeys = $this->getEphemeralKeys($taskId);
        $compacted = [];

        foreach ($ephemeralKeys as $fullKey) {
            $shortKey = str_replace($this->resolveEphemeralKey($taskId, ''), '', $fullKey);
            $data = Cache::get($fullKey);

            if ($data) {
                $compacted[$shortKey] = $data['value'];
            }
        }

        return $compacted;
    }

    public function compactToPostop(string $taskId): bool
    {
        $ephemeralData = $this->compactEphemeral($taskId);

        if (empty($ephemeralData)) {
            return true;
        }

        $currentPostop = $this->getPostop($taskId, []);

        $merged = array_merge(
            is_array($currentPostop) ? $currentPostop : [],
            [
                'ephemeral_compacted_at' => now()->toIso8601String(),
                'scratch' => $ephemeralData,
            ]
        );

        return $this->setPostop($taskId, $merged);
    }

    public function cleanupEphemeral(string $taskId): int
    {
        $keys = $this->getEphemeralKeys($taskId);
        $count = 0;

        foreach ($keys as $key) {
            if (Cache::forget($key)) {
                $count++;
            }
        }

        return $count;
    }

    protected function resolveDurableKey(string $taskId, string $key): string
    {
        return "mem:task:{$taskId}:{$key}";
    }

    protected function resolveEphemeralKey(string $taskId, string $key): string
    {
        return "mem:task:{$taskId}:scratch:{$key}";
    }

    protected function getEphemeralKeys(string $taskId): array
    {
        $registryKey = "mem:task:{$taskId}:scratch:_registry";
        $keys = Cache::get($registryKey, []);

        return array_map(
            fn ($key) => $this->resolveEphemeralKey($taskId, $key),
            $keys
        );
    }
}
