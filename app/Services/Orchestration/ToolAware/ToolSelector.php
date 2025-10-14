<?php

namespace App\Services\Orchestration\ToolAware;

use App\Services\Orchestration\ToolAware\Contracts\ToolSelectorInterface;
use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Orchestration\ToolAware\DTOs\ToolPlan;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ToolSelector implements ToolSelectorInterface
{
    public function __construct(
        protected ToolRegistry $toolRegistry
    ) {}

    public function selectTools(string $goal, ContextBundle $context): ToolPlan
    {
        $toolsSlice = $this->getToolsSliceForGoal($goal);

        $promptTemplate = file_get_contents(__DIR__.'/Prompts/tool_candidates.txt');

        $prompt = str_replace(
            ['{high_level_goal}', '{tools}'],
            [$goal, json_encode($toolsSlice, JSON_PRETTY_PRINT)],
            $promptTemplate
        );

        // Use session model and provider if available, otherwise fall back to config
        $model = $context->agent_prefs['model_name'] ?? Config::get('fragments.tool_aware_turn.models.candidate_selector', 'gpt-4o-mini');
        $provider = $context->agent_prefs['model_provider'] ?? $this->getProviderForModel($model);
        $retryOnFailure = Config::get('fragments.tool_aware_turn.features.retry_on_parse_failure', true);

        try {
            $response = $this->callLLM($prompt, $model, $provider);
            $plan = $this->parseResponse($response);

            // Apply permission filtering and arg resolution
            $plan = $this->filterByPermissions($plan);
            $plan = $this->fillMissingArgs($plan, $context);

            Log::info('Tool plan created', [
                'selected_tools' => $plan->selected_tool_ids,
                'step_count' => $plan->stepCount(),
            ]);

            return $plan;

        } catch (\JsonException $e) {
            if (! $retryOnFailure) {
                throw new \RuntimeException('Tool selector LLM returned invalid JSON: '.$e->getMessage());
            }

            Log::warning('Tool selector returned invalid JSON, retrying');

            $retryPrompt = $prompt."\n\nIMPORTANT: Respond with ONLY valid JSON, no additional text.";

            try {
                $response = $this->callLLM($retryPrompt, $model, $provider);
                $plan = $this->parseResponse($response);

                $plan = $this->filterByPermissions($plan);
                $plan = $this->fillMissingArgs($plan, $context);

                Log::info('Tool plan created on retry', [
                    'selected_tools' => $plan->selected_tool_ids,
                    'step_count' => $plan->stepCount(),
                ]);

                return $plan;

            } catch (\JsonException $retryError) {
                throw new \RuntimeException('Tool selector returned invalid JSON after retry: '.$retryError->getMessage());
            }
        }
    }

    protected function getToolsSliceForGoal(string $goal): array
    {
        $this->checkAndRefreshMcpCache();

        // Only get builtin tools from tool_definitions (exclude MCP tools to reduce prompt size)
        // Also exclude the generic 'mcp' wrapper tool since we're not using MCP tools in this pipeline
        $toolDefinitions = \App\Models\ToolDefinition::enabled()
            ->where('source', 'builtin')
            ->whereNotIn('slug', ['mcp'])  // Exclude MCP wrapper tool
            ->get();

        $slice = [];
        foreach ($toolDefinitions as $toolDef) {
            $slice[] = $toolDef->toPromptFormat();
        }

        Log::info('Tool selection slice prepared', [
            'goal' => substr($goal, 0, 100),
            'tool_count' => count($slice),
            'tools' => array_column($slice, 'name'),
        ]);

        // TODO: In future, use semantic matching to filter by goal
        // For now, return only builtin native tools (shell, fs, project_fs)
        return $slice;
    }

    protected function checkAndRefreshMcpCache(): void
    {
        $autoRefresh = Config::get('fragments.tool_aware_turn.cache.auto_refresh', true);

        if (! $autoRefresh) {
            return;
        }

        $ttlHours = Config::get('fragments.tool_aware_turn.cache.mcp_ttl_hours', 24);
        $staleThreshold = now()->subHours($ttlHours);

        $oldestSync = \App\Models\ToolDefinition::mcp()
            ->whereNotNull('synced_at')
            ->min('synced_at');

        if (! $oldestSync || $oldestSync < $staleThreshold) {
            Log::info('MCP cache is stale, dispatching refresh job', [
                'oldest_sync' => $oldestSync,
                'threshold' => $staleThreshold,
                'ttl_hours' => $ttlHours,
            ]);

            dispatch(new \App\Jobs\RefreshMcpToolsJob)->onQueue('low');
        }
    }

    protected function filterByPermissions(ToolPlan $plan): ToolPlan
    {
        // TODO: Implement actual permission checking against user/agent allow-list
        // For MVP, accept all tools that are enabled

        $allowedTools = Config::get('fragments.tools.allowed', []);

        if (empty($allowedTools)) {
            // No restrictions if allow list is empty
            return $plan;
        }

        $filteredSteps = [];
        foreach ($plan->plan_steps as $step) {
            if (in_array($step['tool_id'], $allowedTools, true)) {
                $filteredSteps[] = $step;
            } else {
                Log::warning('Tool filtered by permission', ['tool_id' => $step['tool_id']]);
            }
        }

        $plan->plan_steps = $filteredSteps;

        return $plan;
    }

    protected function fillMissingArgs(ToolPlan $plan, ContextBundle $context): ToolPlan
    {
        // TODO: Implement smart arg resolution from context
        // For MVP, leave args as-is from LLM
        return $plan;
    }

    protected function callLLM(string $prompt, string $model, ?string $provider = null): string
    {
        // Use provided provider or infer from model name
        if ($provider === null) {
            $provider = $this->getProviderForModel($model);
        }

        $providerManager = app(\App\Services\AI\AIProviderManager::class);

        $systemMessage = 'You are a tool selection agent that responds only with valid JSON.';
        $fullPrompt = "{$systemMessage}\n\n{$prompt}";

        $response = $providerManager->generateText($fullPrompt, [
            'request_type' => 'tool_selection',
            'provider' => $provider,
            'model' => $model,
        ], [
            'temperature' => 0.2,
            'max_tokens' => 1000,
        ]);

        return $response['text'] ?? '';
    }

    protected function getProviderForModel(string $model): string
    {
        if (str_starts_with($model, 'gpt-') || str_starts_with($model, 'o1-')) {
            return 'openai';
        }
        if (str_starts_with($model, 'claude-')) {
            return 'anthropic';
        }
        if (str_contains($model, '/')) {
            return explode('/', $model)[0];
        }
        
        return Config::get('fragments.models.default_provider', 'openai');
    }

    protected function parseResponse(string $response): ToolPlan
    {
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```json\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^```\s*/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

        return ToolPlan::fromArray($data);
    }
}
