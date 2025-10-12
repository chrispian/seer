# ETL Import System Improvements Project

## Executive Summary
This project aims to modernize and unify our data import infrastructure, focusing on creating a scalable, maintainable ETL system that can handle diverse data sources while extracting maximum value from imported content.

## Project Goals
1. **Unify Import Architecture**: Create standardized contracts and adapters for all import types
2. **Maximize Data Value**: Extract actionable telemetry and insights from imported data
3. **Simplify Operations**: Reduce code duplication and maintenance burden
4. **Enable User Uploads**: Implement proper CAS system for media/artifact management

## Current State Analysis

### Existing Import Systems
1. **Agent Log Imports** (OpenCode, Claude, Codex)
   - Line-by-line parsing with checksum deduplication
   - Stores in `agent_logs` table
   - Limited telemetry extraction

2. **Third-Party API Imports**
   - Hardcover (books) - Rate limited, Fragment-based
   - Readwise (highlights) - Cursor pagination, Fragment-based
   - Each with custom implementation

3. **Local File Imports**
   - ChatGPT conversations (JSON)
   - Documentation (Markdown)
   - Delegation content (mixed formats)

4. **Storage Systems**
   - Fragments table (primary storage)
   - Documentation table (specialized)
   - Basic artifacts table (underutilized)

### Key Issues Identified
- **Code Duplication**: Similar patterns repeated across services
- **No Unified Interface**: Each import has unique implementation
- **Limited Telemetry**: Missing valuable insights from agent logs
- **No CAS Implementation**: Artifact system lacks deduplication
- **Inconsistent Error Handling**: Rate limiting, retries vary
- **Mixed Storage Strategies**: Fragments vs specialized tables

## Proposed Architecture

### Core Components
1. **Import Contract System** (Area 1)
   - Standardized interfaces
   - Media adapters for books/movies
   - Unified error handling

2. **Document Sync Pipeline** (Area 2)
   - Version-preserving sync
   - Intelligent content routing
   - Relationship mapping

3. **CAS/Artifact System** (Area 3)
   - SHA256-based deduplication
   - User upload handling
   - Media file management

4. **Telemetry Extraction** (Area 4)
   - Agent performance metrics
   - Tool usage analysis
   - Error pattern detection

### Simplification Initiatives
1. **Fragment Consolidation**: Standardize on Fragments as base storage
2. **Rate Limiting Trait**: Shared functionality across imports
3. **Checksum Service**: Unified deduplication logic
4. **Metrics Interface**: Common stats/reporting structure

## Implementation Strategy

### Phase 1: Foundation (Sprint 1-2)
- Create base contracts and interfaces
- Implement MediaImportAdapter pattern
- Build Letterboxd adapter alongside Hardcover migration

### Phase 2: Document Management (Sprint 3-4)
- Implement docs folder sync
- Build delegation content router
- Create version preservation system

### Phase 3: Storage Enhancement (Sprint 5-6)
- Implement CAS deduplication
- Build user upload pipeline
- Migrate existing artifacts

### Phase 4: Intelligence Layer (Sprint 7-8)
- Build telemetry extraction pipeline
- Create analytics dashboards
- Implement performance monitoring

## Success Metrics
- 50% reduction in import service code
- 100% of imports using unified interfaces
- Zero data loss during sync operations
- <5 minute onboarding for new import sources
- Actionable telemetry from 100% of agent sessions

## Risk Mitigation
- **Data Loss**: Implement comprehensive backup before migration
- **Performance**: Benchmark current vs new system
- **Compatibility**: Maintain backward compatibility during transition
- **Rate Limits**: Implement circuit breakers and backoff

## Team Requirements
- Senior Backend Engineer (lead)
- ETL Specialist (architecture)
- Database Administrator (optimization)
- QA Engineer (data integrity testing)

## Dependencies
- Laravel 12 queue system
- PostgreSQL with JSONB support
- Redis for rate limiting
- S3-compatible storage for artifacts

## Timeline Estimate
- Total Duration: 8-10 weeks
- Phase 1: 2 weeks
- Phase 2: 2 weeks  
- Phase 3: 3 weeks
- Phase 4: 2 weeks
- Testing/Refinement: 1 week

## Next Steps
1. Review and approve project plan
2. Break down into detailed tasks per area
3. Create sprints in orchestration system
4. Begin Phase 1 implementation