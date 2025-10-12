# Area 3: Content-Addressable Storage (CAS) & Artifact System

## Overview
Implement proper content-addressable storage with SHA256-based deduplication for user uploads and system artifacts, enabling efficient storage and retrieval of media files.

## Current Problems
- Basic artifacts table exists but lacks CAS implementation
- No deduplication for uploaded files
- No user upload pipeline
- Mixed artifact types without proper categorization
- No integration with Fragment system
- Missing file validation and security checks

## Proposed Solution

### Core CAS Architecture

```php
namespace App\Services\CAS;

class ContentAddressableStorage
{
    protected string $storagePath;
    protected ArtifactRepository $repository;
    
    public function store($content, array $metadata = []): Artifact
    {
        // Calculate hash
        $hash = $this->calculateHash($content);
        
        // Check if content already exists
        $existing = $this->repository->findByHash($hash);
        if ($existing) {
            return $this->linkToExisting($existing, $metadata);
        }
        
        // Store new content
        $path = $this->getStoragePath($hash);
        $this->storeContent($content, $path);
        
        // Create artifact record
        return $this->createArtifact($hash, $path, $content, $metadata);
    }
    
    protected function calculateHash($content): string
    {
        if (is_resource($content)) {
            $ctx = hash_init('sha256');
            while (!feof($content)) {
                hash_update($ctx, fread($content, 8192));
            }
            return hash_final($ctx);
        }
        
        return hash('sha256', $content);
    }
    
    protected function getStoragePath(string $hash): string
    {
        // Use first 2 chars for directory sharding
        $dir1 = substr($hash, 0, 2);
        $dir2 = substr($hash, 2, 2);
        
        return sprintf('%s/%s/%s/%s', 
            $this->storagePath,
            $dir1,
            $dir2,
            $hash
        );
    }
}
```

### Enhanced Artifact Model

```php
namespace App\Models;

class Artifact extends Model
{
    protected $fillable = [
        'hash',           // SHA256 hash of content
        'type',           // file/image/video/document/data
        'mime_type',      
        'size',           
        'storage_path',   // CAS path
        'original_name',  // User's filename
        'metadata',       // JSONB metadata
        'reference_count', // Number of references
        'owner_id',       // User who uploaded
        'visibility',     // public/private/system
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'reference_count' => 'integer',
        'size' => 'integer',
    ];
    
    public function references()
    {
        return $this->hasMany(ArtifactReference::class);
    }
    
    public function fragments()
    {
        return $this->belongsToMany(Fragment::class, 'fragment_artifacts');
    }
    
    public function incrementReferences(): void
    {
        $this->increment('reference_count');
    }
    
    public function decrementReferences(): void
    {
        $this->decrement('reference_count');
        
        if ($this->reference_count <= 0) {
            $this->scheduleForDeletion();
        }
    }
}
```

### User Upload Pipeline

```php
namespace App\Services\Upload;

class UserUploadService
{
    protected ContentAddressableStorage $cas;
    protected FileValidator $validator;
    protected VirusScanner $scanner;
    protected MediaProcessor $processor;
    
    public function handleUpload(UploadedFile $file, User $user): UploadResult
    {
        // Validate file
        $validation = $this->validator->validate($file);
        if (!$validation->passes()) {
            return UploadResult::failed($validation->errors());
        }
        
        // Scan for viruses
        if ($this->scanner->isEnabled()) {
            $scanResult = $this->scanner->scan($file);
            if (!$scanResult->isClean()) {
                return UploadResult::failed(['security' => 'File failed security scan']);
            }
        }
        
        // Process media files (thumbnails, metadata extraction)
        $processed = $this->processor->process($file);
        
        // Store in CAS
        $artifact = $this->cas->store(
            $file->getContent(),
            [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'owner_id' => $user->id,
                'processed' => $processed->toArray(),
            ]
        );
        
        // Create Fragment if needed
        if ($this->shouldCreateFragment($file)) {
            $this->createFragmentForArtifact($artifact, $user);
        }
        
        return UploadResult::success($artifact);
    }
}
```

### File Validation Service

