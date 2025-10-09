<?php

namespace App\Tools\Orchestration;

use App\Models\Message;
use App\Tools\Contracts\SummarizesTool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MessageAckTool extends Tool implements SummarizesTool
{
    protected string $name = 'orchestration_message_ack';

    protected string $title = 'Acknowledge/mark message as read';

    protected string $description = 'Mark a message as read by setting read_at timestamp.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'message_id' => $schema->string()->required()->description('Message UUID'),
        ];
    }

    public function handle(Request $request): Response
    {
        $messageId = (string) $request->get('message_id');

        $message = Message::findOrFail($messageId);

        if ($message->isRead()) {
            return Response::json([
                'success' => true,
                'message' => 'Message already read',
                'message_id' => $message->id,
                'read_at' => $message->read_at->toIso8601String(),
            ]);
        }

        $message->markAsRead();

        return Response::json([
            'success' => true,
            'message' => 'Message marked as read',
            'message_id' => $message->id,
            'read_at' => $message->read_at->toIso8601String(),
        ]);
    }

    public static function summaryName(): string
    {
        return 'orchestration_message_ack';
    }

    public static function summaryTitle(): string
    {
        return 'Acknowledge message';
    }

    public static function summaryDescription(): string
    {
        return 'Mark a message as read.';
    }

    public static function schemaSummary(): array
    {
        return [
            'message_id' => 'Message UUID to acknowledge',
        ];
    }
}
