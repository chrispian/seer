# Command Mode Restoration - Technical Context

## 🔧 Current System State

### **DSL Framework Capabilities**
- **12-step framework** with advanced conditional logic support
- **Template engine** with variable processing and filters
- **Working condition steps** (with literal conditions)
- **Fragment operations**: create, query, search capabilities
- **Response types**: notify, response.panel for rich UI

### **Migration Status Overview**
The Sprint 47 command migration prioritized getting basic functionality working quickly. Several commands were simplified to avoid complex conditional logic issues:

#### **Commands Needing Mode Restoration**

##### **Bookmark Command** (`/bookmark`)
**Current**: Only creates bookmarks
**Original Modes**:
- `/bookmark` → Create bookmark from current context
- `/bookmark list` → Show all bookmarks with metadata
- `/bookmark show <name>` → Display specific bookmark with fragments
- `/bookmark forget <name>` → Delete bookmark by name (supports partial matching)

##### **Join Command** (`/join`)
**Current**: Shows help information only
**Original Modes**:
- `/join` → Show help and autocomplete
- `/join #c5` → Direct channel join by short code
- `/join #custom` → Join by custom channel name
- `/join #` → List all available channels
- `/join project` → Search channels containing "project"

##### **Channels Command** (`/channels`)
**Current**: Shows static channel list
**Original**: Dynamic database query with real channel data, activity timestamps

##### **Session Command** (`/session`)
**Current**: Shows session information only
**Original Modes**:
- `/session` → Show current session status
- `/session show` → Detailed current session information
- `/session list` → List recent sessions
- `/session start` → Begin new session
- `/session end` → End current session

##### **Routing Command** (`/routing`)
**Current**: Shows placeholder information
**Original**: Full routing rule management with database operations

## 🚨 Technical Challenges Identified

### **Condition Step Template Issues**
During Sprint 47, we discovered that condition steps fail when using template expressions:
```yaml
# This fails:
condition: "{{ ctx.body == 'list' }}"

# This works:
condition: "true"
```

**Root Cause**: Template rendering in conditions happens before the ConditionStep receives the config, but the rendered result isn't being processed correctly.

### **Argument Parsing Patterns**
Original commands used PHP pattern matching for argument parsing:
```php
// Original PHP pattern
if (str_starts_with($identifier, '#')) {
    $channelIdentifier = substr($identifier, 1);
    return $this->joinByChannelIdentifier($channelIdentifier);
}
```

**DSL Challenge**: Need to replicate this with template-based argument parsing and conditional logic.

### **Database Access Limitations**
Some original commands directly query models:
```php
$channels = ChatSession::searchForAutocomplete($query, $vaultId, $projectId, 5);
```

**Current DSL**: fragment.query step available but may not cover all query patterns needed.

## 🔗 Integration Points

### **Existing Codebase Integration**

#### **Models Used by Original Commands**
- `App\Models\Bookmark` → Bookmark management operations
- `App\Models\ChatSession` → Channel/session operations with autocomplete search
- `App\Models\Fragment` → Fragment querying and creation
- `App\Services\VaultRoutingRuleService` → Routing rule management

#### **Response Types Expected**
- **CommandResponse with panels**: Rich UI responses with structured data
- **Toast notifications**: Success/error messaging
- **Channel switching**: Special response data for navigation

#### **API Contracts**
Commands need to maintain compatibility with existing frontend expectations:
```typescript
// Expected response structure
interface CommandResponse {
  type: string;
  shouldOpenPanel?: boolean;
  shouldShowSuccessToast?: boolean;
  panelData?: any;
  data?: any;
}
```

### **Files Requiring Updates**

#### **Command Definition Files**
- `fragments/commands/bookmark/command.yaml`
- `fragments/commands/join/command.yaml` 
- `fragments/commands/channels/command.yaml`
- `fragments/commands/session/command.yaml`
- `fragments/commands/routing/command.yaml`

#### **Framework Files** (if needed)
- `app/Services/Commands/DSL/Steps/ConditionStep.php` → Fix template condition processing
- `app/Services/Commands/DSL/TemplateEngine.php` → Enhanced argument parsing filters
- `app/Services/Commands/DSL/CommandRunner.php` → Improved error handling

#### **Test Files**
- Update sample files for each command mode
- Add comprehensive test coverage for argument patterns

## 📋 Dependencies and Constraints

### **Framework Limitations to Work Around**
1. **Condition template rendering** → May need to implement workarounds or fix framework
2. **Database query patterns** → Limited to fragment.query step currently
3. **Model access** → May need new step types for complex model operations

### **External Dependencies**
- **Frontend compatibility**: Must maintain existing panel data structures
- **Database schema**: Existing models and relationships must be preserved
- **User workflows**: No breaking changes to user command patterns

### **Performance Requirements**
- **No regressions**: Maintain current DSL performance (<10ms for non-AI commands)
- **Database efficiency**: Ensure queries are optimized and properly indexed
- **Memory usage**: Template caching should not cause memory bloat

## 🎯 Implementation Strategy

### **Phase 1: Framework Issues**
1. Investigate and fix condition step template rendering
2. Create enhanced argument parsing template filters
3. Test framework improvements with simple cases

### **Phase 2: Command Mode Implementation**
1. Start with bookmark command (most straightforward)
2. Implement join command (complex argument patterns)
3. Add dynamic data to channels command
4. Complete session command modes
5. Add routing command functionality

### **Phase 3: Testing and Validation**
1. Comprehensive testing of all modes
2. Performance validation
3. Frontend integration verification
4. Documentation updates