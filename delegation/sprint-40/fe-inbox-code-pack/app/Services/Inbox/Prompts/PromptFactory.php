<?php

namespace App\Services\Inbox\Prompts;

class PromptFactory
{
    public static function make(string $kind, array $frag, array $updates): string
    {
        $type = $updates['type'] ?? $frag['type'] ?? 'document';
        $content = $updates['content'] ?? $frag['content'] ?? '';
        $title = $updates['title'] ?? $frag['title'] ?? '';

        return match($kind) {
            'title' => self::titlePrompt($type, $content),
            'summary' => self::summaryPrompt($type, $content),
            'suggest_edit' => self::rewritePrompt($type, $title, $content),
            default => '',
        };
    }

    protected static function titlePrompt(string $type, string $content): string
    {
        return <<<PROMPT
You are titling a {$type} fragment for a personal knowledge system.
Write a concise, descriptive title (max 80 chars). Avoid emojis.
CONTENT:
{$content}
PROMPT;
    }

    protected static function summaryPrompt(string $type, string $content): string
    {
        return <<<PROMPT
Summarize this {$type} fragment in 1-2 sentences, neutral tone, Markdown ok.
CONTENT:
{$content}
PROMPT;
    }

    protected static function rewritePrompt(string $type, string $title, string $content): string
    {
        return <<<PROMPT
Rewrite the fragment's content for clarity and brevity with a style that fits a {$type}.
Maintain original meaning; keep actionable items explicit. Output plain text.
TITLE (if any):
{$title}

CONTENT:
{$content}
PROMPT;
    }
}
