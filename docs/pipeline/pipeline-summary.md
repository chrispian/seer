# Fragment Pipeline - Executive Summary

**Generated:** 2025-09-20
**Audit:** ENG-01 Pipeline Analysis

## Pipeline Overview

The Seer fragment processing pipeline consists of **10 stages** that transform user input into searchable, categorized, and embedded knowledge fragments. The pipeline processes approximately **1-3 AI calls per fragment** depending on configuration.

### Key Statistics
- **Entry Point:** FragmentController (HTTP endpoint)
- **AI Touchpoints:** 4 stages (ParseChaos, Enrich, TypeInference, Embeddings)
- **Queue Processing:** 2 queues (fragments, embeddings)
- **Processing Mode:** Mixed sync/async with resilient error handling
- **Model Providers:** 4 supported (OpenAI, Anthropic, Ollama, OpenRouter)

## Critical Issues Identified

### 🔴 High Priority
1. **No Temperature Controls** - AI calls lack deterministic temperature settings
2. **Weak JSON Validation** - Prompts rely on regex instead of schema validation
3. **Hardcoded Model Selection** - ParseChaosFragment bypasses ModelSelectionService
4. **Missing Correlation IDs** - No end-to-end request tracing capability

### 🟡 Medium Priority
5. **Redundant Entity Extraction** - ParseAtomicFragment + ExtractMetadataEntities overlap
6. **Static Tagging System** - Keyword-based instead of AI-powered suggestions
7. **No Confidence Calibration** - Fixed 0.7 threshold across all models
8. **Limited Error Context** - Insufficient debugging information in failures

## Architecture Strengths

✅ **ModelSelectionService** - Sophisticated priority-based model selection
✅ **Queue Isolation** - Separate queues for different processing types
✅ **Fallback Mechanisms** - Each AI stage has rule-based alternatives
✅ **Provider Abstraction** - Multi-vendor AI support with unified interface
✅ **Vault Routing** - Flexible content routing with rule engine

## Recommended Actions

### Phase 1: Stabilization (Week 1-2)
```php
// Add deterministic controls
'temperature' => 0.1,  // For classification
'temperature' => 0.3,  // For creative tasks

// Implement JSON schema validation
$schema = json_decode(file_get_contents('schema/fragment.json'));
$validator = new JsonSchema\Validator();
$validator->validate($response, $schema);

// Add correlation IDs
Log::withContext(['correlation_id' => $correlationId]);
```

### Phase 2: Model Optimization (Week 3-4)
- Replace hardcoded Ollama in ParseChaosFragment with ModelSelectionService
- Fine-tune model assignments per pipeline stage
- Add confidence calibration per model type
- Implement A/B testing for model comparison

### Phase 3: Enhanced Intelligence (Week 5-6)
- Consolidate entity extraction into unified service
- Replace static tagging with AI-powered suggestions
- Add hierarchical tag relationships
- Implement few-shot examples for better classification

## Model Recommendations

| Stage | Current | Recommended Primary | Recommended Fallback |
|-------|---------|-------------------|---------------------|
| **Chaos Parsing** | Ollama/llama3 (hardcoded) | gpt-4o-mini | claude-3-5-haiku-latest |
| **Enrichment** | ModelSelectionService | gpt-4o-mini | claude-3-5-sonnet-latest |
| **Type Inference** | ModelSelectionService | gpt-4o-mini | gpt-3.5-turbo |
| **Embeddings** | Configurable | text-embedding-3-small | nomic-embed-text |

## Success Metrics

### Immediate (Post-Phase 1)
- 🎯 **99.5%** JSON parsing success rate
- 🎯 **<2s** average pipeline completion time
- 🎯 **100%** request traceability with correlation IDs

### Long-term (Post-Phase 3)
- 🎯 **95%** type classification accuracy
- 🎯 **<50%** manual tag correction rate
- 🎯 **<5%** pipeline error rate with graceful degradation

## Risk Assessment

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| AI Provider Outage | High | Medium | Circuit breakers + fallbacks |
| Model Quality Drift | Medium | Low | Response monitoring + alerts |
| Queue Backlog | Medium | Medium | Auto-scaling + monitoring |
| JSON Parsing Failures | High | High | Schema validation + retries |

## Integration Points

### With AI-01 Provider Abstraction
- Coordinate model provider interface standardization
- Ensure compatibility with unified AI provider management
- Align on error handling and fallback strategies

### With ENG-02 Vault Routing
- Pipeline already integrates VaultRoutingRuleService
- Consider enhanced routing based on AI-inferred content types
- Coordinate vault-specific model preferences

## Cost Implications

### Current Monthly Estimate (1000 fragments/day)
- **ParseChaos:** ~$15/month (assuming gpt-4o-mini migration)
- **Enrichment:** ~$20/month (optimized model selection)
- **TypeInference:** ~$10/month (classification tasks)
- **Embeddings:** ~$5/month (text-embedding-3-small)
- **Total:** ~$50/month vs current unknown costs

### ROI Factors
- **Reduced manual categorization** - 80% time savings
- **Improved search accuracy** - Better embeddings and tagging
- **Enhanced user experience** - Faster, more reliable processing
- **Operational efficiency** - Better observability and debugging

## Next Steps

1. **Immediate:** Implement Phase 1 stabilization changes
2. **Week 2:** Begin model optimization work
3. **Week 3:** Start enhanced intelligence features
4. **Week 4:** Performance testing and optimization
5. **Week 5:** Production deployment with monitoring
6. **Week 6:** Post-deployment analysis and iteration

**Contact:** ENG-01 Pipeline Audit Agent
**Documentation:** `/docs/pipeline/eng-01-audit.md`
**Implementation Tracking:** Update PROJECT_PLAN.md with specific tasks