# UI-00: Terminology Migration - "Agents" to "Agent Profiles"

**Task Code**: `UI-00`  
**Sprint**: Sprint 64 - Agent Management Dashboard UI  
**Priority**: HIGH  
**Status**: `todo`  
**Estimated**: 1-2 hours  
**Dependencies**: None (should be done FIRST, before other tasks)

## Objective

Migrate all references from "Agents" to "Agent Profiles" throughout the codebase to clarify that we're managing agent profile configurations, not active agent instances. This creates clear terminology before building the new dashboard UI.

## Rationale

**Current Confusion:**
- "Agents" could mean active running agents OR agent profile configurations
- `AgentProfile` model exists but UI/commands call them "Agents"
- Need clear distinction: "Agent Profiles" = configurations, "Agents" = runtime instances

**After Migration:**
- `/agent-profiles` - Manage agent profile configurations (what we're building)
- Future: `/agents` could be for runtime agent monitoring/status
- Clear, consistent terminology across codebase

## Scope of Changes

### 1. Slash Commands

**Current:**
- `/agents` → Lists agent profiles
- `/agent-list` (alias)
- `/al` (alias)
- `/agent-detail [slug]` → Show agent profile details
- `/ad` (alias)

**New:**
- `/agent-profiles` → Lists agent profiles (primary)
- `/ap` (alias, short)
- `/agent-profile-detail [slug]` → Show profile details
- `/apd` (alias)

**Keep Deprecated (for backward compatibility):**
- `/agents` → Redirect to `/agent-profiles` with deprecation notice
- `/agent-list` → Same
- `/agent-detail` → Same

### 2. Command Classes

**Files to Rename:**
- `app/Commands/AgentListCommand.php` → `app/Commands/AgentProfileListCommand.php`
- `app/Commands/AgentDetailCommand.php` → `app/Commands/AgentProfileDetailCommand.php`

**Class Names:**
- `AgentListCommand` → `AgentProfileListCommand`
- `AgentDetailCommand` → `AgentProfileDetailCommand`

### 3. React Components

**Files to Rename:**
- `resources/js/components/orchestration/AgentListModal.tsx` → `resources/js/components/orchestration/AgentProfileListModal.tsx`

**Component/Interface Names:**
- `AgentListModal` → `AgentProfileListModal`
- `AgentListModalProps` → `AgentProfileListModalProps`
- `Agent` interface → Keep as `AgentProfile` (or create new if doesn't exist)

### 4. Command Registry

**File:** `app/Services/CommandRegistry.php`

**Update mappings:**
```php
// New primary commands
'agent-profiles' => AgentProfileListCommand::class,
'ap' => AgentProfileListCommand::class,
'agent-profile-list' => AgentProfileListCommand::class,
'agent-profile-detail' => AgentProfileDetailCommand::class,
'apd' => AgentProfileDetailCommand::class,

// Deprecated aliases (with deprecation notice in handler)
'agents' => AgentProfileListCommand::class,
'agent-list' => AgentProfileListCommand::class,
'al' => AgentProfileListCommand::class,
'agent-detail' => AgentProfileDetailCommand::class,
'ad' => AgentProfileDetailCommand::class,
```

### 5. YAML Command Definition

**File:** `fragments/commands/agents/command.yaml`

**Rename to:** `fragments/commands/agent-profiles/command.yaml`

**Update content:**
```yaml
name: "Agent Profile List"
slug: agent-profiles
triggers:
  slash: "/agent-profiles"
# ... rest of content
```

### 6. Modal Routing

**File:** `resources/js/islands/chat/CommandResultModal.tsx`

**Update:**
```typescript
// Change from:
case 'AgentListModal':
  return <AgentListModal ... />

// To:
case 'AgentProfileListModal':
  return <AgentProfileListModal ... />

// Keep backward compatibility:
case 'AgentListModal': // Deprecated
  return <AgentProfileListModal ... />
```

### 7. User-Facing Text

**Update all user-facing strings:**
- "Agent List" → "Agent Profiles"
- "List all agents" → "Manage agent profiles"
- "Show agent details" → "Show agent profile details"
- "No agents found" → "No agent profiles found"
- "Agent Management" → "Agent Profile Management"

**Command descriptions:**
- `/agent-profiles` - "Manage agent profile configurations"
- `/agent-profile-detail` - "Show detailed agent profile information"

### 8. Help Command

**File:** `app/Commands/HelpCommand.php` (if exists)

Update help text to show new commands as primary.

### 9. Routing Command

**File:** `app/Commands/RoutingCommand.php`

Update route display:
```php
'/agent-profiles' => 'Manage agent profiles',
```

## Implementation Checklist

### Phase 1: Backend Command Classes (30 min)
- [ ] Rename `AgentListCommand.php` → `AgentProfileListCommand.php`
- [ ] Update class name inside file
- [ ] Update `getName()` to return "Agent Profile List"
- [ ] Update `getDescription()` to describe profiles
- [ ] Update `getUsage()` to show `/agent-profiles`
- [ ] Update component reference: `'component' => 'AgentProfileListModal'`
- [ ] Rename `AgentDetailCommand.php` → `AgentProfileDetailCommand.php`
- [ ] Update class name, methods, and text inside

### Phase 2: Frontend Components (20 min)
- [ ] Rename `AgentListModal.tsx` → `AgentProfileListModal.tsx`
- [ ] Update export: `export function AgentProfileListModal`
- [ ] Update interface: `AgentProfileListModalProps`
- [ ] Update title prop: `title="Agent Profile Management"`
- [ ] Update empty state: "No agent profiles found"
- [ ] Update search placeholder: "Search agent profiles..."
- [ ] Update description: "Manage AI agent profiles..."

### Phase 3: Modal Routing (10 min)
- [ ] Update import in `CommandResultModal.tsx`
- [ ] Add case for `'AgentProfileListModal'`
- [ ] Keep backward compatibility case for `'AgentListModal'`
- [ ] Update console.log messages

### Phase 4: Command Registry (15 min)
- [ ] Update `CommandRegistry.php` with new mappings
- [ ] Add primary commands (`/agent-profiles`, `/ap`, etc.)
- [ ] Keep deprecated aliases pointing to new commands
- [ ] Add comments marking deprecated commands

### Phase 5: YAML Command Definition (10 min)
- [ ] Rename directory: `agents/` → `agent-profiles/`
- [ ] Update `command.yaml` name and slug
- [ ] Update trigger to `/agent-profiles`
- [ ] Update response messages to say "agent profiles"

### Phase 6: Deprecation Notices (15 min)
- [ ] Add deprecation notice to old command handlers
- [ ] Show message: "⚠️ `/agents` is deprecated. Use `/agent-profiles` instead."
- [ ] Log deprecation usage for tracking

### Phase 7: Documentation Updates (10 min)
- [ ] Update `docs/ORCH_COMMANDS.md` with new commands
- [ ] Mark old commands as deprecated
- [ ] Update any README or help docs

## Detailed Implementation

### Backend Command Example

```php
// app/Commands/AgentProfileListCommand.php

<?php

namespace App\Commands;

class AgentProfileListCommand extends BaseCommand
{
    public function handle(): array
    {
        // Check if called via deprecated alias
        $isDeprecated = in_array(
            request()->input('command'), 
            ['agents', 'agent-list', 'al']
        );
        
        if ($isDeprecated) {
            // Add deprecation notice to response
            $deprecationNotice = "⚠️ This command is deprecated. Use `/agent-profiles` instead.\n\n";
        }
        
        $agents = $this->getAgentProfiles();
        
        return [
            'type' => 'agent-profile',
            'component' => 'AgentProfileListModal',
            'data' => [
                'agents' => $agents,
                'notice' => $deprecationNotice ?? null
            ]
        ];
    }
    
    private function getAgentProfiles(): array
    {
        if (class_exists(\App\Models\AgentProfile::class)) {
            $agents = \App\Models\AgentProfile::query()
                ->orderBy('name')
                ->limit(50)
                ->get();
                
            return $agents->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'slug' => $agent->slug,
                    'status' => $agent->status,
                    'type' => $agent->type,
                    'mode' => $agent->mode,
                    'description' => $agent->description ?? null,
                    'capabilities' => $agent->capabilities ?? [],
                    'constraints' => $agent->constraints ?? [],
                    'tools' => $agent->tools ?? [],
                    'metadata' => $agent->metadata ?? [],
                    'created_at' => $agent->created_at?->toISOString(),
                    'updated_at' => $agent->updated_at?->toISOString(),
                ];
            })->all();
        }
        
        return [];
    }
    
    public static function getName(): string
    {
        return 'Agent Profile List';
    }
    
    public static function getDescription(): string
    {
        return 'Manage agent profile configurations';
    }
    
    public static function getUsage(): string
    {
        return '/agent-profiles';
    }
    
    public static function getCategory(): string
    {
        return 'Orchestration';
    }
}
```

### Frontend Component Example

```typescript
// resources/js/components/orchestration/AgentProfileListModal.tsx

import { DataManagementModal } from '@/components/ui/DataManagementModal'

interface AgentProfile {
  id: string
  name: string
  slug: string
  type?: string
  mode?: string
  status?: string
  // ... other fields
}

interface AgentProfileListModalProps {
  isOpen: boolean
  onClose: () => void
  agents: AgentProfile[]
  notice?: string | null
  // ... other props
}

export function AgentProfileListModal({ 
  isOpen, 
  onClose, 
  agents,
  notice,
  // ... other props
}: AgentProfileListModalProps) {
  return (
    <>
      {notice && (
        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-2 rounded mb-4">
          {notice}
        </div>
      )}
      
      <DataManagementModal
        isOpen={isOpen}
        onClose={onClose}
        title="Agent Profile Management"
        data={agents}
        searchPlaceholder="Search agent profiles..."
        customHeader={
          <div className="text-sm text-muted-foreground">
            Manage AI agent profile configurations and capabilities
          </div>
        }
        emptyStateMessage="No agent profiles found. Create your first profile to get started."
        // ... other props
      />
    </>
  )
}

// Backward compatibility export
export { AgentProfileListModal as AgentListModal }
```

### Command Registry Example

```php
// app/Services/CommandRegistry.php

private const COMMAND_MAP = [
    // ... other commands
    
    // Agent Profile Commands (Primary)
    'agent-profiles' => AgentProfileListCommand::class,
    'ap' => AgentProfileListCommand::class,
    'agent-profile-list' => AgentProfileListCommand::class,
    'agent-profile-detail' => AgentProfileDetailCommand::class,
    'apd' => AgentProfileDetailCommand::class,
    
    // Deprecated Agent Commands (Backward Compatibility)
    'agents' => AgentProfileListCommand::class, // Deprecated: use /agent-profiles
    'agent-list' => AgentProfileListCommand::class, // Deprecated: use /agent-profiles
    'al' => AgentProfileListCommand::class, // Deprecated: use /ap
    'agent-detail' => AgentProfileDetailCommand::class, // Deprecated: use /agent-profile-detail
    'ad' => AgentProfileDetailCommand::class, // Deprecated: use /apd
    
    // ... other commands
];
```

## Acceptance Criteria

- [ ] All command files renamed with "AgentProfile" prefix
- [ ] All class names updated to "AgentProfile*"
- [ ] All user-facing text says "Agent Profile" or "Agent Profiles"
- [ ] New primary commands work: `/agent-profiles`, `/ap`, `/agent-profile-detail`, `/apd`
- [ ] Old commands still work but show deprecation notice
- [ ] Modal component renamed to `AgentProfileListModal`
- [ ] Modal routing updated in `CommandResultModal.tsx`
- [ ] Command registry updated with new mappings
- [ ] YAML command definition updated
- [ ] No broken imports or references
- [ ] No TypeScript/PHP errors
- [ ] Old commands show deprecation warning

## Testing Checklist

- [ ] Run `/agent-profiles` - shows list
- [ ] Run `/ap` - shows list (alias works)
- [ ] Run `/agents` - shows list with deprecation notice
- [ ] Run `/agent-profile-detail [slug]` - shows details
- [ ] Run `/apd [slug]` - shows details (alias works)
- [ ] Run `/agent-detail [slug]` - shows details with deprecation
- [ ] Search functionality still works
- [ ] Modal opens and closes properly
- [ ] No console errors
- [ ] No 404s or broken routes
- [ ] Help command shows new commands

## Files to Create/Modify

### Files to Rename
- `app/Commands/AgentListCommand.php` → `app/Commands/AgentProfileListCommand.php`
- `app/Commands/AgentDetailCommand.php` → `app/Commands/AgentProfileDetailCommand.php`
- `resources/js/components/orchestration/AgentListModal.tsx` → `resources/js/components/orchestration/AgentProfileListModal.tsx`
- `fragments/commands/agents/` → `fragments/commands/agent-profiles/`

### Files to Modify
- `app/Services/CommandRegistry.php` - Update command mappings
- `resources/js/islands/chat/CommandResultModal.tsx` - Update modal routing
- `app/Commands/RoutingCommand.php` - Update help text
- `docs/ORCH_COMMANDS.md` - Update documentation

### Files to Keep (Backward Compatibility)
- Keep old command names as aliases in registry
- Keep old modal routing cases
- Add deprecation notices, don't remove old functionality

## Migration Strategy

**Phase 1: Non-Breaking Changes (Week 1)**
1. Create new command classes alongside old ones
2. Add new command mappings
3. Create new modal component (can export old name too)
4. Test new commands work

**Phase 2: Deprecation (Week 2)**
1. Add deprecation notices to old commands
2. Update documentation
3. Monitor usage of old vs new commands

**Phase 3: Cleanup (Future Sprint)**
1. Remove deprecated commands after grace period
2. Remove backward compatibility exports
3. Final cleanup

## Post-Migration

After this task is complete:
- All new UI (UI-01, UI-02, UI-03, UI-04) should use "Agent Profile" terminology
- New dashboard page should be called `AgentProfileDashboard.tsx`
- New components use `AgentProfile` prefix
- API endpoints can stay `/api/agents` (backend implementation detail)

## Notes

- **Backward Compatibility**: Keep old commands working with deprecation notices
- **User Communication**: Add clear deprecation messages
- **Documentation**: Update all docs to reflect new terminology
- **Consistency**: Use "Agent Profile" everywhere in UI/commands
- **API**: Backend API routes can stay `/api/agents` (implementation detail)
- **Database**: `agent_profiles` table name doesn't change

## Why This Matters

Clear terminology prevents confusion:
- "Agent Profiles" = Configuration templates for agents
- "Agents" (future) = Running agent instances/sessions
- Separates configuration from runtime
- Aligns with database table name (`agent_profiles`)
- Sets up for future agent runtime monitoring

## Dependencies

**Before Starting:**
- None - can be done immediately

**Blocks:**
- UI-01, UI-02, UI-03, UI-04 (should use new terminology)

**Recommended:**
- Complete this FIRST before other UI tasks
- Ensures consistency from the start
