<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatTelemetry
{
    private const LOG_CHANNEL = 'chat-telemetry';

    /**
     * Log chat message receipt and initial validation
     */
    public static function logMessageReceived(array $data): void
    {
        self::log('chat.message.received', [
            'content_length' => strlen($data['content'] ?? ''),
            'has_conversation_id' => isset($data['conversation_id']),
            'has_session_id' => isset($data['session_id']),
            'attachment_count' => count($data['attachments'] ?? []),
            'provider_requested' => $data['provider'] ?? null,
            'model_requested' => $data['model'] ?? null,
        ]);
    }

    /**
     * Log provider and model selection decisions
     */
    public static function logProviderSelection(array $selection): void
    {
        self::log('chat.provider.selected', [
            'provider' => $selection['provider'],
            'model' => $selection['model'],
            'source' => $selection['source'], // 'request', 'session', 'fallback'
            'session_id' => $selection['session_id'] ?? null,
        ]);
    }

    /**
     * Log fragment creation for user message
     */
    public static function logUserFragmentCreated(string $fragmentId, array $metadata): void
    {
        self::log('chat.fragment.user.created', [
            'fragment_id' => $fragmentId,
            'conversation_id' => $metadata['conversation_id'] ?? null,
            'session_id' => $metadata['session_id'] ?? null,
            'content_length' => strlen($metadata['content'] ?? ''),
        ]);
    }

    /**
     * Log chat session caching
     */
    public static function logSessionCached(string $messageId, array $sessionData): void
    {
        self::log('chat.session.cached', [
            'message_id' => $messageId,
            'provider' => $sessionData['provider'],
            'model' => $sessionData['model'],
            'conversation_id' => $sessionData['conversation_id'],
            'message_count' => count($sessionData['messages'] ?? []),
            'has_session_id' => isset($sessionData['session_id']),
        ]);
    }

    /**
     * Log streaming initiation
     */
    public static function logStreamingStarted(string $messageId, array $sessionData): void
    {
        self::log('chat.streaming.started', [
            'message_id' => $messageId,
            'provider' => $sessionData['provider'],
            'model' => $sessionData['model'],
            'conversation_id' => $sessionData['conversation_id'],
            'input_message_count' => count($sessionData['messages'] ?? []),
        ]);
    }

    /**
     * Log time to first token (critical streaming performance metric)
     */
    public static function logFirstToken(string $messageId, array $metrics): void
    {
        self::log('chat.streaming.first_token', [
            'message_id' => $messageId,
            'time_to_first_token_ms' => $metrics['time_to_first_token_ms'] ?? 0,
            'provider' => $metrics['provider'] ?? 'unknown',
            'model' => $metrics['model'] ?? 'unknown',
        ]);
    }

    /**
     * Log streaming progress
     */
    public static function logStreamingProgress(string $messageId, array $metrics): void
    {
        self::log('chat.streaming.progress', [
            'message_id' => $messageId,
            'delta_count' => $metrics['delta_count'] ?? 0,
            'total_length' => $metrics['total_length'] ?? 0,
            'elapsed_ms' => $metrics['elapsed_ms'] ?? 0,
            'tokens_per_second' => $metrics['tokens_per_second'] ?? null,
            'time_since_first_token_ms' => $metrics['time_since_first_token_ms'] ?? null,
        ]);
    }

    /**
     * Log streaming completion
     */
    public static function logStreamingCompleted(string $messageId, array $result): void
    {
        self::log('chat.streaming.completed', [
            'message_id' => $messageId,
            'final_message_length' => strlen($result['final_message'] ?? ''),
            'duration_ms' => $result['duration_ms'] ?? 0,
            'token_usage' => $result['token_usage'] ?? null,
            'provider_response_size' => self::calculateResponseSize($result['provider_response'] ?? null),
        ]);
    }

    /**
     * Log streaming errors
     */
    public static function logStreamingError(string $messageId, \Throwable $error, array $context = []): void
    {
        self::log('chat.streaming.error', [
            'message_id' => $messageId,
            'error_type' => get_class($error),
            'error_message' => $error->getMessage(),
            'error_code' => $error->getCode(),
            'duration_ms' => $context['duration_ms'] ?? 0,
            'provider' => $context['provider'] ?? null,
            'model' => $context['model'] ?? null,
        ], 'error');
    }

    /**
     * Log assistant fragment processing start
     */
    public static function logAssistantFragmentProcessingStarted(array $data): void
    {
        self::log('chat.fragment.assistant.processing.started', [
            'conversation_id' => $data['conversation_id'] ?? null,
            'user_fragment_id' => $data['user_fragment_id'] ?? null,
            'provider' => $data['provider'] ?? null,
            'model' => $data['model'] ?? null,
            'message_length' => strlen($data['message'] ?? ''),
            'latency_ms' => $data['latency_ms'] ?? null,
        ]);
    }

    /**
     * Log assistant fragment created
     */
    public static function logAssistantFragmentCreated(string $fragmentId, array $metadata): void
    {
        self::log('chat.fragment.assistant.created', [
            'fragment_id' => $fragmentId,
            'conversation_id' => $metadata['conversation_id'] ?? null,
            'in_reply_to_id' => $metadata['in_reply_to_id'] ?? null,
            'provider' => $metadata['provider'] ?? null,
            'model' => $metadata['model'] ?? null,
            'fragment_creation_time_ms' => $metadata['fragment_creation_time_ms'] ?? null,
        ]);
    }

    /**
     * Log fragment relationship correlation for conversation tracking
     */
    public static function logFragmentCorrelation(array $correlationData): void
    {
        self::log('chat.fragment.correlation', [
            'user_fragment_id' => $correlationData['user_fragment_id'] ?? null,
            'assistant_fragment_id' => $correlationData['assistant_fragment_id'] ?? null,
            'conversation_id' => $correlationData['conversation_id'] ?? null,
            'processing_chain' => $correlationData['processing_chain'] ?? [],
            'total_conversation_time_ms' => $correlationData['total_conversation_time_ms'] ?? null,
        ]);
    }

    /**
     * Log enrichment pipeline start
     */
    public static function logEnrichmentPipelineStarted(string $fragmentId, array $steps): void
    {
        self::log('chat.enrichment.pipeline.started', [
            'fragment_id' => $fragmentId,
            'pipeline_steps' => array_map(fn ($step) => class_basename($step), $steps),
            'step_count' => count($steps),
        ]);
    }

    /**
     * Log enrichment pipeline completion
     */
    public static function logEnrichmentPipelineCompleted(string $fragmentId, float $durationMs): void
    {
        self::log('chat.enrichment.pipeline.completed', [
            'fragment_id' => $fragmentId,
            'duration_ms' => round($durationMs, 2),
        ]);
    }

    /**
     * Log individual enrichment step execution
     */
    public static function logEnrichmentStep(string $fragmentId, array $stepData): void
    {
        $level = $stepData['success'] ? 'info' : 'error';

        self::log('chat.enrichment.step', [
            'fragment_id' => $fragmentId,
            'step' => $stepData['step'],
            'duration_ms' => $stepData['duration_ms'],
            'success' => $stepData['success'],
            'error' => $stepData['error'] ?? null,
            'error_class' => $stepData['error_class'] ?? null,
        ], $level);
    }

    /**
     * Log enrichment pipeline errors
     */
    public static function logEnrichmentPipelineError(string $fragmentId, \Throwable $error, float $durationMs): void
    {
        self::log('chat.enrichment.pipeline.error', [
            'fragment_id' => $fragmentId,
            'duration_ms' => round($durationMs, 2),
            'error_type' => get_class($error),
            'error_message' => $error->getMessage(),
            'error_code' => $error->getCode(),
        ], 'error');
    }

    /**
     * Log session message addition
     */
    public static function logSessionMessageAdded(int $sessionId, array $messageData): void
    {
        self::log('chat.session.message.added', [
            'session_id' => $sessionId,
            'message_type' => $messageData['type'] ?? null,
            'fragment_id' => $messageData['fragment_id'] ?? null,
            'message_length' => strlen($messageData['message'] ?? ''),
        ]);
    }

    /**
     * Log validation errors
     */
    public static function logValidationError(array $errors, array $input): void
    {
        self::log('chat.validation.error', [
            'error_fields' => array_keys($errors),
            'error_count' => count($errors),
            'input_fields' => array_keys($input),
            'content_length' => strlen($input['content'] ?? ''),
        ], 'error');
    }

    /**
     * Log chat session performance metrics
     */
    public static function logSessionMetrics(string $messageId, array $metrics): void
    {
        self::log('chat.session.metrics', [
            'message_id' => $messageId,
            'total_duration_ms' => $metrics['total_duration_ms'] ?? 0,
            'validation_duration_ms' => $metrics['validation_duration_ms'] ?? 0,
            'fragment_creation_duration_ms' => $metrics['fragment_creation_duration_ms'] ?? 0,
            'provider_selection_duration_ms' => $metrics['provider_selection_duration_ms'] ?? 0,
            'cache_duration_ms' => $metrics['cache_duration_ms'] ?? 0,
            'streaming_duration_ms' => $metrics['streaming_duration_ms'] ?? 0,
            'enrichment_duration_ms' => $metrics['enrichment_duration_ms'] ?? 0,
        ]);
    }

    /**
     * Core logging method with correlation context
     */
    private static function log(string $event, array $data, string $level = 'info'): void
    {
        $logData = [
            'event' => $event,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'event_id' => (string) Str::uuid(),
                'service' => 'chat-pipeline',
                'version' => '1.0',
            ],
        ];

        // Add correlation context if available
        if (CorrelationContext::hasContext()) {
            $logData['correlation'] = CorrelationContext::forLogging();
        }

        // Use appropriate log level
        match ($level) {
            'error' => Log::channel(self::LOG_CHANNEL)->error($event, $logData),
            'warning' => Log::channel(self::LOG_CHANNEL)->warning($event, $logData),
            'debug' => Log::channel(self::LOG_CHANNEL)->debug($event, $logData),
            default => Log::channel(self::LOG_CHANNEL)->info($event, $logData),
        };
    }

    /**
     * Privacy-safe calculation of response data size
     */
    private static function calculateResponseSize($response): ?int
    {
        if ($response === null) {
            return null;
        }

        if (is_string($response)) {
            return strlen($response);
        }

        if (is_array($response)) {
            return strlen(json_encode($response));
        }

        return strlen(serialize($response));
    }

    /**
     * Generate privacy-safe content hash for deduplication tracking
     */
    public static function generateContentHash(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Log complete chat transaction summary
     */
    public static function logChatTransactionSummary(array $summary): void
    {
        self::log('chat.transaction.summary', [
            'message_id' => $summary['message_id'],
            'conversation_id' => $summary['conversation_id'],
            'total_duration_ms' => $summary['total_duration_ms'],
            'user_fragment_id' => $summary['user_fragment_id'],
            'assistant_fragment_id' => $summary['assistant_fragment_id'] ?? null,
            'provider' => $summary['provider'],
            'model' => $summary['model'],
            'input_length' => $summary['input_length'],
            'output_length' => $summary['output_length'] ?? 0,
            'token_usage' => $summary['token_usage'] ?? null,
            'success' => $summary['success'] ?? true,
            'error_occurred' => $summary['error_occurred'] ?? false,
            'enrichment_completed' => $summary['enrichment_completed'] ?? false,
        ]);
    }
}
