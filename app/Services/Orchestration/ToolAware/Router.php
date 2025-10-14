<?php

namespace App\Services\Orchestration\ToolAware;

use App\Services\Orchestration\ToolAware\Contracts\RouterInterface;
use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Orchestration\ToolAware\DTOs\RouterDecision;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Router implements RouterInterface
{
    public function decide(ContextBundle $context): RouterDecision
    {
        $promptTemplate = file_get_contents(__DIR__.'/Prompts/router_decision.txt');

        $prompt = str_replace(
            ['{conversation_summary}', '{user_message}'],
            [$context->conversation_summary, $context->user_message],
            $promptTemplate
        );

        // Use session model and provider if available, otherwise fall back to config
        $sessionModel = $context->agent_prefs['model_name'] ?? null;
        $sessionProvider = $context->agent_prefs['model_provider'] ?? null;
        $model = $sessionModel ?? Config::get('fragments.tool_aware_turn.models.router', 'gpt-4o-mini');
        $provider = $sessionProvider ?? $this->getProviderForModel($model);
        $retryOnFailure = Config::get('fragments.tool_aware_turn.features.retry_on_parse_failure', true);

        Log::info('Router::decide - Model selection', [
            'component' => 'ToolAware/Router',
            'session_model' => $sessionModel,
            'session_provider' => $sessionProvider,
            'selected_model' => $model,
            'selected_provider' => $provider,
            'used_session_prefs' => $sessionModel !== null,
        ]);

        try {
            $response = $this->callLLM($prompt, $model, $provider);
            $decision = $this->parseResponse($response);

            Log::info('Router decision made', [
                'needs_tools' => $decision->needs_tools,
                'goal' => $decision->high_level_goal,
            ]);

            return $decision;

        } catch (\JsonException $e) {
            if (! $retryOnFailure) {
                throw new \RuntimeException('Router LLM returned invalid JSON: '.$e->getMessage());
            }

            Log::warning('Router returned invalid JSON, retrying with explicit instruction');

            // Retry with explicit JSON-only instruction
            $retryPrompt = $prompt."\n\nIMPORTANT: Respond with ONLY valid JSON, no additional text or formatting.";

            try {
                $response = $this->callLLM($retryPrompt, $model, $provider);
                $decision = $this->parseResponse($response);

                Log::info('Router decision made on retry', [
                    'needs_tools' => $decision->needs_tools,
                    'goal' => $decision->high_level_goal,
                ]);

                return $decision;

            } catch (\JsonException $retryError) {
                throw new \RuntimeException('Router LLM returned invalid JSON after retry: '.$retryError->getMessage());
            }
        }
    }

    protected function callLLM(string $prompt, string $model, ?string $provider = null): string
    {
        // Use provided provider or infer from model name
        if ($provider === null) {
            $provider = $this->getProviderForModel($model);
        }

        $providerManager = app(\App\Services\AI\AIProviderManager::class);

        $systemMessage = 'You are a routing agent that responds only with valid JSON.';
        $fullPrompt = "{$systemMessage}\n\n{$prompt}";

        $response = $providerManager->generateText($fullPrompt, [
            'request_type' => 'tool_routing',
            'provider' => $provider,
            'model' => $model,
        ], [
            'temperature' => 0.1,
            'max_tokens' => 500,
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
            // Format like "openai/gpt-4" or "anthropic/claude-3"
            return explode('/', $model)[0];
        }
        
        return Config::get('fragments.models.default_provider', 'openai');
    }

    protected function parseResponse(string $response): RouterDecision
    {
        // Clean response - remove markdown code blocks if present
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```json\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^```\s*/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

        // Validate required fields
        if (! isset($data['needs_tools']) || ! is_bool($data['needs_tools'])) {
            throw new \JsonException('Missing or invalid needs_tools field');
        }

        return RouterDecision::fromArray($data);
    }
}
