# Unused Models - Detailed Analysis

## Models with 0 References (Safe to Remove)

### 1. AgentVector
- **File**: `app/Models/AgentVector.php`
- **Table**: `agent_vectors`
- **Purpose**: Vector embeddings storage for agent data
- **Status**: Table exists but model never used in code
- **Fields**: agent_id, embedding (vector), metadata, created_at, updated_at
- **Assessment**: Appears to be for RAG/semantic search but never implemented
- **Migration**: Search for `create_agent_vectors_table`

### 2. ArticleFragment
- **File**: `app/Models/ArticleFragment.php`
- **Table**: Unknown (model is empty stub)
- **Purpose**: Likely intended to link articles with fragments
- **Status**: Empty model, no implementation
- **Assessment**: Safe to remove - no table, no code
- **Migration**: Likely none

### 3. CalendarEvent
- **File**: `app/Models/CalendarEvent.php`
- **Table**: Unknown (model is empty stub)
- **Purpose**: Calendar/scheduling feature
- **Status**: Empty model, no implementation
- **Assessment**: Feature never started
- **Migration**: Likely none

### 4. FileText
- **File**: `app/Models/FileText.php`
- **Table**: Unknown (model is empty stub)
- **Purpose**: Extracted text content from files
- **Status**: Empty model, no implementation
- **Assessment**: Safe to remove - possibly replaced by Fragment system
- **Migration**: Likely none

### 5. FragmentTag
- **File**: `app/Models/FragmentTag.php`
- **Table**: `fragment_tag` (pivot)
- **Purpose**: Many-to-many relationship between fragments and tags
- **Status**: Pivot table exists but never queried
- **Assessment**: Tagging system exists but this explicit model never used
- **Migration**: Search for `create_fragment_tag_table`
- **Note**: Fragment model likely uses direct relationship without this model

### 6. ObjectType
- **File**: `app/Models/ObjectType.php`
- **Table**: Unknown (model is empty stub)
- **Purpose**: Type definitions for polymorphic relations
- **Status**: Empty model, no implementation
- **Assessment**: Safe to remove
- **Migration**: Likely none

### 7. PromptEntry
- **File**: `app/Models/PromptEntry.php`
- **Table**: `prompt_registry`
- **Purpose**: Store reusable prompt templates
- **Status**: Table exists, model never used
- **Fields**: name, slug, prompt_text, variables, category, is_active
- **Assessment**: Prompt management feature never completed
- **Migration**: Search for `create_prompt_registry_table`

### 8. Thumbnail
- **File**: `app/Models/Thumbnail.php`
- **Table**: Unknown (model is empty stub)
- **Purpose**: Image thumbnail generation/storage
- **Status**: Empty model, no implementation
- **Assessment**: Safe to remove
- **Migration**: Likely none

### 9. WorkItemEvent
- **File**: `app/Models/WorkItemEvent.php`
- **Table**: `work_item_events`
- **Purpose**: Event sourcing for work items
- **Status**: Table exists but never queried
- **Fields**: work_item_id, event_type, event_data, user_id, created_at
- **Assessment**: Event tracking feature never implemented
- **Migration**: Search for `create_work_item_events_table`

## Migration Search Strategy

```bash
# Find migrations for these tables
grep -r "agent_vectors" database/migrations/
grep -r "fragment_tag" database/migrations/
grep -r "prompt_registry" database/migrations/
grep -r "work_item_events" database/migrations/
```

## Removal Checklist

For each model:
- [ ] Confirm 0 references in codebase
- [ ] Find migration file(s)
- [ ] Check if table has any data (production concern)
- [ ] Move model to backup/models/
- [ ] Move migration to backup/migrations/
- [ ] Document in git commit
- [ ] Run tests
