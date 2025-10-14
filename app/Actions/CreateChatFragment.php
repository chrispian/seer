<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Models\Project;
use App\Models\Vault;
use Illuminate\Support\Facades\Log;

class CreateChatFragment
{
    public function __invoke(string $content, string $source = 'chat-user', array $metadata = []): Fragment
    {
        Log::debug('CreateChatFragment::invoke()', [
            'content_length' => strlen($content),
            'source' => $source,
        ]);

        // Normalize input content and generate hash (same as RouteFragment)
        $normalization = app(NormalizeInput::class)($content);

        // Get default vault and project with error handling
        $defaultVault = Vault::getDefault();
        if (! $defaultVault) {
            Log::error('No default vault found - creating one');
            $defaultVault = Vault::create([
                'name' => 'Default',
                'description' => 'Auto-created default vault',
                'is_default' => true,
                'sort_order' => 1,
            ]);
        }

        $defaultProject = Project::getDefaultForVault($defaultVault->id);
        if (! $defaultProject) {
            Log::error('No default project found for vault - creating one', ['vault_id' => $defaultVault->id]);
            $defaultProject = Project::create([
                'name' => 'General',
                'description' => 'Auto-created default project',
                'vault_id' => $defaultVault->id,
                'is_default' => true,
                'sort_order' => 1,
            ]);
        }

        // Always create a new fragment for chat (bypass deduplication)
        $fragment = Fragment::create([
            'message' => $normalization['normalized'],
            'type' => 'log',
            'vault' => $defaultVault->name,
            'project_id' => $defaultProject->id,
            'input_hash' => $normalization['hash'],
            'hash_bucket' => $normalization['bucket'],
            'source' => $source,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);

        Log::debug('Chat fragment created (bypassed deduplication)', [
            'fragment_id' => $fragment->id,
            'vault_id' => $defaultVault->id,
            'project_id' => $defaultProject->id,
            'hash' => $normalization['hash'],
            'bucket' => $normalization['bucket'],
            'source' => $source,
        ]);

        return $fragment;
    }
}
