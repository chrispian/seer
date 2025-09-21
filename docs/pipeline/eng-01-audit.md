# ENG-01 Pipeline Audit Report

**Date:** 2025-09-20
**Auditor:** ENG-01 Pipeline Audit Agent
**Scope:** Fragment Ingestion/Enrichment Pipeline Analysis

## Executive Summary

This audit analyzes the complete fragment processing pipeline in the Seer application, evaluating AI integration, prompt determinism, model selection, and operational robustness. The pipeline processes user input through 10 distinct stages, from initial fragment creation to embedding generation.

**Key Findings:**
- Pipeline architecture is well-structured with clear separation of concerns
- AI integration lacks deterministic controls and robust error handling
- Prompt engineering needs system prompt structure and JSON schema validation
- Model selection service is sophisticated but needs configuration optimization
- Metadata extraction has redundancy between components
- Logging lacks correlation IDs for end-to-end traceability

## Pipeline Architecture Map

### Current Processing Flow

```
┌─────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│ FragmentController │────▶│ ParseChaosFragment │────▶│ Child Fragments   │
│ • Creates base     │    │ • AI parsing     │    │ • Async pipeline  │
│ • Sets type=chaos  │    │ • Splits multi-   │    │ • Queue: fragments│
│ • Sync execution   │    │   topic content   │    │                  │
└─────────────────┘    └──────────────────┘    └──────────────────┘
                                  │
                                  ▼
┌─────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│ ParseAtomicFragment │────▶│ ExtractMetadata  │────▶│ GenerateAutoTitle │
│ • Type prefix      │    │ Entities         │    │ • AI title gen   │
│ • Extract tags     │    │ • Regex patterns │    │ • Coming soon    │
│ • Clean message    │    │ • People, URLs   │    │                  │
└─────────────────┘    └──────────────────┘    └──────────────────┘
                                  │
                                  ▼
┌─────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│ EnrichFragmentWith │────▶│ InferFragmentType │────▶│ SuggestTags      │
│ Llama              │    │ • AI classification│    │ • Keyword matching│
│ • AI enrichment    │    │ • Confidence 0.7  │    │ • Simple heuristics│
│ • JSON response    │    │ • Fallback rules  │    │                  │
└─────────────────┘    └──────────────────┘    └──────────────────┘
                                  │
                                  ▼
┌─────────────────┐    ┌──────────────────┐
│ RouteToVault      │────▶│ EmbedFragmentAction │
│ • Vault directives│    │ • Vector embeddings│
│ • Rule-based      │    │ • Queue: embeddings│
│ • Default fallback│    │ • Content hashing │
└─────────────────┘    └──────────────────┘
```

### Component Details

| Stage | Input | Output | Sync/Async | AI Provider | Queue |
|-------|-------|--------|-------------|-------------|-------|
| **FragmentController** | HTTP Request | Fragment entity | Sync | - | - |
| **ParseChaosFragment** | Fragment | Child fragments | Async | Ollama (llama3) | fragments |
| **ParseAtomicFragment** | Fragment | Enhanced fragment | Sync | - | - |
| **ExtractMetadataEntities** | Fragment | Metadata entities | Sync | - | - |
| **GenerateAutoTitle** | Fragment | Fragment with title | Async | TBD | fragments |
| **EnrichFragmentWithLlama** | Fragment | JSON enrichment | Async | ModelSelectionService | fragments |
| **InferFragmentType** | Fragment | Typed fragment | Async | TypeInferenceService | fragments |
| **SuggestTags** | Fragment | Tagged fragment | Sync | - | - |
| **RouteToVault** | Fragment | Routed fragment | Sync | - | - |
| **EmbedFragmentAction** | Fragment | Embedding job | Async | Embeddings service | embeddings |

## Prompt Analysis & Determinism Assessment

### 1. ParseChaosFragment Prompt

**File:** `app/Actions/ParseChaosFragment.php:20-44`

**Current Prompt:**
```
The following text contains multiple different tasks or thoughts mixed together.

Split it into **multiple self-contained JSON fragments**. Each should represent **one idea or task**.

Output an array of valid JSON objects like this (Do not include markdown or anything except valid json):

[
  {
    "type": "todo",
    "message": "Call the doctor.",
    "tags": ["health"]
  },
  {
    "type": "reminder",
    "message": "Email the client before noon.",
    "tags": ["work"]
  }
]

Input:
{$fragment->message}

ONLY return an array of JSON objects. No explanation, no markdown, no prose.
```

