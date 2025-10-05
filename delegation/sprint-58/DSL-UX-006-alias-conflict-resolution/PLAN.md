# DSL-UX-006: Alias Conflict Resolution - Implementation Plan

## Overview
Implement a robust system to detect, resolve, and manage command alias conflicts with proper precedence rules and administrative oversight.

**Dependencies**: DSL-UX-001 (Enhanced Registry Schema), DSL-UX-002 (Autocomplete Service)  
**Estimated Time**: 4-6 hours  
**Priority**: MEDIUM (system robustness and maintainability)

## Implementation Phases

### Phase 1: Conflict Detection Foundation (2 hours)

#### 1.1 Create Conflict Tracking Database Schema (30 minutes)
**Migration**: `database/migrations/create_command_alias_conflicts_table.php`

```php
Schema::create('command_alias_conflicts', function (Blueprint $table) {
    $table->id();
    $table->string('alias');
    $table->string('existing_command');
    $table->string('conflicting_command');
    $table->enum('conflict_type', [
        'alias_collision', 
        'alias_to_slug_collision', 
        'reserved_conflict'
    ]);
    $table->enum('resolution_action', [
        'auto_resolved', 
        'manual_override', 
        'pending'
    ])->default('pending');
    $table->string('resolved_in_favor_of')->nullable();
    $table->timestamp('detected_at')->useCurrent();
    $table->timestamp('resolved_at')->nullable();
    $table->json('resolver_context')->nullable();
    
    $table->index(['alias']);
    $table->index(['resolution_action']);
    $table->index(['detected_at']);
});
```

**Model**: `app/Models/CommandAliasConflict.php`

```php
class CommandAliasConflict extends Model
{
    protected $fillable = [
        'alias', 'existing_command', 'conflicting_command', 
        'conflict_type', 'resolution_action', 'resolved_in_favor_of',
        'detected_at', 'resolved_at', 'resolver_context'
    ];
    
    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'resolver_context' => 'array'
    ];
    
    public function isResolved(): bool
    {
        return $this->resolution_action !== 'pending';
    }
    
    public function getWinnerCommand(): ?string
    {
        return $this->resolved_in_favor_of;
    }
}
```

#### 1.2 Implement Conflict Detection Service (1 hour)
**Service**: `app/Services/AliasConflictService.php`

```php
class AliasConflictService
{
    private array $aliasOwnershipMap = [];
    private array $commandPriorities = [];
    
    public function validateAliases(array $newAliases, string $commandSlug, string $manifestPath): array
    {
        $this->buildAliasMap();
        $conflicts = [];
        
        foreach ($newAliases as $alias) {
            // Check alias collision
            if (isset($this->aliasOwnershipMap[$alias]) && 
                $this->aliasOwnershipMap[$alias] !== $commandSlug) {
                
                $conflicts[] = [
                    'alias' => $alias,
                    'existing_command' => $this->aliasOwnershipMap[$alias],
                    'new_command' => $commandSlug,
                    'conflict_type' => 'alias_collision',
                    'new_command_priority' => $this->getCommandPriority($manifestPath),
                    'existing_command_priority' => $this->getCommandPriority(
                        $this->getManifestPath($this->aliasOwnershipMap[$alias])
                    )
                ];
            }
            
            // Check alias-to-slug collision
            $existingCommand = CommandRegistry::where('slug', $alias)->first();
            if ($existingCommand && $existingCommand->slug !== $commandSlug) {
                $conflicts[] = [
                    'alias' => $alias,
                    'existing_command' => $existingCommand->slug,
                    'new_command' => $commandSlug,
                    'conflict_type' => 'alias_to_slug_collision',
                    'new_command_priority' => $this->getCommandPriority($manifestPath),
                    'existing_command_priority' => $this->getCommandPriority($existingCommand->manifest_path)
                ];
            }
            
            // Check reserved conflicts
            if ($this->isReservedAlias($alias) && !$this->isSystemCommand($commandSlug, $manifestPath)) {
                $conflicts[] = [
                    'alias' => $alias,
                    'existing_command' => 'SYSTEM_RESERVED',
                    'new_command' => $commandSlug,
                    'conflict_type' => 'reserved_conflict',
                    'new_command_priority' => $this->getCommandPriority($manifestPath),
                    'existing_command_priority' => 1 // Highest priority
                ];
            }
        }
        
        return $conflicts;
    }
    
    private function buildAliasMap(): void
    {
        if (!empty($this->aliasOwnershipMap)) return; // Already built
        
        $commands = CommandRegistry::select('slug', 'aliases', 'manifest_path')->get();
        
        foreach ($commands as $command) {
            $aliases = json_decode($command->aliases, true) ?? [];
            foreach ($aliases as $alias) {
                $this->aliasOwnershipMap[$alias] = $command->slug;
            }
        }
    }
    
    private function getCommandPriority(string $manifestPath): int
    {
        // System commands (highest priority)
        if (str_contains($manifestPath, '/system/commands/')) return 1;
        
        // Core/framework commands
        if (str_contains($manifestPath, '/core/commands/')) return 2;
        
        // User commands
        if (str_contains($manifestPath, '/user/commands/')) return 3;
        
        // Generated/fragment commands (lowest priority)
        return 4;
    }
    
    private function isReservedAlias(string $alias): bool
    {
        $reserved = ['help', 'version', 'exit', 'quit', 'clear'];
        return in_array(strtolower($alias), $reserved);
    }
    
    private function isSystemCommand(string $slug, string $manifestPath): bool
    {
        return $this->getCommandPriority($manifestPath) <= 2;
    }
}
```

