# Area 1: Unified Import Contract System

## Overview
Create standardized interfaces and adapters for all import operations, enabling consistent handling of diverse data sources while reducing code duplication.

## Current Problems
- Each import service has unique implementation patterns
- No standardized error handling or retry logic
- Difficult to add new import sources
- Code duplication across services (~40% similar code)
- Inconsistent statistics and reporting

## Proposed Solution

### Core Architecture

```php
namespace App\Contracts\Import;

interface ImportAdapter
{
    public function validate(array $config): bool;
    public function parse($source): ImportedContent;
    public function transform(ImportedContent $content): array;
    public function store(array $data): Collection;
    public function getStats(): ImportStats;
}

interface ImportedContent
{
    public function getItems(): Collection;
    public function getMetadata(): array;
    public function getChecksum(): string;
}

interface ImportStats
{
    public function getProcessed(): int;
    public function getImported(): int;
    public function getSkipped(): int;
    public function getErrors(): array;
    public function getDuration(): float;
}
```

### Media Import Implementation

```php
namespace App\Services\Import\Media;

class MediaItem
{
    public string $externalId;
    public string $title;
    public array $creators;      // authors/directors/artists
    public ?string $description;
    public ?DateTime $releaseDate;
    public string $mediaType;     // book/movie/album/show
    public array $metadata;
    public string $status;        // want/consuming/completed/abandoned
    public ?float $rating;
    public ?DateTime $consumedAt;
}

abstract class MediaImportAdapter implements ImportAdapter
{
    use RateLimitingTrait;
    use ChecksumDeduplicationTrait;
    
    abstract protected function parseSource($source): Collection;
    abstract protected function mapToMediaItem(array $data): MediaItem;
    
    public function transform(ImportedContent $content): array
    {
        return $content->getItems()->map(function ($item) {
            $mediaItem = $this->mapToMediaItem($item);
            return $this->transformMediaItem($mediaItem);
        })->toArray();
    }
    
    protected function transformMediaItem(MediaItem $item): array
    {
        return [
            'type' => 'media',
            'title' => $item->title,
            'message' => $this->buildMessage($item),
            'metadata' => [
                'media_type' => $item->mediaType,
                'external_id' => $item->externalId,
                'creators' => $item->creators,
                'status' => $item->status,
                'rating' => $item->rating,
                'release_date' => $item->releaseDate,
                'consumed_at' => $item->consumedAt,
            ],
            'source_key' => $this->getSourceKey(),
        ];
    }
}
```

### Specific Adapters

```php
class HardcoverAdapter extends MediaImportAdapter
{
    protected function getSourceKey(): string
    {
        return 'hardcover';
    }
    
    protected function mapToMediaItem(array $data): MediaItem
    {
        $item = new MediaItem();
        $item->externalId = $data['id'];
        $item->title = $data['title'];
        $item->mediaType = 'book';
        $item->creators = $this->extractAuthors($data['contributions']);
        $item->description = $data['description'] ?? null;
        $item->releaseDate = $data['release_date'] ? 
            Carbon::parse($data['release_date']) : null;
        $item->status = self::STATUS_MAP[$data['status_id']] ?? 'unknown';
        
        return $item;
    }
}

class LetterboxdAdapter extends MediaImportAdapter
{
    protected function parseSource($source): Collection
    {
        // Parse Letterboxd CSV export format
        $csv = Reader::createFromPath($source);
        $csv->setHeaderOffset(0);
        
        return collect($csv->getRecords());
    }
    
    protected function mapToMediaItem(array $data): MediaItem
    {
        $item = new MediaItem();
        $item->externalId = $data['Letterboxd URI'] ?? Str::slug($data['Name']);
        $item->title = $data['Name'];
        $item->mediaType = 'movie';
        $item->creators = [$data['Directors'] ?? 'Unknown'];
        $item->releaseDate = $data['Year'] ? 
            Carbon::createFromFormat('Y', $data['Year']) : null;
        $item->rating = $data['Rating'] ? (float) $data['Rating'] : null;
        $item->consumedAt = $data['Watched Date'] ? 
            Carbon::parse($data['Watched Date']) : null;
        $item->status = $item->consumedAt ? 'completed' : 'want';
        
        return $item;
    }
}
```

