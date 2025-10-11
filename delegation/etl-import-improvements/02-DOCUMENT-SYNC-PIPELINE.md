# Area 2: Document Sync Pipeline

## Overview
Create intelligent sync system for documentation that preserves existing data while importing new content from docs/ and delegation/ folders, with smart routing based on content type.

## Current Problems
- No automated sync from docs/ folder
- Risk of overwriting user modifications
- Mixed content types in delegation folder
- No version tracking for document changes
- Manual process to identify documentation vs tasks

## Proposed Solution

### Core Architecture

```php
namespace App\Services\Sync;

class DocumentSyncService
{
    protected DocumentationImportService $importer;
    protected DelegationContentRouter $router;
    protected VersioningService $versioning;
    
    public function syncAll(): SyncResult
    {
        $result = new SyncResult();
        
        // Sync docs folder
        $result->merge($this->syncDocsFolder());
        
        // Sync delegation folder with routing
        $result->merge($this->syncDelegationFolder());
        
        return $result;
    }
}
```

### Version-Preserving Sync

```php
class DocumentVersioningService
{
    public function shouldCreateNewVersion(Documentation $existing, string $newContent): bool
    {
        // Never overwrite if user-modified
        if ($existing->is_user_modified) {
            return true;
        }
        
        // Check if content significantly changed
        $similarity = similar_text($existing->content, $newContent, $percent);
        return $percent < 95; // Create new version if >5% change
    }
    
    public function createVersion(Documentation $doc, string $newContent): Documentation
    {
        // Archive current version
        DocumentationVersion::create([
            'documentation_id' => $doc->id,
            'version' => $doc->version,
            'content' => $doc->content,
            'file_hash' => $doc->file_hash,
            'archived_at' => now(),
        ]);
        
        // Update with new content
        $doc->update([
            'content' => $newContent,
            'file_hash' => hash('sha256', $newContent),
            'version' => $doc->version + 1,
            'last_modified' => now(),
            'git_hash' => $this->getGitHash($doc->file_path),
        ]);
        
        return $doc;
    }
}
```

### Enhanced Documentation Import

```php
class EnhancedDocumentationImportService extends DocumentationImportService
{
    public function syncDocsFolder(): array
    {
        $basePath = base_path('docs');
        $stats = [
            'processed' => 0,
            'new' => 0,
            'updated' => 0,
            'versioned' => 0,
            'skipped' => 0,
        ];
        
        $files = File::allFiles($basePath);
        
        foreach ($files as $file) {
            if (!$this->shouldSync($file)) {
                $stats['skipped']++;
                continue;
            }
            
            $stats['processed']++;
            $relativePath = Str::after($file->getPathname(), $basePath . '/');
            $content = File::get($file);
            $fileHash = hash('sha256', $content);
            
            $existing = Documentation::where('file_path', $relativePath)->first();
            
            if (!$existing) {
                $this->importNewDocument($file, $relativePath, $content, $fileHash);
                $stats['new']++;
            } elseif ($existing->file_hash !== $fileHash) {
                if ($this->versioning->shouldCreateNewVersion($existing, $content)) {
                    $this->versioning->createVersion($existing, $content);
                    $stats['versioned']++;
                } else {
                    $this->updateDocument($existing, $content, $fileHash);
                    $stats['updated']++;
                }
            } else {
                $stats['skipped']++;
            }
        }
        
        return $stats;
    }
    
    protected function shouldSync(SplFileInfo $file): bool
    {
        // Skip non-markdown files
        if ($file->getExtension() !== 'md') {
            return false;
        }
        
        // Skip temporary or backup files
        if (Str::contains($file->getFilename(), ['.tmp', '.bak', '~'])) {
            return false;
        }
        
        // Skip files in excluded directories
        $excludedDirs = ['.git', 'node_modules', 'vendor', 'tmp'];
        foreach ($excludedDirs as $dir) {
            if (Str::contains($file->getPath(), "/{$dir}/")) {
                return false;
            }
        }
        
        return true;
    }
}
```

### Delegation Content Router

