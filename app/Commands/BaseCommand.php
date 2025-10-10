<?php

namespace App\Commands;

use App\Models\Command;
use App\Models\Type;

abstract class BaseCommand
{
    protected ?string $context = null;
    
    protected ?Command $command = null;
    
    protected ?Type $type = null;

    /**
     * Handle the command execution
     */
    abstract public function handle(): array;
    
    public function setCommand(?Command $command): self
    {
        $this->command = $command;
        
        if ($command && $command->type) {
            $this->type = $command->type;
        }
        
        return $this;
    }
    
    public function getCommand(): ?Command
    {
        return $this->command;
    }
    
    public function setType(?Type $type): self
    {
        $this->type = $type;
        return $this;
    }
    
    public function getTypeModel(): ?Type
    {
        return $this->type;
    }
    
    /**
     * Get response type identifier (for backward compatibility)
     * Subclasses may override this
     */
    protected function getType(): string
    {
        return $this->getResponseType();
    }
    
    protected function getTypeConfig(): array
    {
        if (!$this->type) {
            return [];
        }
        
        return [
            'slug' => $this->type->slug,
            'display_name' => $this->type->display_name,
            'plural_name' => $this->type->plural_name,
            'storage_type' => $this->type->storage_type,
            'model_class' => $this->type->model_class,
            'schema' => $this->type->schema,
            'default_card_component' => $this->type->default_card_component,
            'default_detail_component' => $this->type->default_detail_component,
            'capabilities' => $this->type->capabilities,
            'hot_fields' => $this->type->hot_fields,
        ];
    }
    
    protected function getUIConfig(): array
    {
        if (!$this->command) {
            return [];
        }
        
        $typeConfig = $this->getTypeConfig();
        
        return [
            'modal_container' => $this->command->ui_modal_container,
            'layout_mode' => $this->command->ui_layout_mode,
            'card_component' => $this->command->ui_card_component ?? $typeConfig['default_card_component'] ?? null,
            'detail_component' => $this->command->ui_detail_component ?? $typeConfig['default_detail_component'] ?? null,
            'filters' => $this->command->filters,
            'default_sort' => $this->command->default_sort,
            'pagination_default' => $this->command->pagination_default,
        ];
    }
    
    public function getFullConfig(): array
    {
        return [
            'type' => $this->getTypeConfig(),
            'ui' => $this->getUIConfig(),
            'command' => $this->command ? [
                'command' => $this->command->command,
                'name' => $this->command->name,
                'description' => $this->command->description,
                'category' => $this->command->category,
            ] : [],
        ];
    }

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
            'type' => $this->getResponseType(),
            'data' => $data,
        ];

        if ($component) {
            $response['component'] = $component;
        }
        
        $config = $this->getFullConfig();
        if (!empty($config)) {
            $response['config'] = $config;
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
    protected function getResponseType(): string
    {
        return $this->type?->slug ?? 'generic';
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
