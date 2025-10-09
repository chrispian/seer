<?php

namespace Database\Seeders;

use App\Models\ToolDefinition;
use Illuminate\Database\Seeder;

class ToolDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            [
                'slug' => 'shell',
                'name' => 'Shell Command',
                'version' => '1.0',
                'source' => 'builtin',
                'summary' => 'Execute shell commands on the server for system queries and file operations.',
                'selection_hint' => 'Use when you need to run system commands like ls, pwd, date, cat, grep, find, or check server state.',
                'syntax' => 'shell(cmd, timeout=15)',
                'args_schema' => [
                    'cmd' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Shell command to execute (must be in allowlist)',
                    ],
                    'workdir' => [
                        'type' => 'string',
                        'required' => false,
                        'description' => 'Working directory (defaults to project root)',
                    ],
                    'timeout' => [
                        'type' => 'integer',
                        'required' => false,
                        'description' => 'Timeout in seconds (max 300)',
                    ],
                ],
                'examples' => [
                    [
                        'goal' => 'Get current server date and time',
                        'call' => ['tool' => 'shell', 'args' => ['cmd' => 'date']],
                        'expect' => 'Current date/time string from server',
                    ],
                    [
                        'goal' => 'List files in current directory',
                        'call' => ['tool' => 'shell', 'args' => ['cmd' => 'ls -la']],
                        'expect' => 'Directory listing with permissions and timestamps',
                    ],
                    [
                        'goal' => 'Find PHP files in app directory',
                        'call' => ['tool' => 'shell', 'args' => ['cmd' => 'find app -name "*.php"']],
                        'expect' => 'List of PHP file paths',
                    ],
                ],
                'weights' => [
                    'priority' => 0.33,
                    'cost_hint' => 0.2,
                    'success_hint' => 0.33,
                ],
                'permissions' => [
                    'fs_read' => true,
                    'fs_write' => false,
                    'net' => false,
                ],
                'constraints' => [
                    'allowed_commands' => ['ls', 'pwd', 'echo', 'cat', 'grep', 'find', 'date', 'whoami'],
                    'timeout_max' => 300,
                ],
                'enabled' => true,
            ],
            [
                'slug' => 'fs',
                'name' => 'File System',
                'version' => '1.0',
                'source' => 'builtin',
                'summary' => 'Read and write files in the application workspace.',
                'selection_hint' => 'Use to read file contents or write/update files. Prefer read-only operations unless explicitly authorized.',
                'syntax' => 'fs(operation, path, content=null)',
                'args_schema' => [
                    'operation' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Operation: read, write, append, delete',
                    ],
                    'path' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'File path relative to workspace root',
                    ],
                    'content' => [
                        'type' => 'string',
                        'required' => false,
                        'description' => 'Content for write/append operations',
                    ],
                ],
                'examples' => [
                    [
                        'goal' => 'Read configuration file',
                        'call' => ['tool' => 'fs', 'args' => ['operation' => 'read', 'path' => 'config/app.php']],
                        'expect' => 'File contents as string',
                    ],
                    [
                        'goal' => 'Write new file',
                        'call' => ['tool' => 'fs', 'args' => ['operation' => 'write', 'path' => 'tmp/output.txt', 'content' => 'Hello World']],
                        'expect' => 'Success confirmation',
                    ],
                ],
                'weights' => [
                    'priority' => 0.33,
                    'cost_hint' => 0.25,
                    'success_hint' => 0.33,
                ],
                'permissions' => [
                    'fs_read' => true,
                    'fs_write' => true,
                    'net' => false,
                ],
                'constraints' => [
                    'allowed_paths' => ['app', 'resources', 'config', 'storage/app/tools'],
                    'max_file_size' => 1048576,
                ],
                'enabled' => false,
            ],
            [
                'slug' => 'mcp',
                'name' => 'MCP Tool',
                'version' => '1.0',
                'source' => 'builtin',
                'summary' => 'Execute tools via Model Context Protocol servers.',
                'selection_hint' => 'Use for MCP server tools. Specific MCP tools are synced separately.',
                'syntax' => 'mcp(server, method, params={})',
                'args_schema' => [
                    'server' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'MCP server identifier',
                    ],
                    'method' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Method to call on the server',
                    ],
                    'params' => [
                        'type' => 'object',
                        'required' => false,
                        'description' => 'Parameters for the method',
                    ],
                ],
                'examples' => [
                    [
                        'goal' => 'Query JSON with jq',
                        'call' => ['tool' => 'mcp', 'args' => ['server' => 'laravel-tool-crate', 'method' => 'json_query', 'params' => ['program' => '.name', 'json' => '{"name":"test"}']]],
                        'expect' => 'Extracted JSON value',
                    ],
                ],
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
                'enabled' => true,
            ],
        ];

        foreach ($tools as $tool) {
            ToolDefinition::updateOrCreate(
                ['slug' => $tool['slug']],
                $tool
            );
        }

        $this->command->info('Seeded '.count($tools).' tool definitions');
    }
}