**Issues Identified:**
- ❌ No system prompt structure
- ❌ No temperature control (relies on Ollama defaults)
- ❌ Limited JSON schema enforcement
- ❌ No retry mechanism for malformed responses
- ❌ Basic regex extraction for JSON cleanup

**Recommendations:**
- Implement system prompt with JSON schema validation
- Set explicit temperature=0.1 for deterministic parsing
- Add structured response validation with Pydantic/JSON Schema
- Implement retry logic with exponential backoff

### 2. EnrichFragmentWithLlama Prompt

**File:** `app/Actions/EnrichFragmentWithLlama.php:45-65`

**Current Prompt:**
```
Given the following user input, return a structured fragment in JSON.

Input:
{$fragment->message}

Output format:
{
  "type": "log",
  "message": "...",
  "tags": ["tag1", "tag2"],
  "metadata": {
    "confidence": 0.9
  },
  "state": {
    "status": "open"
  },
  "vault": "default"
}
Only return JSON. No markdown, no explanation.
```

**Issues Identified:**
- ❌ No system prompt role definition
- ✅ Fixed temperature=0.3 (good for creativity balance)
- ❌ Weak JSON enforcement
- ❌ No confidence calibration per model
- ❌ Limited error handling for malformed responses

**Recommendations:**
- Add system prompt defining the assistant's role
- Implement JSON schema validation
- Add model-specific confidence calibration
- Enhanced error recovery with fallback structured data

### 3. TypeInferenceService Prompt

**File:** `app/Services/AI/TypeInferenceService.php:148-176`

**Current Prompt:**
```
You are a text classifier that categorizes fragments of information into specific types.

Available types (JSON format):
{$typesJson}

Fragment to classify:
"{$fragment->message}"

Instructions:
1. Analyze the fragment content carefully
2. Choose the most appropriate type from the available types
3. Provide a confidence score between 0.0 and 1.0 (1.0 = completely confident)
4. If you're not confident (< 0.7), default to 'log'
5. Respond in this exact JSON format:

{
  "type": "selected_type_value",
  "confidence": 0.85,
  "reasoning": "Brief explanation of why this type was chosen"
}

Only respond with valid JSON, no additional text.
```

**Issues Identified:**
- ✅ Well-structured system prompt
- ✅ Clear confidence threshold (0.7)
- ✅ Structured JSON response format
- ✅ Fallback logic for low confidence
- ❌ No temperature control specified
- ❌ Static confidence threshold (not model-calibrated)

**Recommendations:**
- Add explicit temperature=0.1 for classification consistency
- Implement model-specific confidence thresholds
- Add few-shot examples for better accuracy
- Consider ensemble voting for critical classifications

## Model Selection & Fit Analysis

### ModelSelectionService Architecture

**File:** `app/Services/AI/ModelSelectionService.php`

**Strengths:**
- ✅ Sophisticated priority-based selection strategy
- ✅ Context-aware model assignment (vault, project, command)
- ✅ Provider availability checking
- ✅ Fallback mechanisms
- ✅ Support for multiple AI providers (OpenAI, Anthropic, Ollama, OpenRouter)

**Current Selection Priority:**
1. Command override (100) - Explicit model specification
2. Project preference (80) - Project metadata AI model settings
3. Vault preference (60) - Vault-specific model configuration
4. Global default (40) - System-wide default model
5. Fallback (20) - Emergency fallback model

### Provider Configuration Analysis

**File:** `config/fragments.php:12-91`

| Provider | Text Models | Embedding Models | Config Required |
|----------|-------------|------------------|-----------------|
| **OpenAI** | gpt-4o, gpt-4o-mini, gpt-4-turbo, gpt-3.5-turbo | text-embedding-3-large/small, ada-002 | OPENAI_API_KEY |
| **Anthropic** | claude-3-5-sonnet/haiku/opus-latest | None | ANTHROPIC_API_KEY |
| **Ollama** | llama3:latest/8b/70b, codellama:latest | nomic-embed-text, all-minilm | OLLAMA_BASE_URL |
| **OpenRouter** | claude-3.5-sonnet, gpt-4o, llama-3.1-70b | None | OPENROUTER_API_KEY |

