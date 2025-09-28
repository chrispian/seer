<?php

namespace App\Actions;

use App\Models\Fragment;
use App\Models\Project;
use App\Models\Vault;
use Illuminate\Support\Facades\Log;

class CreateChatFragment
{
    public function __invoke(string $content): Fragment
    {
        Log::debug('CreateChatFragment::invoke()');

        // Normalize input content and generate hash (same as RouteFragment)
        $normalization = app(NormalizeInput::class)($content);

        // Get default vault and project
        $defaultVault = Vault::getDefault();
        $defaultProject = Project::getDefaultForVault($defaultVault->id);

        // Always create a new fragment for chat (bypass deduplication)
        $fragment = Fragment::create([
            'message' => $normalization['normalized'],
            'type' => 'log',
            'vault' => $defaultVault->name,
            'project_id' => $defaultProject->id,
            'input_hash' => $normalization['hash'],
            'hash_bucket' => $normalization['bucket'],
            'source' => 'chat-user', // Set source immediately
        ]);

        Log::debug('Chat fragment created (bypassed deduplication)', [
            'fragment_id' => $fragment->id,
            'hash' => $normalization['hash'],
            'bucket' => $normalization['bucket'],
        ]);

        return $fragment;
    }
}
