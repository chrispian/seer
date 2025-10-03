<?php

namespace App\Services\Tools;

use App\Services\Tools\Contracts\Tool;
use App\Services\Tools\Providers\ShellTool;
use App\Services\Tools\Providers\FileSystemTool;
use App\Services\Tools\Providers\MCPTool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ToolRegistry
{
    /** @var array<string,Tool> */
    protected array $map = [];

    protected bool $initialized = false;

    /**
     * Register a tool in the registry
     */
    public function register(Tool $tool): void
    {
        $this->map[$tool->slug()] = $tool;
    }

    /**
     * Get a tool by slug
     */
    public function get(string $slug): Tool
    {
        $this->ensureInitialized();
        
        if (!isset($this->map[$slug])) {
            throw new \RuntimeException("Tool not registered: {$slug}");
        }
        
        return $this->map[$slug];
    }

    /**
     * Check if a tool is allowed to be used
     */
    public function allowed(string $slug): bool
    {
        $allowed = Config::get('fragments.tools.allowed', []);
        return in_array($slug, $allowed, true);
    }

    /**
     * Check if a tool exists in the registry
     */
    public function exists(string $slug): bool
    {
        $this->ensureInitialized();
        return isset($this->map[$slug]);
    }

    /**
     * Get all registered tools
     */
    public function all(): array
    {
        $this->ensureInitialized();
        return $this->map;
    }

    /**
     * Get all allowed tools
     */
    public function getAllowed(): array
    {
        $this->ensureInitialized();
        $allowed = Config::get('fragments.tools.allowed', []);
        
        return array_filter($this->map, function($slug) use ($allowed) {
            return in_array($slug, $allowed, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get tool capabilities
     */
    public function getCapabilities(string $slug): array
    {
        if (!$this->exists($slug)) {
            return [];
        }

        try {
            return $this->get($slug)->capabilities();
        } catch (\Exception $e) {
            Log::warning("Failed to get capabilities for tool {$slug}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Initialize default tools
     */
    protected function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        // Register core tools
        $this->register(new ShellTool());
        $this->register(new FileSystemTool());
        $this->register(new MCPTool());

        $this->initialized = true;
    }

    /**
     * Check if a user has capability to use a tool
     */
    public function hasCapability(string $capability, ?int $userId = null): bool
    {
        // For now, simplified capability checking
        // In the future, this could check user permissions
        return true;
    }

    /**
     * Validate tool arguments against schema
     */
    public function validateArgs(string $slug, array $args): bool
    {
        if (!$this->exists($slug)) {
            return false;
        }

        try {
            $tool = $this->get($slug);
            $schema = $tool->getConfigSchema();
            
            // Simple validation - in production, use a proper JSON schema validator
            foreach ($schema['required'] ?? [] as $field) {
                if (!isset($args[$field])) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Tool argument validation failed for {$slug}", ['error' => $e->getMessage()]);
            return false;
        }
    }
}