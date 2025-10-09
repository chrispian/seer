<?php

namespace App\Services\Orchestration\ToolAware\Guards;

use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class StepLimiter
{
    /**
     * Enforce maximum step limit on tool plan
     *
     * @param ToolPlan $plan
     * @return ToolPlan Limited plan
     */
    public function limit(ToolPlan $plan): ToolPlan
    {
        $maxSteps = Config::get('fragments.tool_aware_turn.limits.max_steps_per_turn', 10);

        if ($plan->stepCount() <= $maxSteps) {
            return $plan;
        }

        Log::warning('Tool plan exceeds max step limit, truncating', [
            'requested_steps' => $plan->stepCount(),
            'max_steps' => $maxSteps,
        ]);

        // Truncate to max steps
        $plan->plan_steps = array_slice($plan->plan_steps, 0, $maxSteps);

        // Update selected_tool_ids
        $plan->selected_tool_ids = array_unique(
            array_column($plan->plan_steps, 'tool_id')
        );

        return $plan;
    }

    /**
     * Check if plan is within limits without modifying
     */
    public function isWithinLimit(ToolPlan $plan): bool
    {
        $maxSteps = Config::get('fragments.tool_aware_turn.limits.max_steps_per_turn', 10);
        return $plan->stepCount() <= $maxSteps;
    }
}
