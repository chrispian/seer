# Documentation Knowledge Base System

**Task**: T-ORCH-013  
**Sprint**: SPRINT-ORCH-CONTEXT  
**Status**: Planned  
**Estimated**: 6 hours

## Overview

Import 61 documentation files (~14,259 lines, avg 233 lines/file) from `docs/` folder into database with multi-dimensional organization for efficient agent and developer research.

**Current State**: Markdown files in `docs/` folder  
**Target State**: Searchable, tagged, versioned database records

---

## Organization Strategy

### Multi-Dimensional Taxonomy

#### 1. Namespace/Path (File Structure)
Preserves original organization, enables hierarchical browsing.

| Namespace | Purpose |
|-----------|---------|
| `orchestration` | Task orchestration, agents, sprints |
| `ingestion` | Obsidian, Readwise, ChatGPT imports |
| `pipeline` | Fragment processing pipeline |
| `mcp-servers` | MCP server implementations |
| `laravel-tool-crate` | Tool crate documentation |
| `root` | Top-level guides (CONTEXT.md, etc.) |

**Query Example**: "All orchestration docs"
```sql
SELECT * FROM documentation WHERE namespace = 'orchestration';
```

#### 2. Subsystem (Functional Grouping)
Cross-cutting system boundaries.

**Subsystems**:
- `orchestration` - Task/agent/sprint management
- `ingestion` - External data imports
- `pipeline` - Fragment processing
- `commands` - Slash command system
- `fragments` - Fragment storage/retrieval
- `ai` - AI providers, embeddings, classification
- `ui` - Frontend, Flux, React components
- `infrastructure` - Database, queue, cache, deployment
- `testing` - Test infrastructure, patterns

**Query Example**: "All command system docs"
```sql
SELECT * FROM documentation WHERE subsystem = 'commands';
```

#### 3. Purpose/Type (Document Role)
What the document does.

**Types**:
- `guide` - How-to, tutorials, walkthroughs
- `reference` - API docs, configuration, specifications
- `architecture` - System design, ADRs, diagrams
- `troubleshooting` - Problem diagnosis, solutions
- `migration` - Upgrade guides, breaking changes
- `plan` - Feature plans, sprint plans
- `context` - Background, history, decisions

**Query Example**: "All troubleshooting guides"
```sql
SELECT * FROM documentation WHERE purpose = 'troubleshooting';
```

#### 4. Semantic Tags (Content Discovery)

##### Special Research Tags

**Agent Safety & Awareness**:
- `#danger` - Tricky/confusing areas that trip agents and people
- `#gotcha` - Non-obvious behaviors, edge cases
- `#breaking-change` - API/behavior changes requiring migration
- `#deprecated` - Obsolete patterns to avoid

**Problem Solving**:
- `#solution` - Fixes for specific issues
- `#common-issue` - Known recurring problems
- `#workaround` - Temporary fixes pending proper solution
- `#bug` - Known bugs not yet fixed

**Quality & Status**:
- `#incomplete` - Documentation in progress
- `#outdated` - Needs updating
- `#verified` - Tested and confirmed accurate
- `#reviewed` - Recently reviewed for accuracy

##### Domain Tags

**Systems**:
- `#slash-commands`, `#yaml-dsl`, `#php-commands`
- `#postmaster`, `#artifacts`, `#messaging`
- `#task-activities`, `#work-items`, `#assignments`
- `#obsidian`, `#readwise`, `#chatgpt`
- `#vector-search`, `#embeddings`, `#fulltext`

**Technical**:
- `#migrations`, `#seeders`, `#testing`
- `#queue`, `#horizon`, `#jobs`
- `#livewire`, `#flux`, `#react`
- `#postgresql`, `#sqlite`, `#redis`

**Features**:
- `#wikilinks`, `#bidirectional-sync`, `#conflict-detection`
- `#deterministic-pipeline`, `#type-inference`
- `#activity-logging`, `#audit-trail`

---

## Database Schema

```sql
CREATE TABLE documentation (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    
    -- Core content
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    
    -- File metadata
    file_path VARCHAR(500) UNIQUE,
    namespace VARCHAR(100),
    file_hash VARCHAR(64),
    
    -- Organization
    subsystem VARCHAR(100),
    purpose VARCHAR(50),
    tags JSONB DEFAULT '[]',
    
    -- Relationships
    related_docs JSONB DEFAULT '[]',
    related_code_paths JSONB DEFAULT '[]',
    
    -- Search optimization
    content_vector VECTOR(1536),
    search_vector TSVECTOR GENERATED ALWAYS AS (
        to_tsvector('english', title || ' ' || content)
    ) STORED,
    
    -- Versioning
    version INT DEFAULT 1,
    last_modified TIMESTAMP,
    git_hash VARCHAR(40),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_docs_namespace ON documentation(namespace);
CREATE INDEX idx_docs_subsystem ON documentation(subsystem);
CREATE INDEX idx_docs_purpose ON documentation(purpose);
CREATE INDEX idx_docs_tags ON documentation USING GIN(tags);
CREATE INDEX idx_docs_search ON documentation USING GIN(search_vector);
CREATE INDEX idx_docs_vector ON documentation USING ivfflat(content_vector vector_cosine_ops);
CREATE INDEX idx_docs_file_path ON documentation(file_path);
```

