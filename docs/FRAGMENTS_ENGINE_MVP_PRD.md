# Fragments Engine MVP Product Requirements Document

## Executive Summary

**Fragments Engine** is an evolution of the current SEER system into a block-level atomicity knowledge management platform. It transforms traditional note-taking into a queryable, AI-augmented knowledge substrate where documents are dynamic projections of atomic fragments rather than static files.

## Product Vision

Create a knowledge management system where:
- **Fragments** (blocks) are the atomic storage unit
- **Documents** are virtual views/queries over fragment collections  
- **AI** provides intelligent classification, tagging, and recall
- **Templates** ensure consistent rendering across document types
- **CLI-first** ergonomics maintain frictionless capture

## Core Architecture Principles

### 1. Block-Level Atomicity
- Each fragment has unique ID and standalone addressability
- Fragments can be reused across multiple documents without duplication
- Documents are queries/views over fragments, not storage containers

### 2. Document-as-View Model  
- Documents = named queries + rendering templates
- Dynamic composition based on filters, similarity, time, tags
- Manual ordering supported through join tables

### 3. AI-Native Design
- Block-sized embeddings for precise recall
- Smart classification without user burden
- Template-driven consistent formatting
- Confidence scoring and human override

## MVP Feature Set

### Core Fragment Management

**Fragment Schema**
```json
{
  "id": "uuid",
  "type": "text|todo|draft|contact|bookmark|meeting_note|code_snippet|seed",
  "content": "string",
  "tags": ["array"],
  "state": {
    "status": "open|done|archived",
    "due": "date",
    "priority": 1-5,
    "assignees": ["array"]
  },
  "relations": {
    "parent": "uuid",
    "children": ["array"],
    "linked_blocks": ["array"]
  },
  "metadata": {
    "created_at": "timestamp",
    "modified_at": "timestamp", 
    "author": "string",
    "classifier": "confidence_data"
  }
}
```

### Fragment Query Language (FQL)

**Syntax**
```sql
VIEW <name>
FROM blocks
[WHERE <predicates>]
[SIMILAR TO "<query>" [TOPK <n>]]
[ORDER BY <field> [ASC|DESC]]
[LIMIT <n>]
[INCLUDE <other_views>]
[TEMPLATE <template_name>]
[OUTPUT AS markdown|html|json]
```

**Example Queries**
```sql
-- Project overview
VIEW ProjectBrief_FragmentsEngine  
FROM blocks
WHERE tags CONTAINS "fragments-engine" 
  AND state.status != "archived"
ORDER BY priority DESC, modified_at DESC
TEMPLATE "brief.v1.json"

-- AI-powered recall
VIEW Related_Context
FROM blocks  
SIMILAR TO "block-level atomicity and document projection"
TOPK 20
EMBEDDING "text-embedding-3-large"
TEMPLATE "context-pack.v1.json"
```

### Intelligent Classification System

**Multi-Stage Pipeline**
1. **Inline Metadata Extraction** (deterministic)
   - Tags: `#scifi #writing`
   - Categories: `!Builders World`  
   - People: `@ada @team/ops`
   - Dates: `^2025-09-01, due:friday, in 2d`
   - Priority: `p1, prio:high, !!`
   - TODO markers: `- [ ], @todo, Next:`

2. **Heuristic Classifiers** (rule-based)
   - TODO: checkbox patterns, imperative verbs + time
   - CONTACT: email + phone patterns, contact keywords
   - BOOKMARK: URL + minimal prose, "save later" keywords
   - DRAFT: multiple headings, paragraphs, outline structure
   - MEETING: timestamps + attendees + action items

3. **Prototype Similarity** (embedding-based)
   - Maintain exemplar fragments per type/tag
   - k-NN classification with confidence scoring
   - Self-improving through user corrections

4. **AI Model Classification** (constrained)
   - JSON schema-bound outputs
   - Confidence thresholds
   - Abstention for uncertainty

### Template System