## Simplification Components

### 1. Rate Limiting Trait
```php
trait RateLimitingTrait
{
    protected int $requestCount = 0;
    protected Carbon $windowStart;
    protected array $rateLimits = [
        'requests_per_minute' => 60,
        'requests_per_day' => 500,
    ];
    
    protected function checkRateLimit(): bool
    {
        if ($this->requestCount >= $this->rateLimits['requests_per_minute']) {
            $elapsed = now()->diffInSeconds($this->windowStart);
            if ($elapsed < 60) {
                sleep(60 - $elapsed);
                $this->resetWindow();
            }
        }
        
        $this->requestCount++;
        return true;
    }
}
```

### 2. Checksum Deduplication Service
```php
class ChecksumService
{
    public function calculateChecksum($content): string
    {
        return hash('sha256', $this->normalize($content));
    }
    
    public function isDuplicate(string $checksum, string $source): bool
    {
        return DB::table('import_checksums')
            ->where('checksum', $checksum)
            ->where('source', $source)
            ->exists();
    }
    
    public function markAsImported(string $checksum, string $source): void
    {
        DB::table('import_checksums')->insert([
            'checksum' => $checksum,
            'source' => $source,
            'imported_at' => now(),
        ]);
    }
}
```

### 3. Unified Metrics Interface
```php
class ImportMetrics implements ImportStats
{
    protected array $metrics = [
        'processed' => 0,
        'imported' => 0,
        'skipped' => 0,
        'errors' => [],
        'start_time' => null,
        'end_time' => null,
    ];
    
    public function toArray(): array
    {
        return array_merge($this->metrics, [
            'duration' => $this->getDuration(),
            'rate' => $this->getImportRate(),
            'success_rate' => $this->getSuccessRate(),
        ]);
    }
    
    public function logToTelemetry(): void
    {
        Telemetry::record('import', $this->toArray());
    }
}
```

## Migration Strategy

### Phase 1: Foundation (Week 1)
1. Create base interfaces and traits
2. Implement ChecksumService
3. Build ImportMetrics class
4. Create MediaImportAdapter base class

### Phase 2: Adapter Implementation (Week 2)
1. Migrate HardcoverAdapter
2. Create LetterboxdAdapter
3. Update existing imports to use new interfaces
4. Add comprehensive tests

### Phase 3: Integration (Week 3)
1. Update commands to use new adapters
2. Create unified import command
3. Add import source registry
4. Build admin UI for imports

## Success Criteria
- All imports use standardized interfaces
- New import sources can be added in <30 minutes
- 50% reduction in import service code
- Zero data loss during migration
- Consistent error handling across all imports

## Testing Requirements
- Unit tests for each adapter
- Integration tests for end-to-end flow
- Performance benchmarks vs current system
- Data integrity validation

## Database Changes
```sql
CREATE TABLE import_checksums (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    checksum VARCHAR(64) NOT NULL,
    source VARCHAR(50) NOT NULL,
    imported_at TIMESTAMP NOT NULL,
    metadata JSONB DEFAULT '{}',
    UNIQUE(checksum, source)
);

CREATE INDEX idx_import_checksums_source ON import_checksums(source);
CREATE INDEX idx_import_checksums_imported_at ON import_checksums(imported_at);
```

## Configuration
```php
// config/imports.php
return [
    'adapters' => [
        'hardcover' => HardcoverAdapter::class,
        'letterboxd' => LetterboxdAdapter::class,
        'readwise' => ReadwiseAdapter::class,
        'chatgpt' => ChatGptAdapter::class,
    ],
    
    'rate_limits' => [
        'default' => [
            'requests_per_minute' => 60,
            'requests_per_day' => 1000,
        ],
        'hardcover' => [
            'requests_per_minute' => 50,
            'requests_per_day' => 500,
        ],
    ],
    
    'storage' => [
        'use_fragments' => true,
        'deduplicate' => true,
        'batch_size' => 100,
    ],
];
```

## Next Steps
1. Review and approve design
2. Create database migration
3. Implement base contracts
4. Build first adapter (Letterboxd)
5. Migrate existing imports incrementally