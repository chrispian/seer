<?php

namespace App\Services\Commands\DSL\Steps;

use App\Services\Tools\ToolRegistry;
use App\Events\Tools\ToolInvoked;
use App\Events\Tools\ToolCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolCallStep implements Step
{
    public function __construct(protected ToolRegistry $tools) {}

    public function execute(array $definition, array $scope): array
    {
        $toolName = data_get($definition, 'with.tool');
        $args = data_get($definition, 'with.args', []);
        
        if (!$toolName) {
            throw new \InvalidArgumentException('Missing required parameter: tool');
        }

        // Check if tool is allowed
        if (!$this->tools->allowed($toolName)) {
            throw new \RuntimeException("Tool not allowed: {$toolName}");
        }

        // Validate tool exists
        if (!$this->tools->exists($toolName)) {
            throw new \RuntimeException("Tool not found: {$toolName}");
        }

        // Validate arguments
        if (!$this->tools->validateArgs($toolName, $args)) {
            throw new \InvalidArgumentException("Invalid arguments for tool: {$toolName}");
        }

        $tool = $this->tools->get($toolName);
        
        // Build context
        $context = [
            'user' => data_get($scope, 'ctx.user'),
            'fragment_id' => data_get($scope, 'ctx.fragment_id'),
            'command_slug' => data_get($scope, 'ctx.command_slug'),
            'session_id' => data_get($scope, 'ctx.session_id'),
        ];

        $invocationId = (string) Str::uuid();
        $userId = data_get($context, 'user.id', data_get($scope, 'ctx.user_id'));
        $commandSlug = data_get($context, 'command_slug');
        $fragmentId = data_get($context, 'fragment_id');

        // Fire tool invoked event
        event(new ToolInvoked(
            tool: $toolName,
            invocationId: $invocationId,
            commandSlug: $commandSlug,
            fragmentId: $fragmentId,
            userId: $userId
        ));

        $start = microtime(true);
        $status = 'ok';
        $response = [];

        try {
            $response = $tool->call($args, $context);
            return $response;

        } catch (\Throwable $e) {
            $status = 'error';
            $response = ['error' => $e->getMessage()];
            throw $e;

        } finally {
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            // Log invocation to database
            DB::table('tool_invocations')->insert([
                'id' => $invocationId,
                'user_id' => $userId,
                'tool_slug' => $toolName,
                'command_slug' => $commandSlug,
                'fragment_id' => $fragmentId,
                'request' => json_encode($args),
                'response' => json_encode($response),
                'status' => $status,
                'duration_ms' => $durationMs,
                'created_at' => now(),
            ]);

            // Fire tool completed event
            event(new ToolCompleted(
                tool: $toolName,
                status: $status,
                durationMs: (int) $durationMs,
                invocationId: $invocationId,
                commandSlug: $commandSlug,
                fragmentId: $fragmentId,
                userId: $userId
            ));
        }
    }
}