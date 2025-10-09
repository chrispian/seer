<?php

namespace App\Commands;

use App\Models\Fragment;
use App\Models\Type;

class NoteCommand extends BaseCommand
{
    public function handle(): array
    {
        $content = trim($this->getData()['body'] ?? $this->getData()['selection'] ?? '');

        if (empty($content)) {
            return [
                'type' => 'error',
                'component' => null,
                'data' => null,
                'message' => 'Please provide content for the note.',
            ];
        }

        // Extract title (first 50 chars)
        $title = mb_substr($content, 0, 50);
        if (mb_strlen($content) > 50) {
            $title .= '...';
        }

        // Create fragment
        $fragment = Fragment::create([
            'type_id' => Type::where('value', 'document')->first()?->id ?? 1,
            'message' => $content,
            'title' => $title,
            'metadata' => [
                'command' => 'note',
                'created_via' => 'slash_command',
                'type' => 'note',
                'created_at' => now()->toISOString(),
            ],
            'tags' => ['note', 'document'],
        ]);

        return [
            'type' => 'success',
            'component' => null,
            'data' => null,
            'message' => '✅ Note created successfully',
        ];
    }

    public static function getName(): string
    {
        return 'Create Note';
    }

    public static function getDescription(): string
    {
        return 'Create and manage notes';
    }

    public static function getUsage(): string
    {
        return '/note [content]';
    }

    public static function getCategory(): string
    {
        return 'Content Management';
    }
}
