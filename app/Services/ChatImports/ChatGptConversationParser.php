<?php

namespace App\Services\ChatImports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class ChatGptConversationParser
{
    private const SUPPORTED_USER_CONTENT = [
        'text',
        'multimodal_text',
    ];

    private const SUPPORTED_ASSISTANT_CONTENT = [
        'text',
        'multimodal_text',
        'code',
    ];

    /**
     * Parse a ChatGPT conversation export into a normalized structure.
     */
    public function parse(array $conversation): ?ParsedConversation
    {
        $mapping = $conversation['mapping'] ?? [];
        $currentNodeId = $conversation['current_node'] ?? null;

        if (! $currentNodeId || ! isset($mapping[$currentNodeId])) {
            return null;
        }

        $orderedNodes = $this->collectActiveBranch($mapping, $currentNodeId);

        $messages = [];
        $lastMessageTimestamp = null;

        foreach ($orderedNodes as $node) {
            $message = $node['message'] ?? null;

            if (! $this->isRenderableMessage($message)) {
                continue;
            }

            $role = $message['author']['role'] ?? null;
            $contentType = Arr::get($message, 'content.content_type');
            $normalizedText = $this->normaliseContent($message);

            if ($normalizedText === null) {
                continue;
            }

            $timestamp = $this->normaliseTimestamp($message['create_time'] ?? null)
                ?? $lastMessageTimestamp
                ?? $this->normaliseTimestamp($conversation['create_time'] ?? null)
                ?? CarbonImmutable::now();

            $messages[] = new ParsedMessage(
                id: (string) ($message['id'] ?? $node['id']),
                role: $role === 'assistant' ? 'assistant' : 'user',
                text: $normalizedText,
                createdAt: $timestamp,
                metadata: [
                    'chatgpt_content_type' => $contentType,
                    'raw_metadata' => $message['metadata'] ?? [],
                ],
            );

            $lastMessageTimestamp = $timestamp;
        }

        if ($messages === []) {
            return null;
        }

        $createdAt = $this->normaliseTimestamp($conversation['create_time'] ?? null)
            ?? $messages[0]->createdAt;
        $updatedAt = $this->normaliseTimestamp($conversation['update_time'] ?? null)
            ?? end($messages)->createdAt;

        $conversationId = (string) ($conversation['conversation_id'] ?? $conversation['id'] ?? '');

        if ($conversationId === '') {
            return null;
        }

        return new ParsedConversation(
            conversationId: $conversationId,
            title: (string) ($conversation['title'] ?? 'ChatGPT Conversation'),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            messages: $messages,
            metadata: [
                'default_model_slug' => $conversation['default_model_slug'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @return array<int, array<string, mixed>>
     */
    private function collectActiveBranch(array $mapping, string $currentNodeId): array
    {
        $branch = [];
        $nodeId = $currentNodeId;

        while ($nodeId && isset($mapping[$nodeId])) {
            $branch[] = $mapping[$nodeId];
            $nodeId = $mapping[$nodeId]['parent'] ?? null;
        }

        return array_reverse($branch);
    }

    /**
     * @param  array<string, mixed>|null  $message
     */
    private function isRenderableMessage(?array $message): bool
    {
        if (! $message) {
            return false;
        }

        $role = $message['author']['role'] ?? null;
        if (! in_array($role, ['user', 'assistant'], true)) {
            return false;
        }

        $isHidden = (bool) Arr::get($message, 'metadata.is_visually_hidden_from_conversation', false);
        if ($isHidden) {
            return false;
        }

        $contentType = Arr::get($message, 'content.content_type');
        $supported = $role === 'assistant'
            ? self::SUPPORTED_ASSISTANT_CONTENT
            : self::SUPPORTED_USER_CONTENT;

        return in_array($contentType, $supported, true);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function normaliseContent(array $message): ?string
    {
        $content = $message['content'] ?? [];
        $type = $content['content_type'] ?? null;

        return match ($type) {
            'text' => $this->joinParts($content['parts'] ?? []),
            'multimodal_text' => $this->extractMultimodalText($content['parts'] ?? []),
            'code' => $this->formatCodeBlock($content),
            default => null,
        };
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    private function joinParts(array $parts): string
    {
        return collect($parts)
            ->map(fn ($part) => is_string($part) ? $part : '')
            ->filter()
            ->implode("\n");
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    private function extractMultimodalText(array $parts): ?string
    {
        $segments = collect($parts)
            ->map(function ($part) {
                if (is_string($part)) {
                    return $part;
                }

                if (is_array($part) && isset($part['text'])) {
                    return (string) $part['text'];
                }

                return null;
            })
            ->filter();

        return $segments->isEmpty() ? null : $segments->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function formatCodeBlock(array $content): ?string
    {
        $text = $content['text'] ?? null;

        if (! is_string($text) || $text === '') {
            return null;
        }

        $language = $content['language'] ?? 'text';

        return sprintf("```%s\n%s\n```", $language, $text);
    }

    private function normaliseTimestamp(null|int|float|string $timestamp): ?CarbonImmutable
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        if (is_string($timestamp) && is_numeric($timestamp)) {
            $timestamp = (float) $timestamp;
        }

        if (is_int($timestamp) || is_float($timestamp)) {
            try {
                return CarbonImmutable::createFromTimestamp($timestamp);
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return CarbonImmutable::parse((string) $timestamp);
        } catch (\Throwable) {
            return null;
        }
    }
}

final class ParsedConversation
{
    /**
     * @param  array<int, ParsedMessage>  $messages
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $conversationId,
        public string $title,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public array $messages,
        public array $metadata = [],
    ) {}
}

final class ParsedMessage
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $role,
        public string $text,
        public CarbonImmutable $createdAt,
        public array $metadata = [],
    ) {}
}
