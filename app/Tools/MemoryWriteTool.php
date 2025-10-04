<?php

namespace App\Tools;

use App\Contracts\ToolContract;
use App\Support\ToolRegistry;
use App\Models\AgentNote;
use Illuminate\Support\Carbon;

class MemoryWriteTool implements ToolContract
{
    public function __construct(protected ToolRegistry $registry) {}

    public function name(): string { return 'memory.write'; }
    public function scope(): string { return 'memory.write'; }

    public function inputSchema(): array
    {
        return $this->registry->loadContract('memory.write')['input_schema'] ?? [];
    }

    public function outputSchema(): array
    {
        return $this->registry->loadContract('memory.write')['output_schema'] ?? [];
    }

    public function run(array $payload): array
    {
        $this->registry->ensureScope($this->scope());
        $note = new AgentNote();
        $note->agent_id = $payload['agent_id'] ?? null;
        $note->topic = $payload['topic'];
        $note->body = $payload['body'];
        $note->kind = $payload['kind'];
        $note->scope = $payload['scope'];
        $note->ttl_at = isset($payload['ttl_days']) && $payload['ttl_days'] ? now()->addDays((int)$payload['ttl_days']) : null;
        $note->links = $payload['links'] ?? [];
        $note->tags = $payload['tags'] ?? [];
        $note->provenance = [
            'tool' => 'memory.write',
            'input_hash' => ToolRegistry::hash($payload),
        ];
        $note->save();

        return ['note_id' => (string)$note->id];
    }
}