---

## Agent Research Workflows

### Workflow 1: Pre-Task Research

**Scenario**: Agent assigned task to work on slash commands system

**Query**:
```sql
SELECT 
    title,
    purpose,
    tags,
    excerpt
FROM documentation
WHERE subsystem = 'commands'
   OR tags @> '["#slash-commands"]'::jsonb
ORDER BY 
  CASE 
    WHEN tags @> '["#danger"]'::jsonb THEN 1
    WHEN tags @> '["#gotcha"]'::jsonb THEN 2
    WHEN tags @> '["#solution"]'::jsonb THEN 3
    ELSE 4
  END,
  last_modified DESC;
```

**Result**: Agent sees danger areas first, then gotchas, then solutions, then general docs.

### Workflow 2: Finding Solutions

**Scenario**: Agent encounters common database migration issue

**Query**:
```sql
SELECT 
    title,
    content,
    tags,
    related_code_paths
FROM documentation
WHERE tags @> '["#solution"]'::jsonb
  AND tags @> '["#migrations"]'::jsonb
ORDER BY 
  (tags @> '["#common-issue"]'::jsonb)::int DESC,
  last_modified DESC
LIMIT 10;
```

**Result**: Solutions for common migration issues, newest first.

### Workflow 3: Identifying Refactor Targets

**Scenario**: PM wants to find problem areas for future sprints

**Query**:
```sql
SELECT 
  subsystem,
  COUNT(*) as issue_count,
  jsonb_agg(jsonb_build_object(
    'title', title,
    'tags', tags
  )) as problematic_docs
FROM documentation
WHERE tags @> '["#common-issue"]'::jsonb
   OR tags @> '["#danger"]'::jsonb
GROUP BY subsystem
ORDER BY issue_count DESC;
```

**Result**: Subsystems with most documented issues → refactor priorities.

### Workflow 4: Semantic Search

**Scenario**: Agent needs contextual information about "conflict detection"

**Query**:
```sql
SELECT 
    title,
    namespace,
    subsystem,
    tags,
    1 - (content_vector <=> query_embedding) as similarity
FROM documentation
WHERE 1 - (content_vector <=> query_embedding) > 0.7
ORDER BY similarity DESC
LIMIT 10;
```

**Result**: Semantically similar docs regardless of exact keyword matches.

---

## Tag Extraction Rules

### Automatic Tag Detection

**Danger Detection**:
- Headings containing: "Warning", "Caution", "Important", "Danger"
- Paragraphs starting with: "⚠️", "WARNING:", "IMPORTANT:"
- Content patterns: "tricky", "confusing", "gotcha", "edge case"

**Solution Detection**:
- Headings: "Solution", "Fix", "Resolution", "Workaround"
- Sections: "Troubleshooting", "Common Issues", "FAQ"
- Content: "To fix...", "The solution is...", "Resolved by..."

**Common Issue Detection**:
- Headings: "Common Problems", "Known Issues", "Frequently Encountered"
- Content: "This is a known issue", "Many users encounter..."

**Subsystem Detection**:
- Path-based: `docs/orchestration/*.md` → subsystem: orchestration
- Content-based: Mentions of "TaskOrchestrationService" → subsystem: orchestration
- Keyword matching: "slash command" → subsystem: commands

### Manual Tag Format

Markdown frontmatter:
```yaml
---
title: "Postmaster & Agent INIT System"
namespace: orchestration
subsystem: orchestration
purpose: architecture
tags:
  - "#postmaster"
  - "#artifacts"
  - "#messaging"
  - "#danger"
related:
  - "docs/orchestration/task-context-and-activity-logging.md"
code_paths:
  - "app/Jobs/Postmaster/ProcessParcel.php"
  - "app/Services/Orchestration/Artifacts/ContentStore.php"
---
```

---

## Import Process

### Phase 1: Discovery & Parsing
1. Scan `docs/` directory recursively
2. Parse frontmatter (if exists)
3. Extract markdown content
4. Compute file hash

### Phase 2: Classification
1. **Namespace**: From file path
2. **Subsystem**: From path + content analysis
3. **Purpose**: From filename + heading analysis
4. **Tags**: Auto-detect + frontmatter merge

### Phase 3: Enhancement
1. Extract code references
2. Find cross-document links
3. Generate embeddings (if enabled)
4. Create excerpt (first paragraph or summary)

### Phase 4: Storage
1. Upsert to `documentation` table
2. Log import activity
3. Update search indexes

### Phase 5: Validation
1. Check for broken cross-references
2. Verify code paths exist
3. Flag outdated content (last modified >6 months)

---

## API Endpoints

### Search Documentation
```http
GET /api/documentation/search?q=slash+commands&subsystem=commands&tags[]=danger
```

