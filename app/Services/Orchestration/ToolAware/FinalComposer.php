<?php

namespace App\Services\Orchestration\ToolAware;

use App\Services\Orchestration\ToolAware\Contracts\ComposerInterface;
use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Orchestration\ToolAware\DTOs\OutcomeSummary;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class FinalComposer implements ComposerInterface
{
    public function compose(
        ContextBundle $context,
        ?OutcomeSummary $summary = null,
        ?string $correlationId = null
    ): string {
        // If no tools were used, fall back to direct LLM response
        if ($summary === null) {
            return $this->directResponse($context);
        }

        $promptTemplate = file_get_contents(__DIR__ . '/Prompts/final_composer.txt');
        
        $prompt = str_replace(
            ['{user_message}', '{summary}'],
            [$context->user_message, json_encode($summary->toArray(), JSON_PRETTY_PRINT)],
            $promptTemplate
        );

        $model = Config::get('fragments.tool_aware_turn.models.composer', 'gpt-4o');

        try {
            $response = $this->callLLM($prompt, $model);

            Log::info('Final response composed', [
                'correlation_id' => $correlationId,
                'response_length' => strlen($response),
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to compose final response', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to simple summary
            return $this->fallbackResponse($summary);
        }
    }

    protected function directResponse(ContextBundle $context): string
    {
        $provider = Config::get('fragments.models.default_provider', 'openai');
        $model = Config::get('fragments.tool_aware_turn.models.composer', 'gpt-4o');
        
        $providerManager = app(\App\Services\AI\AIProviderManager::class);

        $systemMessage = 'You are a helpful assistant.';
        $fullPrompt = $systemMessage;

        // Add conversation history if available
        if (!empty($context->conversation_summary)) {
            $fullPrompt .= "\n\nPrevious conversation:\n{$context->conversation_summary}";
        }

        $fullPrompt .= "\n\nUser: {$context->user_message}";

        $response = $providerManager->generateText($fullPrompt, [
            'request_type' => 'final_composition',
        ], [
            'model' => $model,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        return $response['text'] ?? 'I apologize, but I was unable to generate a response.';
    }

    protected function fallbackResponse(OutcomeSummary $summary): string
    {
        $response = $summary->short_summary;

        if (!empty($summary->key_facts)) {
            $response .= "\n\n**Key details:**\n";
            foreach ($summary->key_facts as $fact) {
                $response .= "- {$fact}\n";
            }
        }

        if (!empty($summary->links)) {
            $response .= "\n**Links:**\n";
            foreach ($summary->links as $link) {
                $response .= "- {$link}\n";
            }
        }

        if ($summary->confidence < 0.7) {
            $response .= "\n\n_Note: This information may be incomplete or uncertain._";
        }

        return trim($response);
    }

    protected function callLLM(string $prompt, string $model): string
    {
        $provider = Config::get('fragments.models.default_provider', 'openai');
        
        $providerManager = app(\App\Services\AI\AIProviderManager::class);

        $systemMessage = 'You are a helpful assistant that provides clear, concise responses.';
        $fullPrompt = "{$systemMessage}\n\n{$prompt}";

        $response = $providerManager->generateText($fullPrompt, [
            'request_type' => 'direct_response',
        ], [
            'model' => $model,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        return $response['text'] ?? '';
    }
}