#### 1.3 Implement Conflict Resolution Logic (30 minutes)
**Resolution Methods** (add to `AliasConflictService`):

```php
public function resolveConflicts(array $conflicts): array
{
    $resolutions = [];
    
    foreach ($conflicts as $conflict) {
        $resolution = $this->resolveConflict($conflict);
        $resolutions[] = $resolution;
        
        // Record conflict in database
        $this->recordConflict($conflict, $resolution);
    }
    
    return $resolutions;
}

private function resolveConflict(array $conflict): array
{
    $newPriority = $conflict['new_command_priority'];
    $existingPriority = $conflict['existing_command_priority'];
    
    if ($newPriority < $existingPriority) {
        // New command has higher priority (lower number = higher priority)
        return [
            'action' => 'transfer_alias',
            'alias' => $conflict['alias'],
            'from' => $conflict['existing_command'],
            'to' => $conflict['new_command'],
            'reason' => 'priority_override'
        ];
    } elseif ($newPriority > $existingPriority) {
        // Existing command has higher priority
        return [
            'action' => 'reject_alias',
            'alias' => $conflict['alias'],
            'rejected_command' => $conflict['new_command'],
            'winning_command' => $conflict['existing_command'],
            'reason' => 'priority_protection'
        ];
    } else {
        // Same priority - use alphabetical order for deterministic resolution
        $winner = strcmp($conflict['existing_command'], $conflict['new_command']) < 0
            ? $conflict['existing_command']
            : $conflict['new_command'];
            
        return [
            'action' => $winner === $conflict['new_command'] ? 'transfer_alias' : 'reject_alias',
            'alias' => $conflict['alias'],
            'from' => $conflict['existing_command'],
            'to' => $conflict['new_command'],
            'winning_command' => $winner,
            'reason' => 'alphabetical_fallback'
        ];
    }
}

private function recordConflict(array $conflict, array $resolution): void
{
    CommandAliasConflict::create([
        'alias' => $conflict['alias'],
        'existing_command' => $conflict['existing_command'],
        'conflicting_command' => $conflict['new_command'],
        'conflict_type' => $conflict['conflict_type'],
        'resolution_action' => 'auto_resolved',
        'resolved_in_favor_of' => $resolution['winning_command'] ?? $resolution['to'] ?? $resolution['from'],
        'detected_at' => now(),
        'resolved_at' => now(),
        'resolver_context' => [
            'resolution_reason' => $resolution['reason'],
            'new_command_priority' => $conflict['new_command_priority'],
            'existing_command_priority' => $conflict['existing_command_priority']
        ]
    ]);
}
```

### Phase 2: Integration with Command Loading (1-2 hours)

#### 2.1 Enhance CommandPackLoader (1 hour)
**Update**: `app/Services/Commands/CommandPackLoader.php`

