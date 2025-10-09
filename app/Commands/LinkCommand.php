<?php

namespace App\Commands;

use App\Models\Fragment;
use App\Models\Type;

class LinkCommand extends BaseCommand
{
    public function handle(): array
    {
        $content = trim($this->getData()['body'] ?? $this->getData()['selection'] ?? '');

        if (empty($content)) {
            return [
                'type' => 'error',
                'component' => null,
                'data' => null,
                'message' => 'Please provide a link to save.'
            ];
        }

        // Create fragment
        $fragment = Fragment::create([
            'type_id' => Type::where('value', 'log')->first()?->id ?? 1,
            'message' => $content,
            'metadata' => [
                'command' => 'link',
                'created_via' => 'slash_command'
            ],
            'tags' => ['link']
        ]);

        return [
            'type' => 'success',
            'component' => null,
            'data' => null,
            'message' => '✅ Link created successfully'
        ];
    }

    public static function getName(): string
    {
        return 'Save Link';
    }

    public static function getDescription(): string
    {
        return 'Save and manage links';
    }

    public static function getUsage(): string
    {
        return '/link [url]';
    }

    public static function getCategory(): string
    {
        return 'Content Management';
    }
}