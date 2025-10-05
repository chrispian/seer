# DSL-UX-006: Alias Conflict Resolution - Context

## Problem Statement

### Alias Conflict Scenarios
**Command Alias Collisions**: When multiple commands attempt to register the same alias, the system needs to detect conflicts and resolve them predictably.

**Common Conflict Examples**:
```yaml
# fragments/commands/search.yaml
aliases: ["s", "find", "lookup"]

# fragments/commands/sync.yaml  
aliases: ["s", "synchronize"]  # CONFLICT: "s" already used by search

# fragments/commands/sql.yaml
aliases: ["s", "query"]        # CONFLICT: "s" already used by search
```

**Silent Failures**: Currently, alias conflicts may result in:
- Last-loaded command overwriting previous aliases
- Inconsistent alias resolution depending on load order
- Users unable to access commands through expected aliases
- No visibility into which aliases are conflicting

## Current System Analysis

### Command Pack Loading Process
**Location**: `app/Services/Commands/CommandPackLoader.php`

**Current Flow** (likely):
```php
public function updateRegistryCache(): void
{
    foreach ($this->getCommandPacks() as $pack) {
        $commands = $this->parseYamlPack($pack);
        
        foreach ($commands as $command) {
            // PROBLEM: No conflict detection before inserting
            CommandRegistry::updateOrCreate(
                ['slug' => $command['slug']],
                [
                    'aliases' => json_encode($command['aliases'] ?? []),
                    // ... other fields
                ]
            );
        }
    }
}
```

**Issues with Current Approach**:
- No validation of alias uniqueness across commands
- No conflict detection or reporting
- Last command wins in case of conflicts
- No precedence rules for system vs user commands

### Registry Schema Context
**From DSL-UX-001**, the enhanced `command_registry` table includes:
```sql
command_registry:
- slug           -- canonical command identifier (unique)
- aliases        -- JSON array of alternative slugs
- manifest_path  -- source YAML file path
- created_at, updated_at
```

**Conflict Detection Requirements**:
- Track which command owns each alias
- Detect when new commands attempt to claim existing aliases
- Provide conflict resolution mechanisms
- Log conflicts for administrator review

## Types of Conflicts

### 1. Direct Alias Conflicts
**Scenario**: Two commands claim the same alias
```yaml
# Command A
slug: search
aliases: ["s", "find"]

# Command B  
slug: sync
aliases: ["s", "synchronize"]  # Conflict on "s"
```

### 2. Alias-to-Slug Conflicts
**Scenario**: Command alias conflicts with another command's slug
```yaml
# Command A
slug: search
aliases: ["job"]  # Conflicts with Command B's slug

# Command B
slug: job
aliases: ["j"]
```

### 3. Reserved Alias Conflicts
**Scenario**: Command tries to use system-reserved aliases
```yaml
# User command
slug: help-extended
aliases: ["help"]  # Conflicts with system "help" command
```

### 4. Case Sensitivity Conflicts
**Scenario**: Similar aliases with different casing
```yaml
# Command A
aliases: ["AI", "ai"]

# Command B
aliases: ["Ai"]  # Potential conflict depending on resolution strategy
```

## Conflict Resolution Strategies

### Precedence Rules

#### 1. System vs User Commands
**Priority Order**:
1. **System/Core Commands**: Highest priority (e.g., help, version)
2. **Framework Commands**: High priority (built-in functionality)
3. **User Commands**: Lower priority (custom commands)

#### 2. Package Type Precedence  
**Based on manifest_path**:
```
/system/commands/     → Priority 1 (system)
/core/commands/       → Priority 2 (framework)
/user/commands/       → Priority 3 (user)
/fragments/commands/  → Priority 4 (generated)
```

#### 3. Alphabetical Fallback
**When same priority**: Alphabetical order by command slug for deterministic resolution

### Resolution Mechanisms

#### 1. Conflict Prevention
**Pre-Registration Validation**:
```php
public function validateAliases(array $newAliases, string $commandSlug): array
{
    $conflicts = [];
    
    foreach ($newAliases as $alias) {
        // Check against existing aliases
        $existingCommand = $this->findCommandByAlias($alias);
        if ($existingCommand && $existingCommand->slug !== $commandSlug) {
            $conflicts[] = [
                'alias' => $alias,
                'existing_command' => $existingCommand->slug,
                'new_command' => $commandSlug,
                'conflict_type' => 'alias_collision'
            ];
        }
        
        // Check against existing slugs
        $commandWithSlug = CommandRegistry::where('slug', $alias)->first();
        if ($commandWithSlug && $commandWithSlug->slug !== $commandSlug) {
            $conflicts[] = [
                'alias' => $alias,
                'existing_command' => $commandWithSlug->slug,
                'new_command' => $commandSlug,
                'conflict_type' => 'alias_to_slug_collision'
            ];
        }
    }
    
    return $conflicts;
}
```

#### 2. Automatic Resolution
**Priority-Based Resolution**:
```php
public function resolveConflicts(array $conflicts): array
{
    $resolutions = [];
    
    foreach ($conflicts as $conflict) {
        $existingPriority = $this->getCommandPriority($conflict['existing_command']);
        $newPriority = $this->getCommandPriority($conflict['new_command']);
        
        if ($newPriority > $existingPriority) {
            // New command wins - remove alias from existing command
            $resolutions[] = [
                'action' => 'transfer_alias',
                'alias' => $conflict['alias'],
                'from' => $conflict['existing_command'],
                'to' => $conflict['new_command']
            ];
        } else {
            // Existing command wins - reject new alias
            $resolutions[] = [
                'action' => 'reject_alias',
                'alias' => $conflict['alias'],
                'rejected_command' => $conflict['new_command'],
                'winning_command' => $conflict['existing_command']
            ];
        }
    }
    
    return $resolutions;
}
```

