# Implementation Plan: Enhanced Registry Schema & Metadata

## Overview
Extend the command registry infrastructure to capture comprehensive metadata from command manifests, enabling rich autocomplete experiences and dynamic help systems.

## Task Breakdown

### Phase 1: Database Schema Enhancement (2-3 hours)

#### 1.1 Create Migration File
**File**: `database/migrations/YYYY_MM_DD_HHMMSS_enhance_command_registry_metadata.php`
```php
public function up(): void
{
    Schema::table('command_registry', function (Blueprint $table) {
        $table->string('name')->nullable()->after('slug');
        $table->string('category')->nullable()->after('name');
        $table->text('summary')->nullable()->after('category');
        $table->text('usage')->nullable()->after('summary');
        $table->json('examples')->nullable()->after('usage');
        $table->json('aliases')->nullable()->after('examples');
        $table->json('keywords')->nullable()->after('aliases');
        
        // Performance indexes
        $table->index('category');
        $table->index('name');
    });
}
```

#### 1.2 Test Migration
- Run on fresh database installation
- Test upgrade path on existing data
- Verify rollback functionality

### Phase 2: CommandRegistry Model Enhancement (1-2 hours)

#### 2.1 Update Model Class
**File**: `app/Models/CommandRegistry.php`
```php
protected $fillable = [
    'slug', 'version', 'source_path', 'steps_hash',
    'capabilities', 'requires_secrets', 'reserved',
    'name', 'category', 'summary', 'usage', 
    'examples', 'aliases', 'keywords'
];

protected $casts = [
    'capabilities' => 'array',
    'requires_secrets' => 'array',
    'examples' => 'array',
    'aliases' => 'array', 
    'keywords' => 'array',
    'reserved' => 'boolean',
];
```

#### 2.2 Add Accessor Methods
```php
public function getAliasesAttribute($value): array
{
    return $value ? json_decode($value, true) : [];
}

public function getExamplesAttribute($value): array
{
    return $value ? json_decode($value, true) : [];
}

public function getKeywordsAttribute($value): array
{
    return $value ? json_decode($value, true) : [];
}

public function getDisplayNameAttribute(): string
{
    return $this->name ?? ucwords(str_replace(['-', '_'], ' ', $this->slug));
}
```

### Phase 3: CommandPackLoader Enhancement (3-4 hours)

#### 3.1 Help Block Parser
**File**: `app/Services/Commands/CommandPackLoader.php`
```php
protected function extractHelpMetadata(array $commandPack): array
{
    $manifest = $commandPack['manifest'];
    $help = $manifest['help'] ?? [];
    
    // Extract basic info from manifest if help block missing
    $fallbackName = $manifest['name'] ?? ucwords(str_replace(['-', '_'], ' ', $commandPack['slug']));
    $triggers = $manifest['triggers'] ?? [];
    $aliases = $triggers['aliases'] ?? [];
    
    return [
        'name' => $help['name'] ?? $fallbackName,
        'category' => $help['category'] ?? $this->inferCategory($commandPack),
        'summary' => $help['summary'] ?? null,
        'usage' => $help['usage'] ?? $this->generateDefaultUsage($commandPack),
        'examples' => $help['examples'] ?? [],
        'aliases' => $aliases,
        'keywords' => $help['keywords'] ?? [],
    ];
}
```

#### 3.2 Validation Logic
```php
protected function validateHelpMetadata(array $helpData, bool $isReserved): array
{
    $errors = [];
    
    // Name validation
    if (empty($helpData['name']) || strlen($helpData['name']) > 255) {
        $errors[] = "Name must be 1-255 characters";
    }
    
    // Summary required for non-reserved commands
    if (!$isReserved && empty($helpData['summary'])) {
        $errors[] = "Summary required for user commands";
    }
    
    // Category validation
    $validCategories = ['search', 'fragment', 'session', 'system', 'utility', 'navigation'];
    if (!empty($helpData['category']) && !in_array($helpData['category'], $validCategories)) {
        $errors[] = "Invalid category: {$helpData['category']}";
    }
    
    return $errors;
}
```

#### 3.3 Update Registry Cache Method
```php
protected function updateRegistryCache(string $slug, array $commandPack, string $sourcePath): void
{
    $stepsHash = $this->calculateStepsHash($commandPack);
    $capabilities = $this->extractCapabilities($commandPack);
    $requiresSecrets = $this->extractRequiredSecrets($commandPack);
    $reserved = $this->isReservedCommand($commandPack);
    
    // Extract help metadata
    $helpMetadata = $this->extractHelpMetadata($commandPack);
    $validationErrors = $this->validateHelpMetadata($helpMetadata, $reserved);
    
    if (!empty($validationErrors)) {
        \Log::warning("Help metadata validation failed for command: {$slug}", [
            'errors' => $validationErrors,
            'metadata' => $helpMetadata
        ]);
    }
    
    CommandRegistry::updateOrCreateEntry($slug, array_merge([
        'version' => $commandPack['manifest']['version'] ?? '1.0.0',
        'source_path' => $sourcePath,
        'steps_hash' => $stepsHash,
        'capabilities' => $capabilities,
        'requires_secrets' => $requiresSecrets,
        'reserved' => $reserved,
    ], $helpMetadata));
}
```