```php
class CommandPackLoader
{
    public function __construct(
        private AliasConflictService $conflictService
    ) {}
    
    public function updateRegistryCache(): array
    {
        $summary = [
            'commands_processed' => 0,
            'conflicts_detected' => 0,
            'conflicts_resolved' => 0,
            'conflicts_pending' => 0
        ];
        
        foreach ($this->getCommandPacks() as $packPath) {
            $commands = $this->parseYamlPack($packPath);
            
            foreach ($commands as $commandData) {
                $result = $this->processCommandWithConflictDetection($commandData, $packPath);
                
                $summary['commands_processed']++;
                $summary['conflicts_detected'] += $result['conflicts_detected'];
                $summary['conflicts_resolved'] += $result['conflicts_resolved'];
                $summary['conflicts_pending'] += $result['conflicts_pending'];
            }
        }
        
        $this->logRegistryRebuildSummary($summary);
        return $summary;
    }
    
    private function processCommandWithConflictDetection(array $commandData, string $packPath): array
    {
        $aliases = $commandData['aliases'] ?? [];
        
        if (empty($aliases)) {
            // No aliases to check
            $this->updateRegistryEntry($commandData, $packPath);
            return ['conflicts_detected' => 0, 'conflicts_resolved' => 0, 'conflicts_pending' => 0];
        }
        
        // Detect conflicts
        $conflicts = $this->conflictService->validateAliases(
            $aliases, 
            $commandData['slug'], 
            $packPath
        );
        
        if (empty($conflicts)) {
            // No conflicts - proceed normally
            $this->updateRegistryEntry($commandData, $packPath);
            return ['conflicts_detected' => 0, 'conflicts_resolved' => 0, 'conflicts_pending' => 0];
        }
        
        // Resolve conflicts
        $resolutions = $this->conflictService->resolveConflicts($conflicts);
        
        // Apply resolutions and update registry
        $resolvedAliases = $this->applyResolutions($aliases, $resolutions);
        $commandData['aliases'] = $resolvedAliases;
        
        $this->updateRegistryEntry($commandData, $packPath);
        
        return [
            'conflicts_detected' => count($conflicts),
            'conflicts_resolved' => count($resolutions),
            'conflicts_pending' => 0 // All conflicts auto-resolved for now
        ];
    }
    
    private function applyResolutions(array $originalAliases, array $resolutions): array
    {
        $finalAliases = $originalAliases;
        
        foreach ($resolutions as $resolution) {
            if ($resolution['action'] === 'reject_alias') {
                // Remove rejected alias from this command
                $finalAliases = array_filter($finalAliases, fn($alias) => $alias !== $resolution['alias']);
            } elseif ($resolution['action'] === 'transfer_alias') {
                // Remove alias from previous owner, keep for new owner
                $this->removeAliasFromCommand($resolution['from'], $resolution['alias']);
            }
        }
        
        return array_values($finalAliases); // Re-index array
    }
    
    private function removeAliasFromCommand(string $commandSlug, string $aliasToRemove): void
    {
        $command = CommandRegistry::where('slug', $commandSlug)->first();
        if (!$command) return;
        
        $aliases = json_decode($command->aliases, true) ?? [];
        $aliases = array_filter($aliases, fn($alias) => $alias !== $aliasToRemove);
        
        $command->update(['aliases' => json_encode(array_values($aliases))]);
    }
    
    private function logRegistryRebuildSummary(array $summary): void
    {
        Log::info('Command registry rebuild completed', $summary);
        
        if ($summary['conflicts_detected'] > 0) {
            Log::warning('Alias conflicts detected during registry rebuild', [
                'total_conflicts' => $summary['conflicts_detected'],
                'resolved_conflicts' => $summary['conflicts_resolved'],
                'pending_conflicts' => $summary['conflicts_pending']
            ]);
        }
    }
}
```

#### 2.2 Update AutocompleteService for Conflict-Aware Resolution (30 minutes)
**Update**: `app/Services/AutocompleteService.php` (add method)

```php
public function resolveAlias(string $alias): ?string
{
    // Check for active conflicts first
    $activeConflict = CommandAliasConflict::where('alias', $alias)
        ->where('resolution_action', '!=', 'pending')
        ->latest('resolved_at')
        ->first();
        
    if ($activeConflict) {
        return $activeConflict->getWinnerCommand();
    }
    
    // Normal alias resolution through registry
    $command = CommandRegistry::whereJsonContains('aliases', $alias)->first();
    return $command?->slug;
}

public function getAliasConflicts(string $alias): Collection
{
    return CommandAliasConflict::where('alias', $alias)
        ->orderBy('detected_at', 'desc')
        ->get();
}
```