**Response**:
```json
{
  "data": [
    {
      "id": "doc-uuid",
      "title": "Command System Fix Task",
      "namespace": "root",
      "subsystem": "commands",
      "purpose": "troubleshooting",
      "tags": ["#slash-commands", "#danger", "#yaml-dsl"],
      "excerpt": "After debugging the sprints command, we discovered...",
      "file_path": "docs/TASK_COMMAND.md",
      "relevance": 0.95
    }
  ],
  "meta": {
    "total": 5,
    "query": "slash commands",
    "filters": {"subsystem": "commands", "tags": ["danger"]}
  }
}
```

### Get Document
```http
GET /api/documentation/{id}
```

### Update Tags
```http
PUT /api/documentation/{id}/tags
Content-Type: application/json

{
  "tags": ["#danger", "#slash-commands", "#solution"]
}
```

---

## MCP Tools

### `documentation:search`
Search documentation with filters

**Arguments**:
```json
{
  "query": "obsidian wikilinks",
  "subsystem": "ingestion",
  "tags": ["danger", "solution"],
  "limit": 10
}
```

### `documentation:get`
Retrieve full document

**Arguments**:
```json
{
  "id": "doc-uuid"
}
```

### `documentation:tag`
Add tags to document

**Arguments**:
```json
{
  "id": "doc-uuid",
  "tags": ["#verified", "#reviewed"]
}
```

### `documentation:related`
Find related documents

**Arguments**:
```json
{
  "id": "doc-uuid",
  "max_results": 5
}
```

---

## Maintenance Workflow

### Regular Review
1. **Monthly**: Flag docs last modified >3 months ago for review
2. **Quarterly**: Audit `#danger` tags - still relevant?
3. **Sprint End**: Add `#solution` tags for issues resolved

### Quality Checks
```sql
-- Find incomplete docs
SELECT title, file_path 
FROM documentation 
WHERE tags @> '["#incomplete"]'::jsonb
ORDER BY last_modified DESC;

-- Find outdated docs (not modified in 6 months)
SELECT title, subsystem, last_modified
FROM documentation
WHERE last_modified < NOW() - INTERVAL '6 months'
  AND tags @> '[]'::jsonb  -- No #verified tag
ORDER BY last_modified ASC;

-- Docs needing tags
SELECT title, file_path
FROM documentation
WHERE jsonb_array_length(tags) = 0
ORDER BY created_at DESC;
```

### Refactor Priority Report
```sql
SELECT 
  subsystem,
  COUNT(*) FILTER (WHERE tags @> '["#common-issue"]'::jsonb) as common_issues,
  COUNT(*) FILTER (WHERE tags @> '["#danger"]'::jsonb) as danger_areas,
  COUNT(*) FILTER (WHERE tags @> '["#workaround"]'::jsonb) as workarounds,
  COUNT(*) as total_docs
FROM documentation
GROUP BY subsystem
ORDER BY common_issues DESC, danger_areas DESC;
```

**Output**: Prioritized list of subsystems needing attention.

---

## Benefits

1. **Faster Agent Research** - Tag-based discovery finds relevant docs in seconds
2. **Danger Awareness** - Agents see tricky areas before starting work
3. **Solution Database** - Common issues → documented solutions
4. **Refactor Targeting** - Data-driven decisions on what to improve
5. **Version Control** - Track doc changes, detect drift
6. **Semantic Search** - Find docs by meaning, not just keywords
7. **Cross-References** - Navigate related docs and code easily

---

## Example Tag Applications

### docs/TASK_COMMAND.md
```yaml
tags:
  - "#slash-commands"
  - "#danger"
  - "#yaml-dsl"
  - "#php-commands"
  - "#common-issue"
  - "#solution"
```

**Rationale**: Complex YAML system was problematic, document explains issues and solution.

### docs/orchestration/postmaster-and-init.md
```yaml
tags:
  - "#postmaster"
  - "#artifacts"
  - "#messaging"
  - "#architecture"
  - "#verified"
```

**Rationale**: Architectural doc, recently implemented and verified working.

### docs/OBSIDIAN_IMPORT.md
```yaml
tags:
  - "#obsidian"
  - "#wikilinks"
  - "#danger"
  - "#incomplete"
```

**Rationale**: Wikilink parsing has gotchas, doc needs completion for Phase 2 features.

---

## Implementation Checklist

- [ ] Create `documentation` table migration
- [ ] Create `Documentation` model with relationships
- [ ] Create `DocumentationImportService`
- [ ] Implement tag extraction rules
- [ ] Parse all 61 .md files
- [ ] Generate embeddings (if enabled)
- [ ] Create search API endpoints
- [ ] Create MCP tools
- [ ] Add frontmatter to existing docs
- [ ] Create maintenance queries/commands
- [ ] Update agent workflows to use doc search
- [ ] Create refactor priority dashboard

**Estimated**: 6 hours  
**Dependencies**: Task activity logging system (for import activity tracking)
