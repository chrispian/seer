<?php

namespace App\Console\Commands;

use App\Models\ToolDefinition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;

class SyncMcpTools extends Command
{
    protected $signature = 'tools:sync-mcp {--server= : Specific MCP server to sync} {--force : Overwrite manually edited definitions}';

    protected $description = 'Sync MCP server tools to tool_definitions table';

    public function handle()
    {
        $mcpConfigPath = base_path('.mcp.json');
        if (!file_exists($mcpConfigPath)) {
            $this->error('.mcp.json not found');
            return 1;
        }

        $mcpJson = json_decode(file_get_contents($mcpConfigPath), true);
        $mcpServers = $mcpJson['mcpServers'] ?? [];
        
        $specificServer = $this->option('server');
        $force = $this->option('force');

        if ($specificServer) {
            if (!isset($mcpServers[$specificServer])) {
                $this->error("MCP server '{$specificServer}' not found in .mcp.json");
                return 1;
            }
            $mcpServers = [$specificServer => $mcpServers[$specificServer]];
        }

        if (empty($mcpServers)) {
            $this->warn('No MCP servers configured in .mcp.json');
            return 0;
        }

        $totalSynced = 0;
        $totalSkipped = 0;

        foreach ($mcpServers as $serverName => $serverConfig) {
            $this->info("Syncing tools from MCP server: {$serverName}");

            try {
                $tools = $this->getMcpToolsList($serverName, $serverConfig);
                
                foreach ($tools as $tool) {
                    $slug = "mcp.{$serverName}.{$tool['name']}";
                    
                    $existing = ToolDefinition::where('slug', $slug)->first();
                    if ($existing && $existing->overridden && !$force) {
                        $this->line("  - Skipping {$slug} (manually overridden)");
                        $totalSkipped++;
                        continue;
                    }

                    $definition = [
                        'slug' => $slug,
                        'name' => $tool['title'] ?? $tool['name'],
                        'version' => '1.0',
                        'source' => 'mcp',
                        'mcp_server' => $serverName,
                        'summary' => $tool['description'] ?? 'MCP tool',
                        'selection_hint' => $this->generateSelectionHint($tool),
                        'syntax' => $this->generateSyntax($tool),
                        'args_schema' => $tool['inputSchema']['properties'] ?? [],
                        'examples' => $this->generateExamples($tool, $serverName),
                        'weights' => [
                            'priority' => 0.33,
                            'cost_hint' => 0.3,
                            'success_hint' => 0.33,
                        ],
                        'permissions' => [
                            'fs_read' => false,
                            'fs_write' => false,
                            'net' => true,
                        ],
                        'metadata' => [
                            'annotations' => $tool['annotations'] ?? [],
                            'original_schema' => $tool['inputSchema'] ?? [],
                        ],
                        'enabled' => true,
                        'synced_at' => now(),
                    ];

                    if (!$existing || !$existing->overridden || $force) {
                        ToolDefinition::updateOrCreate(
                            ['slug' => $slug],
                            $definition
                        );
                        $this->line("  ✓ Synced {$slug}");
                        $totalSynced++;
                    }
                }

            } catch (\Exception $e) {
                $this->error("  Failed to sync {$serverName}: {$e->getMessage()}");
            }
        }

        $this->info("\nSync complete: {$totalSynced} synced, {$totalSkipped} skipped");
        return 0;
    }

    protected function getMcpToolsList(string $serverName, array $serverConfig): array
    {
        $request = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ];

        $command = $serverConfig['command'] ?? 'php';
        $args = $serverConfig['args'] ?? [];

        $process = new Process(array_merge([$command], $args), base_path());

        $process->setInput(json_encode($request) . "\n");
        $process->setTimeout(10);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Failed to call MCP server: " . $process->getErrorOutput());
        }

        $output = trim($process->getOutput());
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $response = json_decode($line, true);
            if (isset($response['result']['tools'])) {
                return $response['result']['tools'];
            }
        }

        return [];
    }

    protected function generateSelectionHint(array $tool): string
    {
        $description = $tool['description'] ?? '';
        if (empty($description)) {
            return "Use the {$tool['name']} tool";
        }

        return "Use when: " . strtolower($description);
    }

    protected function generateSyntax(array $tool): string
    {
        $params = [];
        $schema = $tool['inputSchema']['properties'] ?? [];
        $required = $tool['inputSchema']['required'] ?? [];

        foreach ($schema as $paramName => $paramDef) {
            $isRequired = in_array($paramName, $required);
            $default = $paramDef['default'] ?? null;

            if ($isRequired) {
                $params[] = $paramName;
            } else {
                $params[] = "{$paramName}=" . ($default !== null ? json_encode($default) : '...');
            }
        }

        return $tool['name'] . '(' . implode(', ', $params) . ')';
    }

    protected function generateExamples(array $tool, string $serverName): array
    {
        return [
            [
                'goal' => "Use {$tool['name']} tool",
                'call' => [
                    'tool' => 'mcp',
                    'args' => [
                        'server' => $serverName,
                        'method' => $tool['name'],
                        'params' => [],
                    ],
                ],
                'expect' => 'Tool result data',
            ],
        ];
    }
}

