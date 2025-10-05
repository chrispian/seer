# AI-Dependent Command Audit Report

**Conducted:** October 4, 2025  
**Project:** Fragments Engine DSL Deterministic Foundation  
**Sprint:** 50  

## Executive Summary

### Overview
- **Total Commands Analyzed:** 30
- **AI-Dependent Commands Found:** 4
- **Migration Candidates:** 3
- **Already Migrated:** 1

### Key Findings
- **66% Success Rate:** 2 out of 3 active AI-dependent commands are addressable through deterministic migration
- **Significant Progress:** Todo parsing migration already completed (MIGRATE-TODO-001)
- **Focused Effort Required:** Only 1 active command (news-digest) requires new migration work
- **Clean Architecture:** AI usage is well-contained and follows consistent patterns

### Recommendation
Proceed with hybrid migration approach for news-digest command while maintaining AI capabilities for creative content generation. Total estimated effort: **50-70 hours** over 2 sprints.

---

## Detailed Command Analysis

### 1. Create Todo (Original) - `todo-original`
**Status:** ✅ MIGRATION COMPLETED  
**Location:** `fragments/commands/todo-original/command.yaml`

**AI Usage:**
- **Purpose:** Parse natural language input → structured JSON
- **Step:** `ai.generate` with todo parsing prompt
- **Complexity:** Medium (date parsing, priority inference, title extraction)

**Migration Status:**
- **Completed in:** MIGRATE-TODO-001 (Sprint 50)
- **Replacement:** Deterministic `text.parse` step with `TodoTextParser`
- **Result:** Zero AI dependency, improved performance (<1ms vs ~2000ms)

**Action Required:** Deprecate legacy command, redirect users to new deterministic version

---

### 2. Generate News Digest - `news-digest`
**Status:** 🔄 MIGRATION PENDING  
**Location:** `fragments/commands/news-digest/command.yaml`

**AI Usage:**
- **Purpose:** Generate structured news digest from topics
- **Model:** GPT-4 with 800 max tokens
- **Complexity:** High (content creation, factual accuracy, structured formatting)

**Current Flow:**
```yaml
topics → ai.generate(digest) → fragment.create
```

**Proposed Migration Strategy - Hybrid Approach:**
```yaml
topics → rss.fetch → template.format → [optional: ai.enhance] → fragment.create
```

**Migration Components:**
1. **RSS Feed Integration** (20-25 hours)
   - Implement news feed aggregation
   - Topic-based feed filtering
   - Content normalization

2. **Template-Based Digest** (15-20 hours)
   - Create digest templates
   - Automated formatting
   - Keyword extraction

3. **Optional AI Enhancement** (10-15 hours)
   - AI summarization as optional step
   - Fallback to template-only approach
   - Performance monitoring

**Benefits:**
- **Reliability:** Works without AI dependencies
- **Performance:** Faster execution with caching
- **Quality:** Consistent formatting with optional AI enhancement
- **Cost:** Reduced API costs for routine operations

---

### 3. AI News Digest (Legacy) - `news-digest-ai`
**Status:** 🗑️ DEPRECATION RECOMMENDED  
**Location:** `delegation/sprint-40/fe-code-ready-pack/fragments/commands/news-digest-ai/command.yaml`

**Analysis:**
- Legacy experimental command with complex two-stage AI processing
- Overlaps with main `news-digest` command functionality
- Low/unknown usage in delegation environment
- High complexity with marginal benefit

**Action Required:** Remove from delegation environment, consolidate functionality into main news-digest command

---

### 4. Create Todo (Delegation) - `delegation/todo`
**Status:** 🗑️ OBSOLETE  
**Location:** `delegation/sprint-40/fe-code-ready-pack/fragments/commands/todo/command.yaml`

**Analysis:**
- Legacy version of todo parsing similar to `todo-original`
- Already superseded by MIGRATE-TODO-001 implementation
- No active usage

**Action Required:** Remove from delegation environment

---

## Migration Categories & Strategy

### Direct Replacement ✅ (Completed)
**Commands:** `todo-original`, `delegation/todo`  
**Status:** Completed in MIGRATE-TODO-001  
**Approach:** Regex-based parsing with business rules

### Hybrid Approach 🔄 (Recommended)
**Commands:** `news-digest`  
**Strategy:** Template-based foundation + optional AI enhancement  
**Benefits:** Reliability with AI creativity when available

### AI-Required 🤖 (Keep but Optimize)
**Commands:** None currently active  
**Strategy:** Improve performance and reliability of AI steps