**JSON Schema-Driven Templates**
```json
{
  "template_type": "contact",
  "version": "1.0.0", 
  "output": "markdown",
  "sections": [
    {
      "id": "header",
      "render": "h1",
      "source": {"type": "field", "path": "contact.name"}
    },
    {
      "id": "meta", 
      "render": "table",
      "rows": [
        ["Email", {"type": "field", "path": "contact.email"}],
        ["Phone", {"type": "field", "path": "contact.phone"}]
      ]
    }
  ],
  "ai_guidance": {
    "system": "You are a formatter. Preserve facts; do not invent.",
    "rules": ["Never change contact formats", "Omit missing fields"]
  },
  "mapping": {
    "contact.name": ["state.title", "metadata.name"],
    "contact.email": ["state.email", "metadata.contact.email"]
  }
}
```

## Technical Implementation

### Database Schema (PostgreSQL + pgvector)
- **blocks** table with vector embeddings
- **views** table for saved queries  
- **view_blocks** join table for manual ordering
- JSON columns for flexible metadata
- Vector indices for similarity search

### API Endpoints
- `POST /api/fragment` - Create fragment
- `GET /api/recall` - List fragments with filters
- `GET /api/search` - Text and vector search
- `POST /api/view` - Execute FQL query
- `GET /api/view/{name}` - Render saved view

### CLI Commands (preserved from SEER)
- `fragment <type> "<message>" <category> <tags>`
- `flist [type] [limit]` - List recent fragments
- `fsearch <term>` - Search fragments
- **New:** `fview <name>` - Execute saved view

## Current Implementation Status

**Existing (SEER)**
- ✅ Basic fragment CRUD via Laravel/Filament
- ✅ CLI functions for capture/search/recall
- ✅ AI enrichment pipeline (OpenAI integration)
- ✅ Real-time chat interface with slash commands
- ✅ Category system and basic tagging

**MVP Requirements**
- [ ] FQL parser and execution engine
- [ ] Template system with JSON schema validation
- [ ] Multi-stage classification pipeline
- [ ] Vector embeddings and similarity search
- [ ] Document-as-view rendering
- [ ] Enhanced CLI with view support

## Success Metrics

### Technical Metrics
- Classification accuracy >85% across fragment types
- Query response time <200ms for typical views
- Template rendering consistency 100%
- Vector search recall@10 >90%

### User Experience Metrics  
- Time-to-capture <10 seconds via CLI
- Classification override rate <15%
- View creation success rate >80%
- User retention week-over-week +10%

## MVP Delivery Timeline

**Phase 1 (Weeks 1-2): Core Infrastructure**
- Database schema migration with pgvector
- FQL parser implementation
- Basic view execution engine

**Phase 2 (Weeks 3-4): Classification System**
- Inline metadata extraction
- Heuristic classifiers for major types
- Prototype similarity matching

**Phase 3 (Weeks 5-6): Template System**
- JSON schema validation
- Template rendering engine
- Core template library (note, todo, contact, draft)

**Phase 4 (Weeks 7-8): Integration & Polish** 
- Enhanced CLI commands
- Classification confidence UI
- Performance optimization
- User acceptance testing

## Post-MVP Roadmap

- **Advanced FQL Features**: GROUP BY, complex joins, subqueries
- **Template Marketplace**: Community template sharing
- **Mobile Apps**: iOS/Android fragment capture
- **Integrations**: Obsidian sync, email ingestion, calendar hooks
- **Advanced AI**: Custom models, multilingual support
- **Collaboration**: Shared vaults, team permissions

## Risk Mitigation

**Technical Risks**
- Vector search performance → Use IVFFlat indices, query optimization
- FQL complexity → Start minimal, iterate based on user needs
- AI classification drift → Regular retraining, human feedback loops

**Product Risks**  
- CLI-first adoption → Maintain backward compatibility with SEER
- Template complexity → Start with proven patterns, user testing
- Fragment overload → Smart defaults, bulk operations