<?php

namespace App\Services\Orchestration\ToolAware;

use App\Services\Orchestration\ToolAware\DTOs\ExecutionTrace;
use App\Services\Orchestration\ToolAware\DTOs\OutcomeSummary;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class OutcomeSummarizer
{
    public function summarize(ExecutionTrace $trace): OutcomeSummary
    {
        $redactedResults = $this->redactResults($trace);
        
        $promptTemplate = file_get_contents(__DIR__ . '/Prompts/outcome_summary.txt');
        
        $prompt = str_replace(
            '{results}',
            json_encode($redactedResults, JSON_PRETTY_PRINT),
            $promptTemplate
        );

        $model = Config::get('fragments.tool_aware_turn.models.summarizer', 'gpt-4o-mini');

        try {
            $response = $this->callLLM($prompt, $model);
            $summary = $this->parseResponse($response);

            Log::info('Outcome summary created', [
                'correlation_id' => $trace->correlation_id,
                'confidence' => $summary->confidence,
                'key_facts_count' => count($summary->key_facts),
            ]);

            return $summary;

        } catch (\JsonException $e) {
            Log::warning('Summarizer returned invalid JSON, creating fallback summary');

            // Create fallback summary
            return new OutcomeSummary(
                short_summary: $this->createFallbackSummary($trace),
                key_facts: [],
                links: [],
                confidence: 0.5
            );
        }
    }

    protected function redactResults(ExecutionTrace $trace): array
    {
        $redactEnabled = Config::get('fragments.tool_aware_turn.features.redact_logs', true);
        
        if (!$redactEnabled) {
            return array_map(fn($step) => $step->toArray(), $trace->steps);
        }

        // Redact sensitive patterns
        $sensitivePatterns = [
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', // emails
            '/\b[A-Z0-9]{20,}\b/', // API keys (long alphanumeric strings)
            '/sk-[a-zA-Z0-9]{20,}/', // OpenAI keys
            '/Bearer\s+[a-zA-Z0-9._-]+/', // Bearer tokens
        ];

        $redacted = [];
        foreach ($trace->steps as $step) {
            $stepArray = $step->toArray();
            
            // Redact result field
            if (isset($stepArray['result'])) {
                $resultJson = json_encode($stepArray['result']);
                foreach ($sensitivePatterns as $pattern) {
                    $resultJson = preg_replace($pattern, '[REDACTED]', $resultJson);
                }
                $stepArray['result'] = json_decode($resultJson, true);
            }

            $redacted[] = $stepArray;
        }

        return $redacted;
    }

    protected function createFallbackSummary(ExecutionTrace $trace): string
    {
        $successCount = 0;
        $errorCount = 0;

        foreach ($trace->steps as $step) {
            if ($step->success) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        if ($errorCount > 0) {
            return "Executed {$successCount} tool(s) successfully with {$errorCount} error(s).";
        }

        return "Successfully executed {$successCount} tool(s).";
    }

    protected function callLLM(string $prompt, string $model): string
    {
        $provider = Config::get('fragments.models.default_provider', 'openai');
        
        $providerManager = app(\App\Services\AI\AIProviderManager::class);

        $systemMessage = 'You are a summarization agent that responds only with valid JSON.';
        $fullPrompt = "{$systemMessage}\n\n{$prompt}";

        $response = $providerManager->generateText($fullPrompt, [
            'request_type' => 'outcome_summarization',
        ], [
            'model' => $model,
            'temperature' => 0.3,
            'max_tokens' => 800,
        ]);

        return $response['text'] ?? '';
    }

    protected function parseResponse(string $response): OutcomeSummary
    {
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```json\s*/m', '', $cleaned);
        $cleaned = preg_replace('/^```\s*/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

        return OutcomeSummary::fromArray($data);
    }
}
