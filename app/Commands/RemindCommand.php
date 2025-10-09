<?php

namespace App\Commands;

use App\Models\Fragment;
use App\Models\Type;

class RemindCommand extends BaseCommand
{
    public function handle(): array
    {
        $content = trim($this->getData()['body'] ?? $this->getData()['selection'] ?? '');

        if (empty($content)) {
            return [
                'type' => 'error',
                'component' => null,
                'data' => null,
                'message' => 'Please provide reminder text.'
            ];
        }

        // Extract title (first 40 chars)
        $title = 'Reminder: ' . mb_substr($content, 0, 40);
        if (mb_strlen($content) > 40) {
            $title .= '...';
        }

        // Create fragment
        $fragment = Fragment::create([
            'type_id' => Type::where('value', 'reminder')->first()?->id ?? 1,
            'message' => $content,
            'title' => $title,
            'metadata' => [
                'command' => 'remind',
                'created_via' => 'scheduled_command',
                'reminder_source' => 'scheduler',
                'type' => 'reminder',
                'created_at' => now()->toISOString(),
                'reminded_at' => now()->toISOString(),
                'status' => 'active'
            ],
            'tags' => ['reminder', 'scheduled']
        ]);

        return [
            'type' => 'success',
            'component' => null,
            'data' => null,
            'message' => "⏰ Reminder: {$content}"
        ];
    }

    public static function getName(): string
    {
        return 'Create Reminder';
    }

    public static function getDescription(): string
    {
        return 'Set and manage reminders';
    }

    public static function getUsage(): string
    {
        return '/remind [reminder_text]';
    }

    public static function getCategory(): string
    {
        return 'Scheduling';
    }
}