### Deprecation 🗑️ (Remove)
**Commands:** `news-digest-ai`, `delegation/todo`  
**Action:** Clean up legacy commands

---

## Migration Roadmap

### Sprint 51: News Digest Hybrid Migration
**Focus:** Implement deterministic foundation for news digest

**Tasks:**
1. **MIGRATE-NEWS-001: RSS Feed Integration** (20-25 hours)
   - Implement news feed aggregation service
   - Topic-based filtering and categorization
   - Content normalization and validation

2. **MIGRATE-NEWS-002: Template-Based Digest** (15-20 hours)
   - Create digest template system
   - Automated formatting and structure
   - Integration with existing fragment creation

**Deliverable:** Hybrid news digest command that works deterministically with optional AI enhancement

### Sprint 52: Legacy Cleanup & Optimization
**Focus:** Clean up legacy commands and optimize remaining AI usage

**Tasks:**
1. **CLEANUP-001: Deprecate Legacy Commands** (5-10 hours)
   - Remove `news-digest-ai` and `delegation/todo`
   - Update command registry
   - Migration notifications

2. **AUDIT-AI-002: AI Step Optimization** (10-15 hours)
   - Improve `AiGenerateStep` performance
   - Better error handling and fallbacks
   - Enhanced caching and monitoring

**Deliverable:** Clean command registry with optimized AI usage patterns

---

## Resource Requirements

### Development Effort
- **Total Estimated Hours:** 50-70 hours
- **Sprint 51:** 35-45 hours (news digest migration)
- **Sprint 52:** 15-25 hours (cleanup and optimization)

### Skill Requirements
- RSS/news feed processing expertise
- Template engine development
- Content aggregation APIs
- Command registry management
- AI integration patterns

### Infrastructure Needs
- News feed API access (RSS aggregators)
- Content aggregation services
- Template caching system
- Monitoring for hybrid AI/deterministic flows

---

## Technical Analysis

### AI Usage Patterns
All AI-dependent commands follow consistent patterns through `AiGenerateStep`:

```php
// Common pattern
'ai.generate' => [
    'prompt' => 'structured prompt with context',
    'expect' => 'json' | 'text',
    'max_tokens' => 300-800,
    'temperature' => 0.3
]
```

### Migration Success Factors
1. **Clear Input/Output Contracts:** AI steps have well-defined interfaces
2. **Structured Prompts:** Prompts follow consistent patterns
3. **Deterministic Alternatives:** Most logic can be replicated with rules
4. **Template Integration:** Existing template engine supports migration

### Risk Assessment
- **Low Risk:** Todo migration already proven successful
- **Medium Risk:** News digest requires external API integration
- **Low Risk:** Legacy cleanup is straightforward removal

---

## Recommendations

### Immediate Actions (Sprint 51)
1. **Proceed with news-digest hybrid migration** - highest impact, manageable effort
2. **Begin RSS feed integration** - longest lead time component
3. **Design template system** - foundation for deterministic approach

### Medium-term Actions (Sprint 52)
1. **Clean up legacy commands** - reduce maintenance burden
2. **Optimize remaining AI usage** - improve reliability and performance
3. **Document migration patterns** - support future migration efforts

### Long-term Strategy
1. **Monitor hybrid performance** - validate approach effectiveness
2. **Evaluate AI-optional patterns** - consider for future commands
3. **Enhance tooling** - build migration utilities for future use

---

## Success Metrics

### Quantitative Goals
- **AI Dependency Reduction:** From 4 to 1 active AI-dependent command
- **Performance Improvement:** <100ms for deterministic operations
- **Reliability Increase:** 99%+ uptime for core todo functionality
- **Cost Reduction:** 70% reduction in AI API calls for routine operations

### Qualitative Goals  
- **Developer Experience:** Easier command authoring with deterministic steps
- **User Experience:** Faster, more reliable command execution
- **Maintainability:** Reduced complexity and external dependencies
- **Scalability:** Foundation for visual flow builder integration

---

## Conclusion

The AI-dependent command audit reveals a positive outlook for the DSL Deterministic Foundation initiative. With todo parsing already successfully migrated, only one active command requires new migration work. The hybrid approach for news-digest provides a balanced solution that maintains AI capabilities while establishing deterministic foundations.

The estimated 50-70 hour effort spread across two sprints represents a manageable investment with significant returns in reliability, performance, and maintainability. The success of MIGRATE-TODO-001 provides a proven pattern for future migrations, and the focused scope ensures achievable deliverables.

**Recommendation: Proceed with the proposed migration roadmap.**