### Phase 4: Helper Utilities (1-2 hours)

#### 4.1 Category Inference
```php
protected function inferCategory(array $commandPack): string
{
    $slug = $commandPack['slug'];
    $manifest = $commandPack['manifest'];
    
    // Pattern-based inference
    if (str_contains($slug, 'search') || str_contains($slug, 'recall')) {
        return 'search';
    }
    if (str_contains($slug, 'frag') || str_contains($slug, 'note')) {
        return 'fragment';
    }
    if (str_contains($slug, 'session') || str_contains($slug, 'clear')) {
        return 'session';
    }
    if (str_contains($slug, 'help') || str_contains($slug, 'settings')) {
        return 'system';
    }
    
    return 'utility';
}
```

#### 4.2 Default Usage Generator
```php
protected function generateDefaultUsage(array $commandPack): string
{
    $slug = $commandPack['slug'];
    $triggers = $commandPack['manifest']['triggers'] ?? [];
    $slashTrigger = $triggers['slash'] ?? "/{$slug}";
    
    // Analyze steps to infer argument patterns
    $steps = $commandPack['manifest']['steps'] ?? [];
    $hasUserInput = $this->analysisUserInputSteps($steps);
    
    if ($hasUserInput) {
        return "{$slashTrigger} <input>";
    }
    
    return $slashTrigger;
}
```

### Phase 5: Testing & Validation (2-3 hours)

#### 5.1 Unit Tests
**File**: `tests/Unit/CommandPackLoaderTest.php`
```php
public function test_extracts_help_metadata_from_manifest()
{
    $commandPack = [
        'slug' => 'test-command',
        'manifest' => [
            'name' => 'Test Command',
            'help' => [
                'name' => 'Test Command',
                'category' => 'utility',
                'summary' => 'A test command',
                'usage' => '/test <arg>',
                'examples' => ['/test hello'],
                'keywords' => ['test', 'demo']
            ]
        ]
    ];
    
    $loader = new CommandPackLoader();
    $metadata = $loader->extractHelpMetadata($commandPack);
    
    $this->assertEquals('Test Command', $metadata['name']);
    $this->assertEquals('utility', $metadata['category']);
    $this->assertEquals('A test command', $metadata['summary']);
}
```

#### 5.2 Migration Tests
**File**: `tests/Feature/CommandRegistryMigrationTest.php`
```php
public function test_migration_adds_metadata_columns()
{
    Schema::dropIfExists('command_registry');
    
    // Run original migration
    $this->artisan('migrate', ['--path' => 'database/migrations/2025_10_03_212411_create_command_registry_table.php']);
    
    // Run enhancement migration
    $this->artisan('migrate', ['--path' => 'database/migrations/enhance_command_registry_metadata.php']);
    
    $this->assertTrue(Schema::hasColumn('command_registry', 'name'));
    $this->assertTrue(Schema::hasColumn('command_registry', 'summary'));
    $this->assertTrue(Schema::hasColumn('command_registry', 'aliases'));
}
```

#### 5.3 Integration Tests
**File**: `tests/Feature/CommandPackLoadingTest.php`
```php
public function test_loads_command_pack_with_help_metadata()
{
    // Create test command pack with help block
    $this->createTestCommandPack([
        'help' => [
            'name' => 'Integration Test',
            'category' => 'test',
            'summary' => 'Test command for integration',
            'examples' => ['/test-cmd hello']
        ]
    ]);
    
    $loader = app(CommandPackLoader::class);
    $loader->loadCommandPack('test-cmd');
    
    $registry = CommandRegistry::where('slug', 'test-cmd')->first();
    $this->assertEquals('Integration Test', $registry->name);
    $this->assertEquals('test', $registry->category);
    $this->assertContains('/test-cmd hello', $registry->examples);
}
```

## Success Validation

### Database Schema:
- [ ] Migration runs successfully on fresh and existing databases
- [ ] All new columns are properly nullable and indexed
- [ ] Rollback functionality works correctly

### Model Enhancement:
- [ ] New fields are accessible through Eloquent
- [ ] JSON casting works for arrays (aliases, examples, keywords)
- [ ] Accessor methods provide clean data access

### Loader Integration:
- [ ] Help metadata is extracted from manifests during pack loading
- [ ] Validation errors are logged but don't break loading
- [ ] Fallback logic works for packs without help blocks

### Performance:
- [ ] Registry queries perform well with new indexes
- [ ] JSON field access is efficient
- [ ] Migration completes in reasonable time

## Risk Mitigation

### Schema Changes:
- All new columns are nullable to maintain backward compatibility
- Indexes are added for anticipated query patterns
- Migration includes proper rollback logic

### Data Integrity:
- Validation errors are logged but non-blocking
- Graceful fallbacks for missing or invalid help data
- JSON field validation prevents malformed data

### Performance Impact:
- Strategic indexing for common query patterns
- JSON field optimization for array access
- Minimal additional storage overhead

This plan establishes the foundational metadata infrastructure that subsequent tasks will build upon for enhanced autocomplete and dynamic help systems.