### Phase 3: Administrative Interface (1 hour)

#### 3.1 Conflict Management API (30 minutes)
**Controller**: `app/Http/Controllers/Admin/ConflictController.php`

```php
class ConflictController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conflicts = CommandAliasConflict::query()
            ->when($request->filled('status'), function($query) use ($request) {
                $query->where('resolution_action', $request->input('status'));
            })
            ->when($request->filled('alias'), function($query) use ($request) {
                $query->where('alias', 'like', '%' . $request->input('alias') . '%');
            })
            ->orderBy('detected_at', 'desc')
            ->paginate(50);
            
        return response()->json($conflicts);
    }
    
    public function show(CommandAliasConflict $conflict): JsonResponse
    {
        return response()->json([
            'conflict' => $conflict,
            'commands' => [
                'existing' => CommandRegistry::where('slug', $conflict->existing_command)->first(),
                'conflicting' => CommandRegistry::where('slug', $conflict->conflicting_command)->first()
            ]
        ]);
    }
    
    public function resolve(CommandAliasConflict $conflict, Request $request): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|in:favor_existing,favor_conflicting,remove_alias',
            'reason' => 'nullable|string|max:255'
        ]);
        
        $resolution = $request->input('resolution');
        $winner = match($resolution) {
            'favor_existing' => $conflict->existing_command,
            'favor_conflicting' => $conflict->conflicting_command,
            'remove_alias' => null
        };
        
        if ($resolution === 'remove_alias') {
            // Remove alias from both commands
            $this->removeAliasFromBothCommands($conflict->alias, $conflict);
            $winner = 'REMOVED';
        } else {
            // Transfer alias to winner, remove from loser
            $loser = $winner === $conflict->existing_command 
                ? $conflict->conflicting_command 
                : $conflict->existing_command;
                
            $this->transferAlias($conflict->alias, $winner, $loser);
        }
        
        $conflict->update([
            'resolution_action' => 'manual_override',
            'resolved_in_favor_of' => $winner,
            'resolved_at' => now(),
            'resolver_context' => [
                'manual_reason' => $request->input('reason'),
                'resolved_by' => auth()->user()?->id,
                'resolution_method' => $resolution
            ]
        ]);
        
        return response()->json(['message' => 'Conflict resolved successfully']);
    }
    
    private function transferAlias(string $alias, string $winner, string $loser): void
    {
        // Remove from loser
        $loserCommand = CommandRegistry::where('slug', $loser)->first();
        if ($loserCommand) {
            $aliases = json_decode($loserCommand->aliases, true) ?? [];
            $aliases = array_filter($aliases, fn($a) => $a !== $alias);
            $loserCommand->update(['aliases' => json_encode(array_values($aliases))]);
        }
        
        // Add to winner (if not already present)
        $winnerCommand = CommandRegistry::where('slug', $winner)->first();
        if ($winnerCommand) {
            $aliases = json_decode($winnerCommand->aliases, true) ?? [];
            if (!in_array($alias, $aliases)) {
                $aliases[] = $alias;
                $winnerCommand->update(['aliases' => json_encode($aliases)]);
            }
        }
    }
}
```

#### 3.2 Conflict Dashboard Routes (15 minutes)
**Routes**: `routes/api.php` (admin section)

```php
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::apiResource('conflicts', Admin\ConflictController::class);
    Route::post('conflicts/{conflict}/resolve', [Admin\ConflictController::class, 'resolve'])
        ->name('admin.conflicts.resolve');
    
    Route::get('conflicts/stats/summary', [Admin\ConflictController::class, 'stats'])
        ->name('admin.conflicts.stats');
});
```

#### 3.3 Conflict Statistics (15 minutes)
**Add to ConflictController**:

