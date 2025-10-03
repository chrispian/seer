<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;

class GmailTool implements Tool
{
    public function slug(): string { return 'gmail'; }
    public function capabilities(): array { return ['list','send','threads']; }

    public function call(array $args, array $context = []): array
    {
        // Stub: call your Gmail integration; honor tokens per workspace/user.
        return ['_demo' => 'gmail action'];
    }
}
