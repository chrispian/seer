# DTO Migration Tracker

## Overview

Migrate all models to use DTOs for consistent, type-safe data transfer between layers.

**Pattern**: Database → Model → DTO → Controller → API Response

## Status

### ✅ Completed
- `ProviderDTO` - Created (not yet integrated)
- Documentation created

### 🔄 In Progress
None

### ⏳ Pending (Do as we encounter them)

#### Core AI Models
- [ ] `AiProviderDTO` - Rename from ProviderDTO, integrate into service
- [ ] `AiModelDTO` - AI model configurations
- [ ] `AiCredentialDTO` - API credentials

#### Orchestration
- [ ] `OrchestrationTaskDTO` - Tasks
- [ ] `OrchestrationSprintDTO` - Sprints  
- [ ] `OrchestrationSessionDTO` - Sessions

#### UI Builder
- [ ] `PageDTO` - Page configurations
- [ ] `ComponentDTO` - Component definitions
- [ ] `DatasourceDTO` - Data source configs
- [ ] `ActionDTO` - Action definitions

#### Agents
- [ ] `AgentDTO` - Agent configurations
- [ ] `AgentProfileDTO` - Agent profiles

#### Fragments & Content
- [ ] `FragmentDTO` - Content fragments
- [ ] `DocumentationDTO` - Documentation entries
- [ ] `BookmarkDTO` - Bookmarks

#### Chat & Sessions
- [ ] `ChatSessionDTO` - Chat sessions
- [ ] `ChatMessageDTO` - Individual messages

## Migration Priority

**When to create a DTO:**
1. When you're working on a feature using that model
2. When you encounter array/object access inconsistencies
3. When API responses need standardization
4. When type safety would prevent bugs

**Don't block on DTOs:**
- Don't refactor everything at once
- Create them as needed
- It's okay to mix old/new approaches temporarily

## Template

When creating a new DTO, use this template:

```php
<?php

namespace App\DTOs;

use App\Models\YourModel;

/**
 * YourModel Data Transfer Object
 * 
 * Brief description of what this represents.
 */
class YourModelDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        // ... all public properties with types
    ) {}

    /**
     * Create DTO from model
     */
    public static function fromModel(YourModel $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            // ... map all properties
        );
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // ... all properties
        ];
    }
}
```

## Benefits Recap

- ✅ Type safety (no more array access errors)
- ✅ IDE autocomplete
- ✅ Single source of truth
- ✅ Easy to refactor (change once, IDE finds all usages)
- ✅ Self-documenting code
- ✅ Testability

## Notes

- Keep DTOs in `app/DTOs/`
- Use readonly properties (PHP 8.1+)
- Always include `fromModel()` factory method
- Always include `toArray()` for API responses
- Document what the DTO represents
- Keep DTOs simple - just data, no business logic

## Next Action

When you touch a model's code:
1. Check if DTO exists
2. If not, consider creating one
3. Update service to return DTO
4. Update controller to use DTO properties
5. Update this tracker