```php
class FileValidator
{
    protected array $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm',
        'audio/mpeg', 'audio/wav',
        'application/pdf',
        'text/plain', 'text/markdown',
        'application/json', 'text/csv',
    ];
    
    protected int $maxFileSize = 104857600; // 100MB
    
    public function validate(UploadedFile $file): ValidationResult
    {
        $errors = [];
        
        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            $errors[] = 'File too large (max ' . $this->formatBytes($this->maxFileSize) . ')';
        }
        
        // Verify file content matches MIME type
        if (!$this->verifyFileContent($file)) {
            $errors[] = 'File content does not match type';
        }
        
        // Check for malicious patterns
        if ($this->containsMaliciousPatterns($file)) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        return new ValidationResult(empty($errors), $errors);
    }
    
    protected function verifyFileContent(UploadedFile $file): bool
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedType = $finfo->file($file->getPathname());
        
        return $detectedType === $file->getMimeType();
    }
}
```

### Media Processing

```php
class MediaProcessor
{
    public function process(UploadedFile $file): ProcessingResult
    {
        $result = new ProcessingResult();
        
        $type = $this->detectMediaType($file);
        
        switch ($type) {
            case 'image':
                $result->merge($this->processImage($file));
                break;
            case 'video':
                $result->merge($this->processVideo($file));
                break;
            case 'document':
                $result->merge($this->processDocument($file));
                break;
        }
        
        return $result;
    }
    
    protected function processImage(UploadedFile $file): array
    {
        $image = Image::make($file);
        
        // Generate thumbnails
        $thumbnails = [
            'small' => $this->generateThumbnail($image, 150, 150),
            'medium' => $this->generateThumbnail($image, 400, 400),
            'large' => $this->generateThumbnail($image, 800, 800),
        ];
        
        // Extract metadata
        $metadata = [
            'width' => $image->width(),
            'height' => $image->height(),
            'orientation' => $image->exif('Orientation'),
            'taken_at' => $image->exif('DateTimeOriginal'),
            'camera' => $image->exif('Model'),
            'location' => $this->extractGPSData($image),
        ];
        
        // Store thumbnails in CAS
        foreach ($thumbnails as $size => $thumb) {
            $artifact = $this->cas->store($thumb, [
                'type' => 'thumbnail',
                'size' => $size,
                'parent_hash' => null, // Will be set later
            ]);
            $metadata['thumbnails'][$size] = $artifact->hash;
        }
        
        return [
            'type' => 'image',
            'metadata' => $metadata,
        ];
    }
}
```

### Artifact Reference Management

```php
class ArtifactReferenceManager
{
    public function createReference(
        Artifact $artifact,
        Model $referencingModel,
        string $referenceType = 'attachment'
    ): ArtifactReference {
        $reference = ArtifactReference::create([
            'artifact_id' => $artifact->id,
            'referenceable_type' => get_class($referencingModel),
            'referenceable_id' => $referencingModel->id,
            'reference_type' => $referenceType,
        ]);
        
        $artifact->incrementReferences();
        
        return $reference;
    }
    
    public function removeReference(ArtifactReference $reference): void
    {
        $artifact = $reference->artifact;
        $reference->delete();
        $artifact->decrementReferences();
    }
    
    public function garbageCollect(): int
    {
        $deleted = 0;
        
        // Find artifacts with zero references
        $orphaned = Artifact::where('reference_count', 0)
            ->where('created_at', '<', now()->subDays(7))
            ->get();
        
        foreach ($orphaned as $artifact) {
            // Delete physical file
            Storage::delete($artifact->storage_path);
            
            // Delete thumbnails
            if ($artifact->metadata['thumbnails'] ?? null) {
                foreach ($artifact->metadata['thumbnails'] as $thumbHash) {
                    $this->deleteByHash($thumbHash);
                }
            }
            
            // Delete record
            $artifact->delete();
            $deleted++;
        }
        
        return $deleted;
    }
}
```

### Integration with Fragments

```php
class FragmentArtifactService
{
    public function attachArtifactToFragment(
        Fragment $fragment,
        Artifact $artifact,
        array $metadata = []
    ): void {
        $fragment->artifacts()->attach($artifact->id, [
            'metadata' => json_encode($metadata),
            'position' => $fragment->artifacts()->count() + 1,
        ]);
        
        $this->referenceManager->createReference(
            $artifact,
            $fragment,
            'fragment_attachment'
        );
    }
    
    public function createFragmentFromArtifact(
        Artifact $artifact,
        User $user
    ): Fragment {
        $message = $this->generateMessageFromArtifact($artifact);
        
        $fragment = Fragment::create([
            'message' => $message,
            'type' => 'media',
            'source' => 'user_upload',
            'source_key' => 'user_upload',
            'metadata' => [
                'artifact_hash' => $artifact->hash,
                'original_name' => $artifact->original_name,
                'mime_type' => $artifact->mime_type,
            ],
        ]);
        
        $this->attachArtifactToFragment($fragment, $artifact);
        
        return $fragment;
    }
}
```

