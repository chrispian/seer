<?php

namespace App\Console\Commands\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCommandPackCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'frag:command:make {slug : The command pack slug} {--force : Overwrite existing command pack}';

    /**
     * The console command description.
     */
    protected $description = 'Scaffold a new Slash Command Pack';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');
        $force = $this->option('force');

        // Validate slug format
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
            $this->error('Command pack slug must start with a letter and contain only lowercase letters, numbers, underscores, and hyphens.');
            return self::FAILURE;
        }

        $commandPackPath = base_path("fragments/commands/{$slug}");

        // Check if command pack already exists
        if (File::isDirectory($commandPackPath) && !$force) {
            $this->error("Command pack '{$slug}' already exists. Use --force to overwrite.");
            return self::FAILURE;
        }

        $this->info("Creating command pack '{$slug}'...");

        // Create directory structure
        File::ensureDirectoryExists($commandPackPath);
        File::ensureDirectoryExists("{$commandPackPath}/prompts");
        File::ensureDirectoryExists("{$commandPackPath}/samples");

        // Generate command pack files
        $this->createManifest($commandPackPath, $slug);
        $this->createPrompt($commandPackPath, $slug);
        $this->createSample($commandPackPath, $slug);
        $this->createReadme($commandPackPath, $slug);

        $this->info("✅ Command pack '{$slug}' created successfully!");
        $this->line("📁 Location: {$commandPackPath}");
        $this->line("📝 Edit the manifest, prompts, and samples to customize your command pack.");
        $this->line("🔄 Run 'php artisan frag:command:cache' to register the command pack.");
        $this->line("🧪 Run 'php artisan frag:command:test {$slug} --dry' to test execution.");

        return self::SUCCESS;
    }

    /**
     * Create the command.yaml manifest file
     */
    protected function createManifest(string $path, string $slug): void
    {
        $name = Str::title(str_replace(['_', '-'], ' ', $slug));
        
        $manifest = <<<YAML
name: "{$name}"
slug: {$slug}
version: 1.0.0
triggers:
  slash: "/{$slug}"
  aliases: []
  input_mode: "inline"
reserved: false

requires:
  secrets: []
  capabilities: ["fragment.create"]

steps:
  - id: coerce-input
    type: transform
    template: |
      {{ ctx.body | default: ctx.selection | trim }}

  - id: create-fragment
    type: fragment.create
    with:
      type: "log"
      content: "{{ steps.coerce-input.output }}"
      tags: ["{$slug}"]
      metadata:
        command: "{$slug}"
        created_via: "slash_command"

  - id: notify
    type: notify
    with:
      message: "✅ {$name} created successfully"
      level: "success"
YAML;

        File::put("{$path}/command.yaml", $manifest);
    }

    /**
     * Create a sample prompt file
     */
    protected function createPrompt(string $path, string $slug): void
    {
        $prompt = <<<MARKDOWN
# {$slug} Command Prompt

You are helping the user create a {$slug} fragment.

## Context
- User input: {{ ctx.body }}
- Selection: {{ ctx.selection }}

## Task
Process the user's input and extract relevant information for the {$slug} fragment.

Return a JSON object with the processed information.
MARKDOWN;

        File::put("{$path}/prompts/process.md", $prompt);
    }

    /**
     * Create a sample input file
     */
    protected function createSample(string $path, string $slug): void
    {
        $sample = [
            'ctx' => [
                'body' => "Sample input for {$slug} command",
                'selection' => '',
                'user' => ['id' => 1, 'name' => 'Test User'],
                'workspace' => ['id' => 1],
                'session' => ['id' => 'test-session'],
            ]
        ];

        File::put("{$path}/samples/basic.json", json_encode($sample, JSON_PRETTY_PRINT));
    }

    /**
     * Create the README.md documentation file
     */
    protected function createReadme(string $path, string $slug): void
    {
        $name = Str::title(str_replace(['_', '-'], ' ', $slug));
        
        $readme = <<<MARKDOWN
# {$name} Command Pack

This command pack provides the `/{$slug}` slash command functionality.

## Usage

Type `/{$slug}` followed by your input to create a {$slug} fragment.

Example:
```
/{$slug} This is my input
```

## Configuration

The command is configured in `command.yaml`:

- **Triggers**: `/{$slug}`
- **Input Mode**: Inline
- **Capabilities**: fragment.create

## Steps

1. **coerce-input**: Processes user input from body or selection
2. **create-fragment**: Creates a new fragment with the processed content
3. **notify**: Shows success notification

## Testing

Test the command with:
```bash
php artisan frag:command:test {$slug} samples/basic.json --dry
```

## Customization

- Edit `command.yaml` to modify the command behavior
- Add prompts in `prompts/` directory for AI-powered processing
- Add sample inputs in `samples/` directory for testing
MARKDOWN;

        File::put("{$path}/README.md", $readme);
    }
}
