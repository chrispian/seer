<?php

namespace App\Commands;

class AcceptCommand extends BaseCommand
{
    public function handle(): array
    {
        $fragmentId = $this->getData()['body'] ?? $this->getData()['fragment_id'] ?? '';

        $message = "Fragment ID: {$fragmentId}\n\n".
                  "Use API endpoint POST /api/inbox/{$fragmentId}/accept to accept this fragment.\n\n".
                  'Optional edits can be provided in request body.';

        return [
            'type' => 'message',
            'component' => null,
            'data' => null,
            'message' => $message,
        ];
    }

    public static function getName(): string
    {
        return 'Accept Inbox Fragment';
    }

    public static function getDescription(): string
    {
        return 'Accept and process incoming fragments from inbox';
    }

    public static function getUsage(): string
    {
        return '/accept [fragment_id]';
    }

    public static function getCategory(): string
    {
        return 'Inbox Management';
    }
}
