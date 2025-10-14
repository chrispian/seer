<?php

namespace App\Services\Tools;

use App\Services\Tools\Contracts\Tool;
use App\Services\Tools\Providers\FileSystemTool;
use App\Services\Tools\Providers\MCPTool;
use App\Services\Tools\Providers\ProjectFileSystemTool;
use App\Services\Tools\Providers\ShellTool;
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

        if (! isset($this->map[$slug])) {
            throw new \RuntimeException("Tool not registered: {$slug}");
        }

        return $this->map[$slug];
    }

    /**
     * Execute a tool with audit logging
     */
    public function call(string $slug, array $args, array $context = []): array
    {
        $tool = $this->get($slug);

        // Log the tool execution attempt
        $this->logToolExecution($slug, $args, $context, 'attempt');

        try {
            $result = $tool->call($args, $context);

            // Log successful execution
            $this->logToolExecution($slug, $args, $context, 'success', $result);

            return $result;
        } catch (\Exception $e) {
            // Log failed execution
            $this->logToolExecution($slug, $args, $context, 'error', null, $e->getMessage());

            throw $e;
        }
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

        return array_filter($this->map, function ($slug) use ($allowed) {
            return in_array($slug, $allowed, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get tool capabilities
     */
    public function getCapabilities(string $slug): array
    {
        if (! $this->exists($slug)) {
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
        $this->register(new ShellTool);
        $this->register(new FileSystemTool);
        $this->register(new MCPTool);
        $this->register(new ProjectFileSystemTool);

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
        if (! $this->exists($slug)) {
            return false;
        }

        try {
            $tool = $this->get($slug);
            $schema = $tool->getConfigSchema();

            // Simple validation - in production, use a proper JSON schema validator
            foreach ($schema['required'] ?? [] as $field) {
                if (! isset($args[$field])) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Tool argument validation failed for {$slug}", ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Log tool execution for audit purposes
     */
    protected function logToolExecution(string $slug, array $args, array $context, string $status, ?array $result = null, ?string $error = null): void
    {
        $logData = [
            'tool' => $slug,
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'user_id' => $context['user_id'] ?? null,
            'session_id' => $context['session_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
        ];

        // For security, don't log full command arguments in production
        if (config('app.env') === 'production') {
            $logData['args_summary'] = $this->summarizeArgs($args);
        } else {
            $logData['args'] = $args;
        }

        if ($result) {
            $logData['result_summary'] = $this->summarizeResult($result);
        }

        if ($error) {
            $logData['error'] = $error;
        }

        // Check if this is a destructive operation
        if ($this->isDestructiveOperation($slug, $args)) {
            Log::warning('DESTRUCTIVE_TOOL_EXECUTION', $logData);
        } else {
            Log::info('TOOL_EXECUTION', $logData);
        }
    }

    /**
     * Check if the operation is potentially destructive
     */
    protected function isDestructiveOperation(string $slug, array $args): bool
    {
        if ($slug === 'shell') {
            $cmd = $args['cmd'] ?? '';

            return str_contains($cmd, 'migrate:fresh') ||
                   str_contains($cmd, 'db:wipe') ||
                   str_contains($cmd, 'DROP') ||
                   str_contains($cmd, 'TRUNCATE') ||
                   str_contains($cmd, 'DELETE FROM');
        }

        return false;
    }

    /**
     * Create a safe summary of arguments for logging
     */
    protected function summarizeArgs(array $args): string
    {
        if (isset($args['cmd'])) {
            // For shell commands, show only the first part
            $cmd = $args['cmd'];
            $parts = explode(' ', $cmd, 3);

            return count($parts) >= 2 ? $parts[0].' '.$parts[1] : $parts[0];
        }

        return json_encode(array_keys($args));
    }

    /**
     * Create a safe summary of results for logging
     */
    protected function summarizeResult(array $result): string
    {
        if (isset($result['success'])) {
            return 'success: '.($result['success'] ? 'true' : 'false');
        }

        return 'keys: '.implode(', ', array_keys($result));
    }
}
