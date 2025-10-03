<?php

namespace App\Console\Commands\TypePacks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeTypePackCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'frag:type:make {slug : The type pack slug} {--force : Overwrite existing type pack}';

    /**
     * The console command description.
     */
    protected $description = 'Scaffold a new Fragment Type Pack';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');
        $force = $this->option('force');

        // Validate slug format
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
            $this->error('Type pack slug must start with a letter and contain only lowercase letters, numbers, underscores, and hyphens.');
            return self::FAILURE;
        }

        $typePackPath = base_path("fragments/types/{$slug}");

        // Check if type pack already exists
        if (File::isDirectory($typePackPath) && !$force) {
            $this->error("Type pack '{$slug}' already exists. Use --force to overwrite.");
            return self::FAILURE;
        }

        $this->info("Creating type pack '{$slug}'...");

        // Create directory structure
        File::ensureDirectoryExists($typePackPath);

        // Generate type pack files
        $this->createManifest($typePackPath, $slug);
        $this->createSchema($typePackPath, $slug);
        $this->createIndexes($typePackPath, $slug);
        $this->createReadme($typePackPath, $slug);

        $this->info("✅ Type pack '{$slug}' created successfully!");
        $this->line("📁 Location: {$typePackPath}");
        $this->line("📝 Edit the manifest, schema, and indexes files to customize your type pack.");
        $this->line("🔄 Run 'php artisan frag:type:cache' to register the type pack.");

        return self::SUCCESS;
    }

    /**
     * Create the type.yaml manifest file
     */
    protected function createManifest(string $path, string $slug): void
    {
        $name = Str::title(str_replace(['_', '-'], ' ', $slug));
        
        $manifest = <<<YAML
name: "{$name}"
description: "Custom type pack for {$slug} fragments"
version: "1.0.0"
author: "Your Organization"

# Type capabilities
capabilities:
  - "state_validation"
  - "hot_fields"

# UI configuration
ui:
  icon: "document-text"
  color: "#6366F1"
  display_name: "{$name}"
  plural_name: "{$name}s"

# Default state when creating new fragments of this type
default_state:
  status: "active"
  created_at: null

# Required fields for this type
required_fields:
  - "status"

# Metadata
metadata:
  tags:
    - "{$slug}"
  category: "custom"
  documentation: "fragments/types/{$slug}/README.md"
YAML;

        File::put("{$path}/type.yaml", $manifest);
    }

    /**
     * Create the state.schema.json file
     */
    protected function createSchema(string $path, string $slug): void
    {
        $name = Str::title(str_replace(['_', '-'], ' ', $slug));
        
        $schema = [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => "{$name} State Schema",
            'description' => "Schema for {$slug} fragment state data",
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive', 'archived'],
                    'description' => "Current status of the {$slug} item"
                ],
                'created_at' => [
                    'type' => ['string', 'null'],
                    'format' => 'date-time',
                    'description' => "When the {$slug} was created (ISO 8601 format)"
                ],
                'notes' => [
                    'type' => ['string', 'null'],
                    'description' => 'Additional notes or context'
                ]
            ],
            'required' => ['status'],
            'additionalProperties' => false
        ];

        File::put("{$path}/state.schema.json", json_encode($schema, JSON_PRETTY_PRINT));
    }

    /**
     * Create the indexes.yaml file
     */
    protected function createIndexes(string $path, string $slug): void
    {
        $indexes = <<<YAML
# Hot fields for performance optimization
hot_fields:
  status:
    type: "string"
    path: "status"
    indexed: true
    description: "{$slug} status for filtering and sorting"
    
  created_at:
    type: "timestamp"
    path: "created_at"
    indexed: true
    description: "Creation date for chronological ordering"

# Partial indexes scoped by type
partial_indexes:
  active_{$slug}s:
    condition: "type = '{$slug}' AND (state->>'status') = 'active'"
    fields: ["created_at"]
    description: "Index for active {$slug}s"
    
  archived_{$slug}s:
    condition: "type = '{$slug}' AND (state->>'status') = 'archived'"
    fields: ["created_at"]
    description: "Index for archived {$slug}s"

# Composite indexes for common queries
composite_indexes:
  status_created:
    fields: ["(state->>'status')", "(state->>'created_at')::timestamp"]
    description: "Multi-field index for {$slug} queries"
YAML;

        File::put("{$path}/indexes.yaml", $indexes);
    }

    /**
     * Create the README.md documentation file
     */
    protected function createReadme(string $path, string $slug): void
    {
        $name = Str::title(str_replace(['_', '-'], ' ', $slug));
        
        $readme = <<<MARKDOWN
# {$name} Type Pack

This type pack provides validation and optimization for {$slug} fragments.

## Schema

The {$slug} type supports the following state fields:

- **status** (required): Current status (active, inactive, archived)
- **created_at**: When the {$slug} was created
- **notes**: Additional context or notes

## Usage

Create a new {$slug} fragment:

```php
Fragment::create([
    'type' => '{$slug}',
    'message' => 'Your {$slug} content...',
    'state' => [
        'status' => 'active',
        'created_at' => now()->toISOString(),
        'notes' => 'Optional notes...'
    ]
]);
```

## Validation

All {$slug} fragments are automatically validated against the JSON schema in `state.schema.json`. Invalid state data will be rejected when validation is enabled.

## Performance

Hot fields are optimized with generated columns and indexes:
- Fast queries by status
- Efficient chronological sorting
- Optimized filtering for active/archived items

## Customization

Edit the following files to customize this type pack:
- `type.yaml` - Manifest and UI configuration
- `state.schema.json` - State validation schema  
- `indexes.yaml` - Database optimization configuration
MARKDOWN;

        File::put("{$path}/README.md", $readme);
    }
}
