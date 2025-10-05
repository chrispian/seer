<?php

namespace App\Services\Commands\DSL;

use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolCallStep implements Step
{
    public function __construct(protected ToolRegistry $tools) {}

    public function execute(array $def, array $scope)
    {
        $name = data_get($def, 'with.name');
        $args = data_get($def, 'with.args', []);
        $context = ['user' => data_get($scope, 'ctx.user'), 'workspace' => data_get($scope, 'ctx.workspace')];

        if (! $this->tools->allowed($name)) {
            throw new \RuntimeException("Tool not allowed: {$name}");
        }
        $tool = $this->tools->get($name);

        $start = microtime(true);
        try {
            $resp = $tool->call($args, $context);
            $status = 'ok';

            return $resp;
        } catch (\Throwable $e) {
            $resp = ['error' => $e->getMessage()];
            $status = 'error';
            throw $e;
        } finally {
            DB::table('tool_invocations')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => data_get($context, 'user.id'),
                'workspace_id' => data_get($context, 'workspace.id'),
                'tool_slug' => $name,
                'command_slug' => data_get($scope, 'ctx.schedule.command_slug'),
                'fragment_id' => data_get($scope, 'ctx.fragment_id'),
                'request' => json_encode($args),
                'response' => json_encode($resp ?? null),
                'status' => $status,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'created_at' => now(),
            ]);
        }
    }
}