## Database Changes

```sql
-- Enhanced artifacts table
ALTER TABLE artifacts 
ADD COLUMN hash VARCHAR(64) UNIQUE NOT NULL,
ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'file',
ADD COLUMN mime_type VARCHAR(255),
ADD COLUMN size BIGINT,
ADD COLUMN storage_path TEXT NOT NULL,
ADD COLUMN original_name TEXT,
ADD COLUMN reference_count INT DEFAULT 0,
ADD COLUMN owner_id UUID REFERENCES users(id),
ADD COLUMN visibility VARCHAR(20) DEFAULT 'private',
ADD COLUMN processed_at TIMESTAMP,
ADD COLUMN deleted_at TIMESTAMP;

CREATE INDEX idx_artifacts_hash ON artifacts(hash);
CREATE INDEX idx_artifacts_owner ON artifacts(owner_id);
CREATE INDEX idx_artifacts_type ON artifacts(type);
CREATE INDEX idx_artifacts_reference_count ON artifacts(reference_count);

-- Artifact references table
CREATE TABLE artifact_references (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    artifact_id UUID NOT NULL REFERENCES artifacts(id) ON DELETE CASCADE,
    referenceable_type VARCHAR(255) NOT NULL,
    referenceable_id UUID NOT NULL,
    reference_type VARCHAR(50) DEFAULT 'attachment',
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(artifact_id, referenceable_type, referenceable_id)
);

CREATE INDEX idx_artifact_refs_artifact ON artifact_references(artifact_id);
CREATE INDEX idx_artifact_refs_referenceable ON artifact_references(referenceable_type, referenceable_id);

-- Fragment artifacts junction table
CREATE TABLE fragment_artifacts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    fragment_id UUID NOT NULL REFERENCES fragments(id) ON DELETE CASCADE,
    artifact_id UUID NOT NULL REFERENCES artifacts(id) ON DELETE CASCADE,
    position INT DEFAULT 0,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(fragment_id, artifact_id)
);

CREATE INDEX idx_fragment_artifacts_fragment ON fragment_artifacts(fragment_id);
CREATE INDEX idx_fragment_artifacts_artifact ON fragment_artifacts(artifact_id);
```

## Storage Configuration

```php
// config/cas.php
return [
    'storage' => [
        'driver' => env('CAS_STORAGE_DRIVER', 'local'),
        'path' => env('CAS_STORAGE_PATH', storage_path('cas')),
        's3' => [
            'bucket' => env('CAS_S3_BUCKET'),
            'region' => env('CAS_S3_REGION', 'us-east-1'),
            'prefix' => env('CAS_S3_PREFIX', 'cas'),
        ],
    ],
    
    'validation' => [
        'max_file_size' => env('CAS_MAX_FILE_SIZE', 104857600), // 100MB
        'allowed_mime_types' => explode(',', env('CAS_ALLOWED_TYPES', 
            'image/jpeg,image/png,image/gif,video/mp4,application/pdf')),
        'scan_viruses' => env('CAS_SCAN_VIRUSES', true),
    ],
    
    'processing' => [
        'generate_thumbnails' => true,
        'thumbnail_sizes' => [
            'small' => [150, 150],
            'medium' => [400, 400],
            'large' => [800, 800],
        ],
        'extract_metadata' => true,
        'ocr_documents' => env('CAS_OCR_ENABLED', false),
    ],
    
    'garbage_collection' => [
        'enabled' => true,
        'retention_days' => 7,
        'run_at' => '03:00',
    ],
];
```

## Implementation Strategy

### Phase 1: CAS Foundation (Week 1)
1. Implement ContentAddressableStorage class
2. Create enhanced artifacts table migration
3. Build hash-based storage system
4. Add reference counting

### Phase 2: Upload Pipeline (Week 2)
1. Create file validation service
2. Implement virus scanning integration
3. Build media processing pipeline
4. Add thumbnail generation

### Phase 3: Integration (Week 3)
1. Connect with Fragment system
2. Build garbage collection
3. Create admin UI for artifacts
4. Add S3 storage option

## Success Criteria
- Zero duplicate files stored
- All uploads validated and scanned
- Automatic thumbnail generation
- Efficient garbage collection
- <2 second upload response time

## Security Considerations
- File type validation
- Content verification
- Virus scanning
- Access control per artifact
- Secure storage paths (no direct access)

## Next Steps
1. Set up virus scanning service
2. Configure S3 bucket for production
3. Build upload UI components
4. Create artifact management dashboard
5. Implement garbage collection job