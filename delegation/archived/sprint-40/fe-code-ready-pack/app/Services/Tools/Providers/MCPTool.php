<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;

class MCPTool implements Tool
{
    public function slug(): string
    {
        return 'mcp';
    }

    public function capabilities(): array
    {
        return ['proxy'];
    }

    public function call(array $args, array $context = []): array
    {
        // Stub: bridge to Laravel MCP client. Route {server, method, params}.
        return ['_demo' => 'Proxy to MCP server here'];
    }
}
