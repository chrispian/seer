<?php

namespace App\Services\Commands\DSL\Steps;

class ToolCallStep extends Step
{
    protected array $allowedTools = [
        'echo',
        'uuid',
        'timestamp',
        'url_extract',
    ];

    public function getType(): string
    {
        return 'tool.call';
    }

    public function execute(array $config, array $context, bool $dryRun = false): mixed
    {
        $tool = $config['tool'] ?? '';
        $args = $config['args'] ?? [];

        if (!$tool) {
            throw new \InvalidArgumentException('Tool call step requires a tool name');
        }

        if (!in_array($tool, $this->allowedTools)) {
            throw new \InvalidArgumentException("Tool '{$tool}' is not in the allowed list");
        }

        if ($dryRun) {
            return [
                'dry_run' => true,
                'tool' => $tool,
                'args' => $args,
                'would_call' => true,
            ];
        }

        return $this->callTool($tool, $args);
    }

    protected function callTool(string $tool, array $args): mixed
    {
        switch ($tool) {
            case 'echo':
                return $args['message'] ?? '';

            case 'uuid':
                return \Str::uuid()->toString();

            case 'timestamp':
                return now()->toISOString();

            case 'url_extract':
                $text = $args['text'] ?? '';
                preg_match_all('/(https?:\/\/[^\s]+)/', $text, $matches);
                return $matches[1] ?? [];

            default:
                throw new \InvalidArgumentException("Unknown tool: {$tool}");
        }
    }

    public function validate(array $config): bool
    {
        return isset($config['tool']) && in_array($config['tool'], $this->allowedTools);
    }
}