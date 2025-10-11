<?php

namespace App\Services\Orchestration;

use App\Models\WorkSession;

class SessionContextStack
{
    public function push(WorkSession $session, string $type, string $id, array $data = []): void
    {
        $stack = $session->context_stack ?? [];

        $stack[] = [
            'type' => $type,
            'id' => $id,
            'data' => $data,
            'pushed_at' => now()->toIso8601String(),
        ];

        $session->update(['context_stack' => $stack]);
    }

    public function pop(WorkSession $session, string $type): ?array
    {
        $stack = $session->context_stack ?? [];

        $poppedIndex = null;
        $popped = null;

        for ($i = count($stack) - 1; $i >= 0; $i--) {
            if ($stack[$i]['type'] === $type) {
                $popped = $stack[$i];
                $poppedIndex = $i;
                break;
            }
        }

        if ($poppedIndex !== null) {
            array_splice($stack, $poppedIndex, 1);
            $session->update(['context_stack' => $stack]);
        }

        return $popped;
    }

    public function getCurrent(WorkSession $session, ?string $type = null): ?array
    {
        $stack = $session->context_stack ?? [];

        if (empty($stack)) {
            return null;
        }

        if ($type === null) {
            return end($stack) ?: null;
        }

        for ($i = count($stack) - 1; $i >= 0; $i--) {
            if ($stack[$i]['type'] === $type) {
                return $stack[$i];
            }
        }

        return null;
    }

    public function getStack(WorkSession $session): array
    {
        return $session->context_stack ?? [];
    }

    public function clear(WorkSession $session): void
    {
        $session->update(['context_stack' => []]);
    }

    public function has(WorkSession $session, string $type): bool
    {
        return $this->getCurrent($session, $type) !== null;
    }

    public function getAll(WorkSession $session, string $type): array
    {
        $stack = $session->context_stack ?? [];
        $results = [];

        foreach ($stack as $item) {
            if ($item['type'] === $type) {
                $results[] = $item;
            }
        }

        return $results;
    }

    public function count(WorkSession $session, ?string $type = null): int
    {
        if ($type === null) {
            return count($session->context_stack ?? []);
        }

        return count($this->getAll($session, $type));
    }
}
