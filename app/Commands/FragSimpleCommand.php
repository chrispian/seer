<?php

namespace App\Commands;

use App\Models\Fragment;
use App\Models\Type;
use App\Models\Vault;

class FragSimpleCommand extends BaseCommand
{
    public function handle(): array
    {
        $message = trim($this->getData()['identifier'] ?? $this->getData()['body'] ?? '');

        if (empty($message)) {
            return [
                'type' => 'error',
                'component' => null,
                'data' => null,
                'message' => 'No valid fragment detected. Please try `/frag Your message here...`',
            ];
        }

        // Create fragment
        $fragment = Fragment::create([
            'vault_id' => Vault::where('name', 'default')->first()?->id ?? 1,
            'type_id' => Type::where('value', 'log')->first()?->id ?? 1,
            'message' => $message,
            'source' => 'chat',
            'metadata' => [
                'aside' => true,
            ],
        ]);

        return [
            'type' => 'success',
            'component' => null,
            'data' => [
                'fragments' => [],
            ],
            'message' => 'Fragment saved',
        ];
    }

    public static function getName(): string
    {
        return 'Create Fragment Simple';
    }

    public static function getDescription(): string
    {
        return 'Create simple fragments';
    }

    public static function getUsage(): string
    {
        return '/frag-simple [message]';
    }

    public static function getCategory(): string
    {
        return 'Content Management';
    }
}