### Model Fit Recommendations

#### For ParseChaosFragment (Multi-text Splitting)
- **Current:** Ollama llama3 (hardcoded)
- **Recommended:**
  - Primary: `gpt-4o-mini` (superior JSON compliance, faster)
  - Fallback: `claude-3-5-haiku-latest` (excellent instruction following)
  - Local: `llama3:8b` (acceptable for development)

#### For EnrichFragmentWithLlama (Content Enhancement)
- **Current:** ModelSelectionService (good)
- **Recommended models by use case:**
  - Production: `gpt-4o-mini` (balanced cost/performance)
  - High-quality: `claude-3-5-sonnet-latest` (superior reasoning)
  - Local development: `llama3:latest`

#### For TypeInferenceService (Classification)
- **Current:** ModelSelectionService with context priority
- **Recommended:**
  - Primary: `gpt-4o-mini` (excellent classification accuracy)
  - Alternative: `claude-3-5-haiku-latest` (fast, accurate)
  - Cost-optimized: `gpt-3.5-turbo`

#### For Embeddings
- **Current:** Configurable via EMBEDDINGS_PROVIDER
- **Recommended:**
  - Production: `text-embedding-3-small` (best price/performance)
  - High-dimensional: `text-embedding-3-large` (3072d for complex retrieval)
  - Local: `nomic-embed-text` (good quality, local)

## Metadata & Tagging Improvements

### Current Issues

#### 1. Redundant Entity Extraction
**Problem:** Both `ParseAtomicFragment` and `ExtractMetadataEntities` extract similar entities (people, URLs, tags) with slight variations.

**Files:**
- `app/Actions/ParseAtomicFragment.php:50-106`
- `app/Actions/ExtractMetadataEntities.php:25-104`

**Recommendation:** Consolidate entity extraction into a single, comprehensive service with standardized output format.

#### 2. Tagging Limitations
**Current Implementation:** `app/Actions/SuggestTags.php:29-42`
```php
$keywords = [
    'todo' => 'task',
    'insight' => 'idea',
    'link' => 'url',
    'reminder' => 'time',
];
```

**Issues:**
- ❌ Static keyword mapping
- ❌ No AI-assisted tagging
- ❌ Limited tag vocabulary
- ❌ No tag confidence scoring

**Recommendations:**
- Implement AI-powered tag suggestion using LLM
- Create dynamic tag vocabulary from historical data
- Add tag confidence scores and user feedback loops
- Support hierarchical tagging (parent-child relationships)

#### 3. Metadata Schema Inconsistencies
**Issues:**
- Mixed metadata storage (`metadata` field vs `parsed_entities` field)
- No versioning for metadata schema changes
- Limited provenance tracking

**Recommendations:**
- Standardize metadata schema with versioning
- Implement comprehensive provenance tracking (model used, confidence, timestamp)
- Add metadata validation and migration scripts

## Operational Hardening Recommendations

### 1. Logging & Observability

**Current State:**
- Basic debug logging in most components
- No correlation IDs for request tracing
- Limited error context capture

**Recommendations:**
```php
// Add correlation ID to all pipeline steps
$correlationId = Str::uuid();
Log::withContext(['correlation_id' => $correlationId, 'fragment_id' => $fragment->id]);

// Comprehensive error logging
Log::error('Pipeline step failed', [
    'step' => 'EnrichFragmentWithLlama',
    'fragment_id' => $fragment->id,
    'model_provider' => $selectedModel['provider'],
    'model_name' => $selectedModel['model'],
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'input_preview' => Str::limit($fragment->message, 100),
]);
```

### 2. Testing Coverage

**Current Gaps:**
- Limited AI pipeline integration tests
- No prompt regression testing
- Missing model output validation tests

**Recommended Test Structure:**
```php
// tests/Feature/Pipeline/FragmentPipelineTest.php
public function test_complete_pipeline_with_ai_responses()
{
    // Mock AI responses with realistic data
    // Test end-to-end pipeline execution
    // Verify metadata and entity extraction
    // Check embedding generation
}

// tests/Unit/AI/PromptRegressionTest.php
public function test_prompt_output_consistency()
{
    // Test prompt outputs against known good responses
    // Validate JSON schema compliance
    // Check confidence score calibration
}
```

