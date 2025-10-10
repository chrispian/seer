<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Command Model
 *
 * Represents a unified command that can be accessed via slash commands, CLI, or MCP.
 * Commands are the controller layer that operate on types.
 */
class Command extends Model
{
    protected $fillable = [
        'command',
        'name',
        'description',
        'category',
        'type_slug',
        'handler_class',
        'available_in_slash',
        'available_in_cli',
        'available_in_mcp',
        'ui_modal_container',
        'ui_layout_mode',
        'ui_card_component',
        'ui_detail_component',
        'filters',
        'default_sort',
        'pagination_default',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'available_in_slash' => 'boolean',
        'available_in_cli' => 'boolean',
        'available_in_mcp' => 'boolean',
        'filters' => 'array',
        'default_sort' => 'array',
        'pagination_default' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the type this command operates on
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_slug', 'slug');
    }

    /**
     * Scope: Only active commands
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Commands available in slash interface
     */
    public function scopeAvailableInSlash($query)
    {
        return $query->where('available_in_slash', true)->where('is_active', true);
    }

    /**
     * Scope: Commands available in CLI
     */
    public function scopeAvailableInCli($query)
    {
        return $query->where('available_in_cli', true)->where('is_active', true);
    }

    /**
     * Scope: Commands available in MCP
     */
    public function scopeAvailableInMcp($query)
    {
        return $query->where('available_in_mcp', true)->where('is_active', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Commands with UI (have modal configuration)
     */
    public function scopeWithUi($query)
    {
        return $query->whereNotNull('ui_modal_container');
    }

    /**
     * Get full configuration array for command handler
     */
    public function getFullConfig(): array
    {
        $config = $this->toArray();
        
        // Include type configuration if available
        if ($this->type) {
            $config['type_config'] = [
                'slug' => $this->type->slug,
                'storage_type' => $this->type->storage_type,
                'model_class' => $this->type->model_class,
                'default_card_component' => $this->type->default_card_component,
                'default_detail_component' => $this->type->default_detail_component,
                'capabilities' => $this->type->capabilities,
                'hot_fields' => $this->type->hot_fields,
            ];
        }
        
        return $config;
    }

    /**
     * Get effective card component (command override or type default)
     */
    public function getCardComponent(): ?string
    {
        return $this->ui_card_component ?? $this->type?->default_card_component;
    }

    /**
     * Get effective detail component (command override or type default)
     */
    public function getDetailComponent(): ?string
    {
        return $this->ui_detail_component ?? $this->type?->default_detail_component;
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Check if command has UI
     */
    public function hasUi(): bool
    {
        return !is_null($this->ui_modal_container);
    }

    /**
     * Get the handler class instance
     */
    public function getHandlerInstance(): ?object
    {
        if (!class_exists($this->handler_class)) {
            return null;
        }

        return new $this->handler_class;
    }
}