```php
class DelegationContentRouter
{
    protected array $patterns = [
        'task' => [
            'patterns' => [
                '/^T-[A-Z]{3}-\d{2}/',  // Task code format
                '/task_code:/',
                '/delegation_status:/',
            ],
            'handler' => 'syncToTask',
        ],
        'sprint' => [
            'patterns' => [
                '/^SPRINT-\d{2}/',
                '/sprint_code:/',
                '/sprint planning/i',
            ],
            'handler' => 'syncToSprint',
        ],
        'documentation' => [
            'patterns' => [
                '/^#\s+.*Guide/i',
                '/^#\s+.*Documentation/i',
                '/^#\s+.*Reference/i',
                '/^#\s+.*Architecture/i',
            ],
            'handler' => 'syncToDocumentation',
        ],
        'research' => [
            'patterns' => [
                '/^#\s+.*Research/i',
                '/^#\s+.*Analysis/i',
                '/^#\s+.*Investigation/i',
            ],
            'handler' => 'syncToDocumentation',
            'tags' => ['#research'],
        ],
    ];
    
    public function route(string $filePath): RoutingResult
    {
        $content = File::get($filePath);
        $filename = basename($filePath);
        
        // Check filename patterns first
        if (preg_match('/^T-[A-Z]{3}-\d{2}/', $filename)) {
            return $this->syncToTask($filePath, $content);
        }
        
        if (preg_match('/^SPRINT-\d{2}/', $filename)) {
            return $this->syncToSprint($filePath, $content);
        }
        
        // Analyze content
        foreach ($this->patterns as $type => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $content)) {
                    $handler = $config['handler'];
                    return $this->$handler($filePath, $content, $config);
                }
            }
        }
        
        // Default to documentation
        return $this->syncToDocumentation($filePath, $content);
    }
    
    protected function syncToTask(string $filePath, string $content): RoutingResult
    {
        $taskCode = $this->extractTaskCode($filePath, $content);
        
        $task = OrchestrationWorkItem::where('task_code', $taskCode)->first();
        
        if (!$task) {
            // Create task from content
            $task = $this->createTaskFromContent($taskCode, $content);
        }
        
        // Store content as artifact
        $artifact = OrchestrationArtifact::create([
            'work_item_id' => $task->id,
            'type' => 'task_content',
            'name' => basename($filePath),
            'content' => $content,
            'metadata' => [
                'source_file' => $filePath,
                'imported_at' => now(),
            ],
        ]);
        
        return new RoutingResult('task', $task, $artifact);
    }
    
    protected function syncToDocumentation(
        string $filePath, 
        string $content, 
        array $config = []
    ): RoutingResult {
        $relativePath = $this->getRelativePath($filePath);
        
        $doc = Documentation::updateOrCreate(
            ['file_path' => $relativePath],
            [
                'title' => $this->extractTitle($content, $relativePath),
                'content' => $content,
                'excerpt' => $this->extractExcerpt($content),
                'namespace' => 'delegation',
                'file_hash' => hash('sha256', $content),
                'subsystem' => $this->detectSubsystem($relativePath, $content),
                'purpose' => $this->detectPurpose($relativePath, $content),
                'tags' => array_merge(
                    $this->extractAutoTags($content),
                    $config['tags'] ?? []
                ),
                'last_modified' => File::lastModified($filePath),
            ]
        );
        
        return new RoutingResult('documentation', $doc);
    }
}
```

### Relationship Mapping

```php
class DocumentRelationshipMapper
{
    public function mapRelationships(Documentation $doc): void
    {
        // Extract code references
        $codeRefs = $this->extractCodeReferences($doc->content);
        foreach ($codeRefs as $ref) {
            $doc->addRelatedCodePath($ref);
        }
        
        // Find related documents
        $relatedDocs = $this->findRelatedDocuments($doc);
        foreach ($relatedDocs as $related) {
            $doc->addRelatedDoc($related->file_path);
        }
        
        // Link to tasks if mentioned
        $taskCodes = $this->extractTaskCodes($doc->content);
        foreach ($taskCodes as $code) {
            $task = OrchestrationWorkItem::where('task_code', $code)->first();
            if ($task) {
                DocumentTaskLink::firstOrCreate([
                    'documentation_id' => $doc->id,
                    'work_item_id' => $task->id,
                ]);
            }
        }
    }
    
    protected function findRelatedDocuments(Documentation $doc): Collection
    {
        // Use full-text search to find similar documents
        $searchTerms = $this->extractKeyTerms($doc->content);
        
        return Documentation::where('id', '!=', $doc->id)
            ->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->orWhereRaw(
                        "to_tsvector('english', content) @@ plainto_tsquery('english', ?)",
                        [$term]
                    );
                }
            })
            ->limit(5)
            ->get();
    }
}
```

