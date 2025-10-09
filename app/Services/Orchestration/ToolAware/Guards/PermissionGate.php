<?php

namespace App\Services\Orchestration\ToolAware\Guards;

use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PermissionGate
{
    /**
     * Filter tool plan by user/agent permissions
     *
     * @return ToolPlan Filtered plan
     */
    public function filter(ToolPlan $plan, ?int $userId = null, ?string $agentId = null): ToolPlan
    {
        $allowedTools = $this->getAllowedTools($userId, $agentId);

        if (empty($allowedTools)) {
            // No restrictions - allow all enabled tools
            Log::debug('No permission restrictions, allowing all tools');

            return $plan;
        }

        $filteredSteps = [];
        $blockedTools = [];

        foreach ($plan->plan_steps as $step) {
            $toolId = $step['tool_id'] ?? null;

            if (! $toolId) {
                continue;
            }

            if ($this->isAllowed($toolId, $allowedTools)) {
                $filteredSteps[] = $step;
            } else {
                $blockedTools[] = $toolId;
                Log::warning('Tool blocked by permission gate', [
                    'tool_id' => $toolId,
                    'user_id' => $userId,
                    'agent_id' => $agentId,
                ]);
            }
        }

        if (! empty($blockedTools)) {
            Log::info('Tools blocked by permissions', [
                'blocked_count' => count($blockedTools),
                'blocked_tools' => $blockedTools,
                'allowed_count' => count($filteredSteps),
            ]);
        }

        // Update plan with filtered steps
        $plan->plan_steps = $filteredSteps;

        // Update selected_tool_ids to match filtered steps
        $plan->selected_tool_ids = array_unique(
            array_column($filteredSteps, 'tool_id')
        );

        return $plan;
    }

    /**
     * Get allowed tools for user/agent
     */
    protected function getAllowedTools(?int $userId, ?string $agentId): array
    {
        // TODO: In production, check user/agent-specific permissions from database
        // For MVP, use global allow-list from config
        return Config::get('fragments.tools.allowed', []);
    }

    /**
     * Check if a specific tool is allowed
     */
    protected function isAllowed(string $toolId, array $allowedTools): bool
    {
        // If allow list is empty, all tools are allowed
        if (empty($allowedTools)) {
            return true;
        }

        // Exact match
        if (in_array($toolId, $allowedTools, true)) {
            return true;
        }

        // Wildcard matching (e.g., "gmail.*" matches "gmail.send", "gmail.list")
        foreach ($allowedTools as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($toolId, $prefix.'.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if tool requires write permissions
     */
    public function requiresWritePermission(string $toolId): bool
    {
        $writeTools = [
            'gmail.send',
            'gmail.delete',
            'calendar.create',
            'calendar.update',
            'calendar.delete',
            'fs.write',
            'shell', // Shell is always considered write
        ];

        foreach ($writeTools as $writeTool) {
            if ($toolId === $writeTool || str_starts_with($toolId, $writeTool.'.')) {
                return true;
            }
        }

        return false;
    }
}
