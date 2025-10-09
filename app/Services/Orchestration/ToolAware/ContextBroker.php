<?php

namespace App\Services\Orchestration\ToolAware;

use App\Models\ChatSession;
use App\Services\Orchestration\ToolAware\Contracts\ContextBrokerInterface;
use App\Services\Orchestration\ToolAware\DTOs\ContextBundle;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Config;

class ContextBroker implements ContextBrokerInterface
{
    public function __construct(
        protected ToolRegistry $toolRegistry
    ) {}

    public function assemble(?int $sessionId, string $userMessage): ContextBundle
    {
        $conversationSummary = $this->buildConversationSummary($sessionId);
        $agentPrefs = $this->extractAgentPreferences($sessionId);
        $toolPreview = $this->previewRelevantTools($userMessage);

        return new ContextBundle(
            user_message: $userMessage,
            conversation_summary: $conversationSummary,
            agent_prefs: $agentPrefs,
            tool_registry_preview: $toolPreview
        );
    }

    protected function buildConversationSummary(?int $sessionId): string
    {
        if (!$sessionId) {
            return '';
        }

        $session = ChatSession::find($sessionId);
        if (!$session) {
            return '';
        }

        $messages = $session->messages ?? [];
        if (empty($messages)) {
            return '';
        }

        $maxLength = Config::get('fragments.tool_aware_turn.context.max_summary_length', 600);
        
        // Take last N messages for context
        $recentMessages = array_slice($messages, -5);
        
        $summary = '';
        foreach ($recentMessages as $msg) {
            $role = $msg['type'] ?? $msg['role'] ?? 'unknown';
            $content = $msg['message'] ?? $msg['md'] ?? '';
            
            if (empty($content)) {
                continue;
            }

            // Truncate long messages
            if (strlen($content) > 200) {
                $content = substr($content, 0, 200) . '...';
            }

            $summary .= "{$role}: {$content}\n";
        }

        // Ensure we don't exceed max length
        if (strlen($summary) > $maxLength) {
            $summary = substr($summary, -$maxLength);
            // Try to start at a message boundary
            $firstNewline = strpos($summary, "\n");
            if ($firstNewline !== false) {
                $summary = substr($summary, $firstNewline + 1);
            }
        }

        return trim($summary);
    }

    protected function extractAgentPreferences(?int $sessionId): array
    {
        if (!$sessionId) {
            return [];
        }

        $session = ChatSession::find($sessionId);
        if (!$session) {
            return [];
        }

        return [
            'model_provider' => $session->model_provider ?? null,
            'model_name' => $session->model_name ?? null,
        ];
    }

    protected function previewRelevantTools(string $userMessage): array
    {
        $previewCount = Config::get('fragments.tool_aware_turn.context.tool_preview_count', 5);
        
        // Get all available tools
        $allTools = $this->toolRegistry->all();
        
        // For MVP, return first N tools as preview
        // TODO: In future, use embeddings or keyword matching to find most relevant tools
        $preview = [];
        $count = 0;
        
        foreach ($allTools as $slug => $tool) {
            if ($count >= $previewCount) {
                break;
            }

            if (!$tool->isEnabled()) {
                continue;
            }

            $preview[] = [
                'slug' => $slug,
                'capabilities' => $tool->capabilities(),
                'schema' => $tool->getConfigSchema(),
            ];
            
            $count++;
        }

        return $preview;
    }
}