## Database Changes

```sql
-- Version tracking table
CREATE TABLE documentation_versions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    documentation_id UUID NOT NULL REFERENCES documentation(id),
    version INT NOT NULL,
    content TEXT NOT NULL,
    file_hash VARCHAR(64),
    metadata JSONB DEFAULT '{}',
    archived_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_doc_versions_doc_id ON documentation_versions(documentation_id);
CREATE INDEX idx_doc_versions_archived ON documentation_versions(archived_at);

-- Document-Task linking table
CREATE TABLE document_task_links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    documentation_id UUID NOT NULL REFERENCES documentation(id),
    work_item_id UUID NOT NULL REFERENCES orchestration_work_items(id),
    link_type VARCHAR(50) DEFAULT 'reference',
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(documentation_id, work_item_id)
);

CREATE INDEX idx_doc_task_links_doc ON document_task_links(documentation_id);
CREATE INDEX idx_doc_task_links_task ON document_task_links(work_item_id);

-- Add user modification tracking
ALTER TABLE documentation 
ADD COLUMN is_user_modified BOOLEAN DEFAULT FALSE,
ADD COLUMN modified_by UUID REFERENCES users(id),
ADD COLUMN modification_note TEXT;
```

## Sync Command

```php
class SyncDocumentationCommand extends Command
{
    protected $signature = 'docs:sync
        {--source=all : Source to sync (all|docs|delegation)}
        {--dry-run : Preview changes without applying}
        {--force : Force update even if no changes detected}';
    
    protected $description = 'Sync documentation from file system';
    
    public function handle(DocumentSyncService $sync): int
    {
        $source = $this->option('source');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        $this->info("Starting documentation sync ({$source})...");
        
        $result = match($source) {
            'docs' => $sync->syncDocsFolder($dryRun, $force),
            'delegation' => $sync->syncDelegationFolder($dryRun, $force),
            default => $sync->syncAll($dryRun, $force),
        };
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $result->processed],
                ['New Documents', $result->new],
                ['Updated', $result->updated],
                ['Versioned', $result->versioned],
                ['Skipped', $result->skipped],
                ['Errors', $result->errors],
            ]
        );
        
        if ($dryRun) {
            $this->warn('DRY RUN - No changes were applied');
        }
        
        return $result->hasErrors() ? self::FAILURE : self::SUCCESS;
    }
}
```

## Implementation Strategy

### Phase 1: Foundation (Week 1)
1. Create versioning service and database tables
2. Implement enhanced documentation importer
3. Build content router with pattern matching
4. Add relationship mapper

### Phase 2: Integration (Week 2)
1. Create sync command with dry-run support
2. Add scheduled job for automatic sync
3. Build UI for version management
4. Implement conflict resolution

## Success Criteria
- Zero data loss during sync operations
- All docs/ files tracked in database
- Intelligent routing of delegation content
- Version history maintained
- Relationships automatically mapped

## Testing Requirements
- Unit tests for routing logic
- Integration tests for sync process
- Version preservation validation
- Performance tests for large folders

## Configuration
```php
// config/documentation.php
return [
    'sync' => [
        'auto_sync' => env('DOCS_AUTO_SYNC', false),
        'sync_interval' => env('DOCS_SYNC_INTERVAL', 3600), // seconds
        'version_threshold' => 0.95, // similarity threshold
        'excluded_dirs' => ['.git', 'node_modules', 'vendor'],
        'excluded_files' => ['.DS_Store', 'Thumbs.db'],
    ],
    
    'routing' => [
        'default_type' => 'documentation',
        'pattern_priority' => ['task', 'sprint', 'documentation', 'research'],
    ],
    
    'versioning' => [
        'enabled' => true,
        'max_versions' => 10,
        'archive_after_days' => 90,
    ],
];
```

## Next Steps
1. Review routing patterns with team
2. Define version retention policy
3. Create migration for new tables
4. Build prototype router
5. Test with sample delegation content