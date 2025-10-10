<?php

namespace App\Commands;

abstract class BaseCommand
{
    protected ?string $context = null; // 'web', 'mcp', 'cli'

    /**
     * Handle the command execution
     */
    abstract public function handle(): array;

    /**
     * Set execution context for context-aware responses
     */
    public function setContext(string $context): self
    {
        if (!in_array($context, ['web', 'mcp', 'cli'])) {
            throw new \InvalidArgumentException("Invalid context: {$context}. Must be 'web', 'mcp', or 'cli'.");
        }
        $this->context = $context;
        return $this;
    }

    /**
     * Get current execution context
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * Format response based on context
     * 
     * @param array $data The data to return
     * @param string|null $component Optional UI component name (for web context)
     * @return array Formatted response
     */
    protected function respond(array $data, ?string $component = null): array
    {
        return match($this->context) {
            'web' => $this->webResponse($data, $component),
            'mcp' => $this->mcpResponse($data),
            'cli' => $this->cliResponse($data),
            default => $data, // Fallback for direct invocation
        };
    }

    /**
     * Web UI response (optional component + data)
     */
    protected function webResponse(array $data, ?string $component): array
    {
        $response = [
            'type' => $this->getType(),
            'data' => $data,
        ];

        // Add component only if specified (UI is optional)
        if ($component) {
            $response['component'] = $component;
        }

        return $response;
    }

    /**
     * MCP response (structured JSON with metadata)
     */
    protected function mcpResponse(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'count' => is_countable($data) ? count($data) : null,
                'command' => static::class,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * CLI response (same as MCP)
     */
    protected function cliResponse(array $data): array
    {
        return $this->mcpResponse($data);
    }

    /**
     * Get response type identifier
     * Override in subclasses
     */
    protected function getType(): string
    {
        return 'generic';
    }

    /**
     * Get help information for this command
     */
    public function getHelp(): array
    {
        return [
            'name' => static::getName(),
            'description' => static::getDescription(),
            'usage' => static::getUsage(),
            'category' => static::getCategory(),
        ];
    }

    /**
     * Get command name (override in subclasses)
     */
    public static function getName(): string
    {
        return 'Unknown Command';
    }

    /**
     * Get command description (override in subclasses)
     */
    public static function getDescription(): string
    {
        return 'No description available';
    }

    /**
     * Get command usage (override in subclasses)
     */
    public static function getUsage(): string
    {
        return '/command';
    }

    /**
     * Get command category (override in subclasses)
     */
    public static function getCategory(): string
    {
        return 'General';
    }

    /**
     * Define MCP input schema
     * Override in subclasses to specify parameters
     */
    public static function getInputSchema(): array
    {
        return [];
    }
}
