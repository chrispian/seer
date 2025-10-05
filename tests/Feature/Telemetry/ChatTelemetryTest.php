<?php

namespace Tests\Feature\Telemetry;

use App\Services\Telemetry\ChatTelemetry;
use App\Services\Telemetry\CorrelationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatTelemetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear correlation context between tests
        CorrelationContext::clear();
    }

    public function test_logs_message_received_with_correlation_context()
    {
        $correlationId = (string) Str::uuid();
        CorrelationContext::set($correlationId);

        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->with('chat.message.received', \Mockery::on(function ($data) use ($correlationId) {
                return $data['event'] === 'chat.message.received'
                    && $data['data']['content_length'] === 12
                    && $data['data']['attachment_count'] === 2
                    && $data['correlation']['correlation_id'] === $correlationId;
            }));

        ChatTelemetry::logMessageReceived([
            'content' => 'Hello there!',
            'attachments' => ['file1.pdf', 'file2.jpg'],
            'provider' => 'openai',
        ]);
    }

    public function test_logs_provider_selection()
    {
        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->with('chat.provider.selected', \Mockery::on(function ($data) {
                return $data['event'] === 'chat.provider.selected'
                    && $data['data']['provider'] === 'openai'
                    && $data['data']['model'] === 'gpt-4'
                    && $data['data']['source'] === 'request';
            }));

        ChatTelemetry::logProviderSelection([
            'provider' => 'openai',
            'model' => 'gpt-4',
            'source' => 'request',
        ]);
    }

    public function test_logs_streaming_error_with_context()
    {
        $messageId = (string) Str::uuid();
        $error = new \RuntimeException('Provider timeout', 500);

        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->andReturnSelf()
            ->shouldReceive('error')
            ->once()
            ->with('chat.streaming.error', \Mockery::on(function ($data) use ($messageId) {
                return $data['event'] === 'chat.streaming.error'
                    && $data['data']['message_id'] === $messageId
                    && $data['data']['error_type'] === 'RuntimeException'
                    && $data['data']['error_message'] === 'Provider timeout'
                    && $data['data']['error_code'] === 500;
            }));

        ChatTelemetry::logStreamingError($messageId, $error, [
            'duration_ms' => 1500,
            'provider' => 'openai',
        ]);
    }

    public function test_logs_transaction_summary()
    {
        $messageId = (string) Str::uuid();
        $conversationId = (string) Str::uuid();

        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->with('chat.transaction.summary', \Mockery::on(function ($data) use ($messageId, $conversationId) {
                return $data['event'] === 'chat.transaction.summary'
                    && $data['data']['message_id'] === $messageId
                    && $data['data']['conversation_id'] === $conversationId
                    && $data['data']['success'] === true
                    && $data['data']['total_duration_ms'] === 2500.0;
            }));

        ChatTelemetry::logChatTransactionSummary([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'total_duration_ms' => 2500.0,
            'user_fragment_id' => 'user-frag-123',
            'assistant_fragment_id' => 'assistant-frag-456',
            'provider' => 'openai',
            'model' => 'gpt-4',
            'input_length' => 100,
            'output_length' => 250,
            'success' => true,
        ]);
    }

    public function test_generates_privacy_safe_content_hash()
    {
        $content = 'This is sensitive user content that should not be logged';
        $hash = ChatTelemetry::generateContentHash($content);

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash)); // SHA-256 hex length
        $this->assertNotEquals($content, $hash);

        // Same content should generate same hash
        $this->assertEquals($hash, ChatTelemetry::generateContentHash($content));
    }

    public function test_includes_event_metadata()
    {
        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->with('chat.message.received', \Mockery::on(function ($data) {
                return isset($data['meta']['timestamp'])
                    && isset($data['meta']['event_id'])
                    && isset($data['meta']['service'])
                    && $data['meta']['service'] === 'chat-pipeline'
                    && isset($data['meta']['version']);
            }));

        ChatTelemetry::logMessageReceived(['content' => 'test']);
    }

    /** @test */
    public function it_logs_first_token_metrics()
    {
        $messageId = 'test-message-123';
        $metrics = [
            'time_to_first_token_ms' => 245.67,
            'provider' => 'openai',
            'model' => 'gpt-4',
        ];

        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('chat.streaming.first_token', \Mockery::on(function ($data) use ($messageId, $metrics) {
                return $data['data']['message_id'] === $messageId &&
                       $data['data']['time_to_first_token_ms'] === $metrics['time_to_first_token_ms'] &&
                       $data['data']['provider'] === $metrics['provider'] &&
                       $data['data']['model'] === $metrics['model'] &&
                       isset($data['meta']['correlation_id']) &&
                       isset($data['meta']['timestamp']);
            }))
            ->once();

        ChatTelemetry::logFirstToken($messageId, $metrics);
    }

    /** @test */
    public function it_logs_enrichment_step_success()
    {
        $fragmentId = 'fragment-456';
        $stepData = [
            'step' => 'ExtractJsonMetadata',
            'duration_ms' => 12.34,
            'success' => true,
        ];

        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('chat.enrichment.step', \Mockery::on(function ($data) use ($fragmentId, $stepData) {
                return $data['data']['fragment_id'] === $fragmentId &&
                       $data['data']['step'] === $stepData['step'] &&
                       $data['data']['duration_ms'] === $stepData['duration_ms'] &&
                       $data['data']['success'] === true &&
                       $data['data']['error'] === null;
            }))
            ->once();

        ChatTelemetry::logEnrichmentStep($fragmentId, $stepData);
    }

    /** @test */
    public function it_logs_fragment_correlation()
    {
        $correlationData = [
            'user_fragment_id' => 'user-fragment-123',
            'assistant_fragment_id' => 'assistant-fragment-456',
            'conversation_id' => 'conversation-789',
            'processing_chain' => [
                'fragment_creation_ms' => 15.23,
                'enrichment_duration_ms' => 234.56,
                'total_processing_ms' => 249.79,
            ],
            'total_conversation_time_ms' => 249.79,
        ];

        Log::shouldReceive('channel')
            ->with('chat-telemetry')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('chat.fragment.correlation', \Mockery::on(function ($data) use ($correlationData) {
                return $data['data']['user_fragment_id'] === $correlationData['user_fragment_id'] &&
                       $data['data']['assistant_fragment_id'] === $correlationData['assistant_fragment_id'] &&
                       $data['data']['conversation_id'] === $correlationData['conversation_id'] &&
                       $data['data']['processing_chain'] === $correlationData['processing_chain'] &&
                       isset($data['meta']['correlation_id']);
            }))
            ->once();

        ChatTelemetry::logFragmentCorrelation($correlationData);
    }
}
