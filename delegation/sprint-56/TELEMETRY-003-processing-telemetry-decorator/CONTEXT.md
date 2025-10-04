# TELEMETRY-003: Fragment Processing Telemetry Decorator - Context

## Current State Analysis

### Fragment Processing Pipeline
**Main Orchestrator**: `app/Jobs/ProcessFragmentJob.php:33-102`
- Logs emoji strings without structured fields
- Cannot correlate job retries or determine which analyzers fired
- Missing timing and outcome data for individual steps

**Current Logging Pattern**:
```php
// ProcessFragmentJob - line 32-33
$messages = [];
$fragments = [];
// No structured logging for job lifecycle
```

### Enrichment Actions (No Telemetry)
**Actions with Missing Telemetry**:
- `app/Actions/ExtractJsonMetadata.php:11-63` - Metadata extraction decisions in memory
- `app/Actions/EnrichAssistantMetadata.php:13-120` - Enrichment steps no status tracking  
- `app/Actions/SuggestTags.php:21-53` - Tag suggestions no duration/outcome data
- `app/Actions/ParseAtomicFragment.php` - Fragment parsing no instrumentation
- `app/Actions/GenerateAutoTitle.php` - Title generation no telemetry

**Current Issues**:
- No per-step duration tracking
- No indication which fragment/object IDs were touched
- Enrichment failures disappear without context
- Cannot measure processing step performance

### Pipeline Architecture
**Processing Flow**:
```
ProcessFragmentJob
├── ParseAtomicFragment (no logs)
├── ExtractMetadataEntities (no logs)  
├── GenerateAutoTitle (no logs)
└── Enrichment Pipeline
    ├── ExtractJsonMetadata (no logs)
    ├── EnrichAssistantMetadata (no logs)
    └── SuggestTags (no logs)
```

## Target Telemetry Schema

### Processing Job Events
```json
{
  "event": "processing.job.started",
  "correlation_id": "uuid",
  "job_id": "uuid",
  "fragment_id": 123,
  "processing_type": "fragment_enrichment",
  "user_id": "local-default"
}
```

### Step-Level Events
```json
{
  "event": "processing.step.completed",
  "correlation_id": "uuid", 
  "job_id": "uuid",
  "step": "ExtractJsonMetadata",
  "fragment_id": 123,
  "duration_ms": 250.5,
  "outcome": "success",
  "generated_keys": {
    "metadata_fields": ["title", "summary", "type"],
    "entity_count": 3
  },
  "user_id": "local-default"
}
```

### Error Events
```json
{
  "event": "processing.step.failed",
  "correlation_id": "uuid",
  "job_id": "uuid", 
  "step": "EnrichAssistantMetadata",
  "fragment_id": 123,
  "duration_ms": 180.2,
  "error": "AI provider timeout",
  "error_code": "TIMEOUT",
  "retry_count": 1,
  "user_id": "local-default"
}
```

## Decorator Pattern Design

### Telemetry Decorator Architecture
```php
// app/Services/Telemetry/ProcessingTelemetryDecorator.php
class ProcessingTelemetryDecorator
{
    public function wrapStep(string $stepName, callable $step, array $context = [])
    {
        // Start telemetry
        // Execute step with error handling
        // End telemetry with outcome
    }
}
```

### Integration Points
1. **ProcessFragmentJob**: Wrap entire job and individual steps
2. **Action Classes**: Instrument `__invoke()` methods
3. **Pipeline Steps**: Wrap Laravel Pipeline step execution
4. **Error Handling**: Capture exceptions with context

## Privacy & Performance Requirements

### What to Log (Metadata Only)
- Step names and execution timing
- Fragment IDs and generated object keys
- Outcome status (success/failure/retry)
- Error messages and codes (no sensitive content)
- Correlation and job IDs

### What NOT to Log
- Fragment content or AI-generated text
- User data or personal information  
- API keys or sensitive configuration
- Raw AI provider responses

### Performance Constraints
- **Per-Step Overhead**: <1ms additional latency
- **Memory Impact**: <10KB per processing job
- **Job Queue Impact**: No interference with retry logic
- **Total Pipeline Overhead**: <5ms for full enrichment

## Integration Requirements

### Dependencies
- **TELEMETRY-001**: Correlation middleware for request tracking
- **TELEMETRY-002**: Chat pipeline telemetry for message correlation
- **Existing Infrastructure**: Laravel jobs, actions, pipeline system

### Key Integration Files
- `app/Jobs/ProcessFragmentJob.php` - Main job orchestration
- `app/Actions/*` - All enrichment and processing actions
- `app/Http/Controllers/FragmentController.php` - Fragment processing triggers
- `app/Actions/ProcessAssistantFragment.php` - Assistant fragment processing

### Context Propagation
- Job correlation IDs from dispatching request
- Fragment IDs and metadata through processing steps
- Error context for debugging and alerting
- Generated keys (tag IDs, metadata fields) for analysis