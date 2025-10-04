<?php

namespace App\Services\Tools\Providers;

use App\Services\Tools\Contracts\Tool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class MCPTool implements Tool
{
    public function slug(): string 
    { 
        return 'mcp'; 
    }

    public function capabilities(): array 
    { 
        return ['external_api', 'mcp_protocol', 'resource_access']; 
    }

    public function isEnabled(): bool
    {
        return Config::get('fragments.tools.mcp.enabled', false);
    }

    public function getConfigSchema(): array
    {
        return [
            'required' => ['server', 'method'],
            'properties' => [
                'server' => ['type' => 'string', 'description' => 'MCP server identifier'],
                'method' => ['type' => 'string', 'description' => 'MCP method to call'],
                'params' => ['type' => 'object', 'description' => 'Parameters for the MCP call'],
                'timeout' => ['type' => 'integer', 'default' => 30, 'description' => 'Request timeout in seconds'],
            ]
        ];
    }

    public function call(array $args, array $context = []): array
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('MCP tool is disabled');
        }

        $server = $args['server'] ?? null;
        $method = $args['method'] ?? null;
        $params = $args['params'] ?? [];
        $timeout = (int) ($args['timeout'] ?? 30);

        if (!$server || !$method) {
            throw new \InvalidArgumentException('Missing required parameters: server and method');
        }

        // Check if server is allowlisted
        $allowedServers = Config::get('fragments.tools.mcp.allowed_servers', []);
        if (!empty($allowedServers) && !in_array($server, $allowedServers, true)) {
            throw new \RuntimeException("MCP server not allowed: {$server}");
        }

        // Get server configuration
        $servers = Config::get('fragments.tools.mcp.servers', []);
        if (!isset($servers[$server])) {
            throw new \RuntimeException("MCP server not configured: {$server}");
        }

        $serverConfig = $servers[$server];
        $baseUrl = $serverConfig['url'] ?? null;
        
        if (!$baseUrl) {
            throw new \RuntimeException("MCP server URL not configured: {$server}");
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders($serverConfig['headers'] ?? [])
                ->post($baseUrl . '/mcp', [
                    'jsonrpc' => '2.0',
                    'id' => uniqid(),
                    'method' => $method,
                    'params' => $params,
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("MCP request failed with status: {$response->status()}");
            }

            $data = $response->json();
            
            if (isset($data['error'])) {
                throw new \RuntimeException("MCP error: " . ($data['error']['message'] ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'result' => $data['result'] ?? null,
                'server' => $server,
                'method' => $method,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'server' => $server,
                'method' => $method,
            ];
        }
    }
}