### 3. Performance Monitoring

**Recommendations:**
- Add pipeline stage timing metrics
- Monitor AI provider response times and error rates
- Track queue depth and processing latency
- Implement circuit breakers for external AI services

### 4. Error Recovery

**Current State:** Basic try-catch with fallbacks

**Enhanced Strategy:**
```php
// Retry with exponential backoff
$attempt = 1;
$maxAttempts = 3;

while ($attempt <= $maxAttempts) {
    try {
        $result = $this->makeApiCall($selectedModel, $prompt);
        break;
    } catch (ApiException $e) {
        if ($attempt === $maxAttempts) {
            // Final fallback to rule-based processing
            return $this->fallbackProcessing($fragment);
        }
        sleep(pow(2, $attempt)); // Exponential backoff
        $attempt++;
    }
}
```

## Priority Recommendations

### High Priority (Implement First)

1. **Add Deterministic Controls**
   - Set explicit temperature=0.1 for classification tasks
   - Set temperature=0.3 for creative enrichment tasks
   - Add JSON schema validation for all AI responses

2. **Improve Error Handling**
   - Add retry logic with exponential backoff
   - Implement comprehensive fallback strategies
   - Add correlation IDs for request tracing

3. **Consolidate Entity Extraction**
   - Merge `ParseAtomicFragment` and `ExtractMetadataEntities` logic
   - Standardize output format in `parsed_entities` field
   - Add confidence scores for extracted entities

### Medium Priority

4. **Enhance Model Selection**
   - Fine-tune model assignments per pipeline stage
   - Add model-specific confidence calibration
   - Implement A/B testing framework for model comparison

5. **Improve Tagging System**
   - Replace static keyword matching with AI-powered tagging
   - Add tag confidence scores and user feedback
   - Implement hierarchical tag relationships

### Low Priority

6. **Advanced Features**
   - Add few-shot examples to classification prompts
   - Implement ensemble voting for critical decisions
   - Create pipeline performance dashboard
   - Add automated prompt regression testing

## Implementation Roadmap

### Phase 1: Stabilization (1-2 weeks)
- [ ] Add temperature controls to all AI calls
- [ ] Implement JSON schema validation
- [ ] Add correlation ID logging
- [ ] Create comprehensive error handling

### Phase 2: Optimization (2-3 weeks)
- [ ] Consolidate entity extraction
- [ ] Optimize model selection per pipeline stage
- [ ] Add model-specific confidence calibration
- [ ] Enhance tagging with AI assistance

### Phase 3: Advanced Features (3-4 weeks)
- [ ] Implement A/B testing framework
- [ ] Add performance monitoring dashboard
- [ ] Create automated testing suite
- [ ] Build tag relationship management

## Technical Debt & Risks

### Current Technical Debt
1. **Hardcoded Ollama model** in `ParseChaosFragment` bypasses ModelSelectionService
2. **Redundant entity extraction** logic across multiple components
3. **Limited prompt versioning** - no way to rollback problematic prompts
4. **Weak JSON validation** - relies on regex instead of proper schema validation

### Risk Mitigation
1. **AI Provider Failures:** Implement circuit breakers and graceful degradation
2. **Model Drift:** Add response quality monitoring and automated alerts
3. **Queue Backlog:** Monitor queue depths and add auto-scaling
4. **Data Quality:** Implement confidence scoring and manual review workflows

## Conclusion

The fragment processing pipeline demonstrates solid architectural foundations with sophisticated model selection capabilities. However, critical improvements are needed in deterministic controls, error handling, and operational observability. The recommended phased approach will systematically address these gaps while maintaining system stability.

**Next Steps:**
1. Prioritize Phase 1 implementation for immediate stability gains
2. Coordinate with AI-01 provider abstraction work for unified model management
3. Establish performance baselines before optimization work
4. Create monitoring dashboards for pipeline health visibility

This audit provides a roadmap for evolving the pipeline from a functional MVP to a production-ready, observable, and maintainable AI-powered system.