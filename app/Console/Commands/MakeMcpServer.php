<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeMcpServer extends Command
{
    protected $signature = 'make:mcp-server 
                            {name : The name of the MCP server (e.g., fragments-tools)}
                            {--domain= : Description of the server domain}
                            {--methods=* : Initial methods to scaffold (e.g., resource/list,resource/get)}
                            {--no-register : Skip automatic registration in .mcp.json}';

    protected $description = 'Create a new MCP server with scaffolding';

    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->option('domain') ?: 'MCP server for '.$name;
        $methodsInput = $this->option('methods');

        // Parse methods from multiple options or comma-separated string
        $methods = [];
        if ($methodsInput) {
            foreach ($methodsInput as $methodString) {
                $parsed = array_map('trim', explode(',', $methodString));
                $methods = array_merge($methods, $parsed);
            }
        }

        // Default methods if none provided
        if (empty($methods)) {
            $methods = ['status', 'list'];
        }

        // Validate server name
        if (! $this->isValidServerName($name)) {
            $this->error('Invalid server name. Use hyphenated lowercase names (e.g., fragments-tools)');

            return 1;
        }

        $className = $this->getClassName($name);
        $commandName = $this->getCommandName($name);

        $this->info("Creating MCP server: {$name}");
        $this->info("Class: {$className}");
        $this->info("Command: {$commandName}");
        $this->newLine();

        // Create the command file
        $this->createCommandFile($name, $className, $commandName, $domain, $methods);

        // Register in .mcp.json unless skipped
        if (! $this->option('no-register')) {
            $this->registerInMcpJson($name, $commandName);
        }

        // Create documentation stub
        $this->createDocumentationStub($name, $domain, $methods);

        $this->newLine();
        $this->info("✅ MCP server '{$name}' created successfully!");
        $this->info("📁 Command file: app/Console/Commands/{$className}.php");
        $this->info("📝 Documentation: docs/mcp-servers/{$name}.md");
        $this->newLine();
        $this->info('Usage examples:');
        $this->line("  /{$name} ".$methods[0]);
        if (count($methods) > 1) {
            $this->line("  /{$name} ".$methods[1]);
        }
        $this->newLine();
        $this->info('Test locally:');
        $this->line("  php artisan {$commandName}");
        $this->line("  echo '{\"method\":\"{$methods[0]}\",\"params\":{}}' | php artisan {$commandName}");

        return 0;
    }

    private function isValidServerName(string $name): bool
    {
        // Must be hyphenated lowercase, no underscores or spaces
        return preg_match('/^[a-z]+(-[a-z]+)*$/', $name);
    }

    private function getClassName(string $name): string
    {
        // Convert hyphenated-name to HyphenatedNameMcp
        return Str::studly(str_replace('-', '_', $name)).'Mcp';
    }

    private function getCommandName(string $name): string
    {
        // Convert hyphenated-name to hyphenated-name:mcp
        return $name.':mcp';
    }

    private function createCommandFile(string $name, string $className, string $commandName, string $domain, array $methods): void
    {
        $stub = $this->getCommandStub();

        $replacements = [
            '{{className}}' => $className,
            '{{commandName}}' => $commandName,
            '{{domain}}' => $domain,
            '{{serverName}}' => $name,
            '{{methods}}' => $this->generateMethodCases($methods),
            '{{methodImplementations}}' => $this->generateMethodImplementations($methods),
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $filePath = app_path("Console/Commands/{$className}.php");
        File::put($filePath, $content);

        $this->info("✅ Created command file: {$filePath}");
    }

    private function generateMethodCases(array $methods): string
    {
        $cases = [];
        foreach ($methods as $method) {
            $methodName = $this->getMethodName($method);
            $cases[] = "            '{$method}' => \$this->{$methodName}(\$params),";
        }

        return implode("\n", $cases);
    }

    private function generateMethodImplementations(array $methods): string
    {
        $implementations = [];

        foreach ($methods as $method) {
            $methodName = $this->getMethodName($method);
            $implementations[] = $this->getMethodImplementationStub($method, $methodName);
        }

        return implode("\n\n", $implementations);
    }

    private function getMethodName(string $method): string
    {
        // Convert 'resource/list' to 'listResource', 'status' to 'getStatus'
        if (str_contains($method, '/')) {
            [$action, $resource] = explode('/', $method, 2);

            return $action.Str::studly($resource);
        }

        return 'get'.Str::studly($method);
    }

    private function getMethodImplementationStub(string $method, string $methodName): string
    {
        if (str_contains($method, '/')) {
            [$action, $resource] = explode('/', $method, 2);

            return match ($action) {
                'list' => $this->getListMethodStub($methodName, $resource),
                'get' => $this->getGetMethodStub($methodName, $resource),
                'create' => $this->getCreateMethodStub($methodName, $resource),
                'update' => $this->getUpdateMethodStub($methodName, $resource),
                'delete' => $this->getDeleteMethodStub($methodName, $resource),
                default => $this->getGenericMethodStub($methodName, $method),
            };
        }

        return $this->getStatusMethodStub($methodName);
    }

    private function getListMethodStub(string $methodName, string $resource): string
    {
        return "    private function {$methodName}(array \$params): array
    {
        try {
            // TODO: Implement {$resource} listing logic
            \$items = collect([
                ['id' => 1, 'name' => 'Example {$resource}', 'status' => 'active'],
                ['id' => 2, 'name' => 'Another {$resource}', 'status' => 'inactive'],
            ]);
            
            return [
                'result' => [
                    'operation' => '{$methodName}',
                    '{$resource}s' => \$items->toArray(),
                    'count' => \$items->count(),
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (\\Exception \$e) {
            return ['error' => 'Failed to list {$resource}s: ' . \$e->getMessage()];
        }
    }";
    }

    private function getGetMethodStub(string $methodName, string $resource): string
    {
        return "    private function {$methodName}(array \$params): array
    {
        \$id = \$params['id'] ?? null;
        
        if (!\$id) {
            return ['error' => 'ID parameter required'];
        }
        
        try {
            // TODO: Implement {$resource} retrieval logic
            \$item = [
                'id' => \$id,
                'name' => \"Example {$resource} {\$id}\",
                'status' => 'active',
                'created_at' => now()->subDays(rand(1, 30))->toISOString()
            ];
            
            return [
                'result' => [
                    'operation' => '{$methodName}',
                    '{$resource}' => \$item,
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (\\Exception \$e) {
            return ['error' => \"Failed to get {$resource}: \" . \$e->getMessage()];
        }
    }";
    }

    private function getCreateMethodStub(string $methodName, string $resource): string
    {
        return "    private function {$methodName}(array \$params): array
    {
        \$name = \$params['name'] ?? null;
        
        if (!\$name) {
            return ['error' => 'Name parameter required'];
        }
        
        try {
            // TODO: Implement {$resource} creation logic
            \$item = [
                'id' => rand(1000, 9999),
                'name' => \$name,
                'status' => 'active',
                'created_at' => now()->toISOString()
            ];
            
            return [
                'result' => [
                    'operation' => '{$methodName}',
                    '{$resource}' => \$item,
                    'message' => \"{$resource} created successfully\",
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (\\Exception \$e) {
            return ['error' => \"Failed to create {$resource}: \" . \$e->getMessage()];
        }
    }";
    }

    private function getUpdateMethodStub(string $methodName, string $resource): string
    {
        return "    private function {$methodName}(array \$params): array
    {
        \$id = \$params['id'] ?? null;
        
        if (!\$id) {
            return ['error' => 'ID parameter required'];
        }
        
        try {
            // TODO: Implement {$resource} update logic
            \$item = [
                'id' => \$id,
                'name' => \$params['name'] ?? \"Updated {$resource} {\$id}\",
                'status' => \$params['status'] ?? 'active',
                'updated_at' => now()->toISOString()
            ];
            
            return [
                'result' => [
                    'operation' => '{$methodName}',
                    '{$resource}' => \$item,
                    'message' => \"{$resource} updated successfully\",
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (\\Exception \$e) {
            return ['error' => \"Failed to update {$resource}: \" . \$e->getMessage()];
        }
    }";
    }

    private function getDeleteMethodStub(string $methodName, string $resource): string
    {
        return "    private function {$methodName}(array \$params): array
    {
        \$id = \$params['id'] ?? null;
        
        if (!\$id) {
            return ['error' => 'ID parameter required'];
        }
        
        try {
            // TODO: Implement {$resource} deletion logic
            
            return [
                'result' => [
                    'operation' => '{$methodName}',
                    'id' => \$id,
                    'message' => \"{$resource} deleted successfully\",
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (\\Exception \$e) {
            return ['error' => \"Failed to delete {$resource}: \" . \$e->getMessage()];
        }
    }";
    }

    private function getStatusMethodStub(string $methodName): string
    {
        return "    private function {$methodName}(array \$params): array
    {
        try {
            // TODO: Implement status checking logic
            return [
                'result' => [
                    'operation' => '{$methodName}',
                    'status' => 'operational',
                    'uptime' => '24h 15m',
                    'version' => '1.0.0',
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (\\Exception \$e) {
            return ['error' => 'Failed to get status: ' . \$e->getMessage()];
        }
    }";
    }

    private function getGenericMethodStub(string $methodName, string $method): string
    {
        return "    private function {$methodName}(array \$params): array
    {
        try {
            // TODO: Implement {$method} logic
            return [
                'result' => [
                    'operation' => '{$methodName}',
                    'message' => '{$method} executed successfully',
                    'params' => \$params,
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (\\Exception \$e) {
            return ['error' => 'Failed to execute {$method}: ' . \$e->getMessage()];
        }
    }";
    }

    private function registerInMcpJson(string $name, string $commandName): void
    {
        $mcpJsonPath = base_path('.mcp.json');

        if (! File::exists($mcpJsonPath)) {
            // Create new .mcp.json
            $mcpConfig = ['mcpServers' => []];
        } else {
            $mcpConfig = json_decode(File::get($mcpJsonPath), true);
        }

        $mcpConfig['mcpServers'][$name] = [
            'command' => 'php',
            'args' => ['./artisan', $commandName],
        ];

        File::put($mcpJsonPath, json_encode($mcpConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('✅ Registered server in .mcp.json');
    }

    private function createDocumentationStub(string $name, string $domain, array $methods): void
    {
        $docsDir = base_path('docs/mcp-servers');
        File::ensureDirectoryExists($docsDir);

        $docContent = "# {$name} MCP Server\n\n";
        $docContent .= "## Overview\n\n{$domain}\n\n";
        $docContent .= "## Available Methods\n\n";

        foreach ($methods as $method) {
            $docContent .= "### {$method}\n\n";
            $docContent .= "**Purpose**: TODO - Describe what this method does\n\n";
            $docContent .= "**Parameters**:\n";
            $docContent .= "- `param1` (required): Description\n";
            $docContent .= "- `param2` (optional): Description\n\n";
            $docContent .= "**Example**:\n";
            $docContent .= "```bash\n";
            $docContent .= "/{$name} {$method} param1=value\n";
            $docContent .= "```\n\n";
            $docContent .= "**Response**:\n";
            $docContent .= "```json\n";
            $docContent .= "{\n";
            $docContent .= "  \"result\": {\n";
            $docContent .= '    "operation": "'.$this->getMethodName($method)."\",\n";
            $docContent .= "    \"data\": \"...\",\n";
            $docContent .= "    \"timestamp\": \"2024-01-01T00:00:00Z\"\n";
            $docContent .= "  }\n";
            $docContent .= "}\n";
            $docContent .= "```\n\n";
        }

        $docContent .= "## Implementation Notes\n\n";
        $docContent .= "TODO - Add implementation details, dependencies, and limitations\n\n";
        $docContent .= "## Testing\n\n";
        $docContent .= "```bash\n";
        $docContent .= "# Test server directly\n";
        $docContent .= "php artisan {$name}:mcp\n\n";
        $docContent .= "# Test specific method\n";
        $docContent .= "echo '{\"method\":\"{$methods[0]}\",\"params\":{}}' | php artisan {$name}:mcp\n";
        $docContent .= "```\n";

        $docPath = "{$docsDir}/{$name}.md";
        File::put($docPath, $docContent);

        $this->info("✅ Created documentation: {$docPath}");
    }

    private function getCommandStub(): string
    {
        return '<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class {{className}} extends Command
{
    protected $signature = \'{{commandName}}\';
    protected $description = \'{{domain}}\';

    /**
     * Available Methods:
     * 
     * status - Get server status and health
     *   Parameters: none
     *   Returns: server status information
     * 
     * TODO: Add method documentation here
     */
    public function handle()
    {
        $this->info(\'Starting {{serverName}} MCP Server...\');
        
        while (true) {
            $input = trim(fgets(STDIN));
            
            if (empty($input)) {
                continue;
            }
            
            try {
                $request = json_decode($input, true);
                $response = $this->handleRequest($request);
                echo json_encode($response) . "\n";
            } catch (\Exception $e) {
                $error = [
                    \'error\' => [
                        \'code\' => -1,
                        \'message\' => $e->getMessage()
                    ]
                ];
                echo json_encode($error) . "\n";
            }
        }
    }
    
    private function handleRequest(array $request): array
    {
        $method = $request[\'method\'] ?? \'\';
        $params = $request[\'params\'] ?? [];
        
        return match ($method) {
{{methods}}
            default => [\'error\' => \'Unknown method: \' . $method]
        };
    }
    
{{methodImplementations}}
}';
    }
}