```php
public function stats(): JsonResponse
{
    $stats = [
        'total_conflicts' => CommandAliasConflict::count(),
        'pending_conflicts' => CommandAliasConflict::where('resolution_action', 'pending')->count(),
        'auto_resolved' => CommandAliasConflict::where('resolution_action', 'auto_resolved')->count(),
        'manual_overrides' => CommandAliasConflict::where('resolution_action', 'manual_override')->count(),
        'most_conflicted_aliases' => CommandAliasConflict::select('alias')
            ->selectRaw('COUNT(*) as conflict_count')
            ->groupBy('alias')
            ->orderBy('conflict_count', 'desc')
            ->limit(10)
            ->get(),
        'recent_conflicts' => CommandAliasConflict::latest('detected_at')->limit(5)->get()
    ];
    
    return response()->json($stats);
}
```

### Phase 4: Monitoring and Logging (30 minutes)

#### 4.1 Enhanced Logging (15 minutes)
**Add to existing MetricsService** (from DSL-UX-005):

```php
public function recordAliasConflict(array $conflictData): void
{
    Log::warning('Alias conflict detected', [
        'alias' => $conflictData['alias'],
        'existing_command' => $conflictData['existing_command'],
        'conflicting_command' => $conflictData['new_command'],
        'conflict_type' => $conflictData['conflict_type'],
        'auto_resolution' => $conflictData['resolution'] ?? null
    ]);
    
    // Send metrics
    $this->sendMetric('alias_conflicts.detected', 1);
    $this->sendMetric("alias_conflicts.type.{$conflictData['conflict_type']}", 1);
}

public function recordConflictResolution(array $resolutionData): void
{
    Log::info('Alias conflict resolved', [
        'alias' => $resolutionData['alias'],
        'resolution_type' => $resolutionData['resolution_type'], // auto/manual
        'winner' => $resolutionData['winner'],
        'resolution_reason' => $resolutionData['reason']
    ]);
    
    $this->sendMetric('alias_conflicts.resolved', 1);
    $this->sendMetric("alias_conflicts.resolution.{$resolutionData['resolution_type']}", 1);
}
```

#### 4.2 Health Check Integration (15 minutes)
**Add to existing HealthController** (from DSL-UX-005):

```php
private function checkAliasConflicts(): array
{
    try {
        $pendingConflicts = CommandAliasConflict::where('resolution_action', 'pending')->count();
        $totalConflicts = CommandAliasConflict::count();
        $recentConflicts = CommandAliasConflict::where('detected_at', '>=', now()->subHours(24))->count();
        
        $status = $pendingConflicts > 10 ? 'unhealthy' : 
                 ($pendingConflicts > 5 ? 'degraded' : 'healthy');
        
        return [
            'status' => $status,
            'pending_conflicts' => $pendingConflicts,
            'total_conflicts' => $totalConflicts,
            'conflicts_last_24h' => $recentConflicts
        ];
    } catch (Exception $e) {
        return [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
    }
}
```

## Testing Strategy

### Unit Tests
**File**: `tests/Unit/AliasConflictServiceTest.php`

```php
class AliasConflictServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_detects_alias_collision()
    public function test_detects_alias_to_slug_collision()
    public function test_detects_reserved_alias_conflicts()
    public function test_resolves_conflicts_by_priority()
    public function test_alphabetical_fallback_resolution()
    public function test_records_conflicts_in_database()
}
```

### Integration Tests
**File**: `tests/Feature/ConflictResolutionTest.php`

```php
class ConflictResolutionTest extends TestCase
{
    public function test_command_loading_handles_conflicts()
    public function test_autocomplete_respects_conflict_resolution()
    public function test_admin_can_override_conflict_resolution()
    public function test_conflict_api_endpoints_work()
}
```

## Success Criteria

### Functional Requirements
- [ ] Detect all types of alias conflicts during command loading
- [ ] Automatically resolve conflicts using priority rules
- [ ] Provide administrative interface for manual conflict resolution
- [ ] Maintain conflict history and audit trail

### Performance Requirements
- [ ] Conflict detection adds < 10% overhead to registry rebuild
- [ ] Alias resolution remains under 50ms with conflict checking
- [ ] Administrative interface loads conflict list under 2 seconds

### Quality Requirements
- [ ] Zero silent alias conflicts (all conflicts detected and logged)
- [ ] Deterministic conflict resolution (same conflicts always resolve the same way)
- [ ] Complete audit trail for all conflict resolutions

This plan ensures robust alias conflict management while maintaining system performance and providing administrative control over conflict resolution.