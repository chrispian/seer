<?php

namespace App\Tools;

use App\Contracts\ToolContract;
use App\Models\AgentNote;
use App\Support\ToolRegistry;

class MemoryWriteTool implements ToolContract
{
    public function __construct(protected ToolRegistry $registry) {}

    public function name(): string
    {
        return 'memory.write';
    }

    public function scope(): string
    {
        return 'memory.write';
    }

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

        // Validate required fields according to the JSON schema
        $this->validatePayload($payload);

        $note = new AgentNote;
        $note->agent_id = $payload['agent_id'] ?? null;
        $note->topic = $payload['topic'];
        $note->body = $payload['body'];
        $note->kind = $payload['kind'];
        $note->scope = $payload['scope'];
        $note->ttl_at = isset($payload['ttl_days']) && $payload['ttl_days'] ? now()->addDays((int) $payload['ttl_days']) : null;
        $note->links = $payload['links'] ?? [];
        $note->tags = $payload['tags'] ?? [];
        $note->provenance = [
            'tool' => 'memory.write',
            'input_hash' => ToolRegistry::hash($payload),
        ];
        $note->save();

        return ['note_id' => (string) $note->id];
    }

    private function validatePayload(array $payload): void
    {
        $schema = $this->inputSchema();
        $required = $schema['required'] ?? [];

        // Check for missing required fields
        $missing = [];
        foreach ($required as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw new \InvalidArgumentException('Missing required fields: '.implode(', ', $missing));
        }

        // Validate enum values if they exist
        $properties = $schema['properties'] ?? [];

        if (isset($properties['kind']['enum']) && ! in_array($payload['kind'], $properties['kind']['enum'], true)) {
            throw new \InvalidArgumentException('Invalid kind value. Must be one of: '.implode(', ', $properties['kind']['enum']));
        }

        if (isset($properties['scope']['enum']) && ! in_array($payload['scope'], $properties['scope']['enum'], true)) {
            throw new \InvalidArgumentException('Invalid scope value. Must be one of: '.implode(', ', $properties['scope']['enum']));
        }
    }
}
