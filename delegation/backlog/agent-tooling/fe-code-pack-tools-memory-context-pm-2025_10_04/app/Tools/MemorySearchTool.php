<?php

namespace App\Tools;

use App\Contracts\ToolContract;
use App\Support\ToolRegistry;
use App\Models\AgentNote;

class MemorySearchTool implements ToolContract
{
    public function __construct(protected ToolRegistry $registry) {}

    public function name(): string { return 'memory.search'; }
    public function scope(): string { return 'memory.read'; }

    public function inputSchema(): array
    {
        return $this->registry->loadContract('memory.search')['input_schema'] ?? [];
    }

    public function outputSchema(): array
    {
        return $this->registry->loadContract('memory.search')['output_schema'] ?? [];
    }

    public function run(array $payload): array
    {
        $this->registry->ensureScope($this->scope());
        $q = AgentNote::query();

        if (!empty($payload['q'])) {
            $term = $payload['q'];
            $q->where(function($qq) use ($term) {
                $qq->where('topic', 'like', '%' . $term . '%')
                   ->orWhere('body', 'like', '%' . $term . '%');
            });
        }
        if (!empty($payload['kinds'])) {
            $q->whereIn('kind', $payload['kinds']);
        }
        if (!empty($payload['scope']) && $payload['scope'] !== 'any') {
            $q->where('scope', $payload['scope']);
        }
        $limit = $payload['limit'] ?? 20;
        $items = $q->orderByDesc('created_at')->limit($limit)->get();

        return ['items' => $items->toArray()];
    }
}
