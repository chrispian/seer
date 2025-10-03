<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;

class TodoistTool implements Tool
{
    public function slug(): string { return 'todoist'; }
    public function capabilities(): array { return ['create','list','complete']; }

    public function call(array $args, array $context = []): array
    {
        // Stub: call Todoist API.
        return ['_demo' => 'todoist action'];
    }
}