#### 3. Manual Override System
**Admin Resolution Interface**:
```php
// Allow administrators to override automatic resolution
public function overrideConflictResolution(string $alias, string $winningCommand): void
{
    $this->recordManualResolution($alias, $winningCommand);
    $this->transferAlias($alias, $winningCommand);
}
```

## Conflict Detection Implementation

### Database Schema for Conflict Tracking
**New Table**: `command_alias_conflicts`
```sql
CREATE TABLE command_alias_conflicts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    alias VARCHAR(255) NOT NULL,
    existing_command VARCHAR(255) NOT NULL,
    conflicting_command VARCHAR(255) NOT NULL,
    conflict_type ENUM('alias_collision', 'alias_to_slug_collision', 'reserved_conflict'),
    resolution_action ENUM('auto_resolved', 'manual_override', 'pending'),
    resolved_in_favor_of VARCHAR(255),
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolver_context JSON,
    INDEX idx_alias (alias),
    INDEX idx_resolution_status (resolution_action),
    INDEX idx_detected_at (detected_at)
);
```

### Real-Time Conflict Detection
**During Registry Updates**:
```php
public function updateRegistryWithConflictDetection(array $commandData): array
{
    $conflicts = $this->validateAliases($commandData['aliases'], $commandData['slug']);
    
    if (!empty($conflicts)) {
        // Log conflicts
        $this->logConflicts($conflicts);
        
        // Record in conflict tracking table
        $this->recordConflicts($conflicts);
        
        // Attempt automatic resolution
        $resolutions = $this->resolveConflicts($conflicts);
        
        // Apply resolutions
        $this->applyResolutions($resolutions);
        
        return [
            'conflicts_detected' => count($conflicts),
            'conflicts_resolved' => count($resolutions),
            'resolution_details' => $resolutions
        ];
    }
    
    return ['conflicts_detected' => 0];
}
```

## Monitoring and Alerting

### Conflict Metrics
**Key Metrics to Track**:
- Total conflicts detected per registry rebuild
- Conflict resolution success rate
- Manual override frequency
- Most conflicted aliases
- Commands most frequently involved in conflicts

### Alerting Triggers
**Alert Conditions**:
- High conflict rate (>5 conflicts per rebuild)
- Unresolved conflicts blocking command registration
- System command aliases being overridden
- Critical command aliases becoming unavailable

### Conflict Dashboard
**Administrative Interface Requirements**:
- List of all detected conflicts
- Conflict resolution history
- Alias ownership mapping
- Manual override controls
- Conflict trend analysis

## Performance Considerations

### Conflict Detection Performance
**Optimization Strategies**:
- Index aliases for fast lookup
- Cache conflict rules and precedence
- Batch conflict detection during rebuilds
- Lazy conflict resolution for non-critical aliases

### Memory Usage
**Efficient Data Structures**:
```php
// Build alias ownership map for fast lookups
private array $aliasOwnershipMap = [];

public function buildAliasMap(): void
{
    $commands = CommandRegistry::select('slug', 'aliases')->get();
    
    foreach ($commands as $command) {
        $aliases = json_decode($command->aliases, true) ?? [];
        foreach ($aliases as $alias) {
            $this->aliasOwnershipMap[$alias] = $command->slug;
        }
    }
}
```

## Integration Points

### Command Pack Loader Integration
**Enhanced Loading Process**:
```php
public function loadCommandPack(string $packPath): array
{
    $commands = $this->parseYamlPack($packPath);
    $conflictSummary = ['total_conflicts' => 0, 'resolved_conflicts' => 0];
    
    foreach ($commands as $command) {
        $result = $this->updateRegistryWithConflictDetection($command);
        $conflictSummary['total_conflicts'] += $result['conflicts_detected'];
        $conflictSummary['resolved_conflicts'] += $result['conflicts_resolved'] ?? 0;
    }
    
    return $conflictSummary;
}
```

### Autocomplete Service Integration
**Conflict-Aware Alias Resolution**:
```php
public function resolveAlias(string $alias): ?string
{
    // Check for active conflicts
    $activeConflict = $this->getActiveConflictForAlias($alias);
    if ($activeConflict) {
        // Return the winning command from conflict resolution
        return $activeConflict->resolved_in_favor_of;
    }
    
    // Normal alias resolution
    return $this->aliasOwnershipMap[$alias] ?? null;
}
```

### Help System Integration
**Conflict Information in Help**:
```php
public function getCommandHelp(string $slug): array
{
    $helpData = $this->buildCommandHelp($slug);
    
    // Add conflict information if command has contested aliases
    $conflicts = $this->getConflictsForCommand($slug);
    if (!empty($conflicts)) {
        $helpData['alias_conflicts'] = [
            'contested_aliases' => $conflicts->pluck('alias'),
            'resolution_status' => $conflicts->pluck('resolution_action')
        ];
    }
    
    return $helpData;
}
```

## Risk Assessment

### System Stability Risks
**Conflict Resolution Impact**:
- **Risk**: Aggressive conflict resolution breaks existing workflows
- **Mitigation**: Conservative resolution strategy, extensive logging
- **Testing**: Comprehensive conflict scenario testing

### User Experience Risks
**Alias Availability**:
- **Risk**: Popular aliases become unavailable due to conflicts
- **Mitigation**: Conflict notification, alternative alias suggestions
- **Monitoring**: Track alias usage patterns and conflicts

### Performance Risks
**Detection Overhead**:
- **Risk**: Conflict detection slows down registry updates
- **Mitigation**: Efficient algorithms, caching, batched processing
- **Optimization**: Profile and optimize conflict detection code

This context establishes the foundation for implementing a robust alias conflict resolution system that maintains system stability while providing visibility into and control over command alias management.