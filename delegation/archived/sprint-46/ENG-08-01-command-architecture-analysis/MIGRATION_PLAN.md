# Command Migration Strategy & Implementation Plan

## Migration Overview

**Goal**: Migrate all 18 hardcoded commands to unified YAML DSL system
**Timeline**: 4 phases across remaining Sprint 46 tasks (ENG-08-02, ENG-08-03, ENG-08-04)
**Strategy**: Incremental migration with framework enhancement

## Migration Phases

### **Phase 1: Foundation & Simple Commands** (ENG-08-02)
**Duration**: 8-12 hours  
**Goal**: Establish migration patterns and handle simple commands

#### **DSL Framework Extensions Required**
1. **`fragment.query`** step type
2. **`fragment.update`** step type  
3. **`condition`** step type
4. **`response.panel`** step type
5. Enhanced template engine with expressions

#### **Commands to Migrate** (5 commands)
1. **`clear`** ✅ Simple - No complex logic
2. **`help`** ✅ Simple - Static content with dynamic command list
3. **`name`** ✅ Simple - Basic text operations
4. **`session`** 🔄 Medium - State management (simplified version)
5. **`bookmark`** 🔄 Medium - Fragment relationships

#### **Migration Approach**
- Start with `clear` (simplest) to establish patterns
- Build framework extensions incrementally
- Test each migration thoroughly before proceeding
- Create migration utility scripts

### **Phase 2: Medium Complexity Commands** (ENG-08-02 continued)
**Goal**: Handle commands with moderate complexity

#### **Additional DSL Extensions**
1. **`state.get`** / **`state.set`** step types
2. **`response.toast`** step type
3. **`user.context`** step type

#### **Commands to Migrate** (4 commands)
1. **`frag`** 🔄 Medium - Fragment operations with filtering
2. **`join`** 🔄 Medium - System operations
3. **`channels`** 🔄 Medium - System information
4. **`routing`** 🔄 Medium - System configuration

### **Phase 3: Complex Commands & Conflict Resolution** (ENG-08-03)
**Duration**: 8-12 hours  
**Goal**: Handle advanced commands and resolve system conflicts

#### **Advanced DSL Extensions**
1. **`loop`** step type for batch operations
2. **`database.query`** for complex queries
3. **Enhanced error handling** and validation
4. **Multi-step workflows**

#### **Commands to Migrate** (5 commands)
1. **`vault`** ❌ Complex - Advanced querying and organization
2. **`project`** ❌ Complex - Project management features  
3. **`context`** ❌ Complex - Context switching and management
4. **`compose`** ❌ Complex - Multi-step workflow management
5. **Conflict resolution** for overlapping commands

#### **Conflict Resolution Strategy**
Handle the 4 conflicted commands:

1. **`recall`** - Merge functionality
   - Keep hardcoded query capabilities
   - Add YAML creation features
   - Unified behavior with type parameter

2. **`todo`** - Enhanced unified version
   - Combine CRUD operations from hardcoded
   - Add AI parsing from YAML
   - Support both simple and complex operations

3. **`inbox`** - Merge API and functionality
   - Keep hardcoded multi-view system
   - Add API documentation from YAML
   - Unified interface

4. **`search`** - Enhanced search system
   - Advanced filtering from hardcoded
   - Simple interface from YAML
   - Progressive complexity

### **Phase 4: System Cleanup & Optimization** (ENG-08-04)
**Duration**: 6-8 hours  
**Goal**: Remove dual system and optimize unified architecture

#### **Cleanup Tasks**
1. **Remove hardcoded system** completely
2. **Clean up CommandRegistry.php**
3. **Simplify CommandController.php**
4. **Update documentation**
5. **Performance optimization**
6. **Final testing and validation**

## Detailed Migration Strategy

### **1. Framework-First Approach**

#### **Step 1: Extend DSL Framework**
Before migrating any commands, implement required step types:

```php
// Priority order for step type implementation
1. fragment.query    // Essential for most commands
2. fragment.update   // Needed for state changes
3. condition         // Required for branching logic  
4. response.panel    // UI response handling
5. state.get/set     // Session management
6. response.toast    // User feedback
```

#### **Step 2: Enhance Template Engine**
Add expression evaluation capabilities:
```yaml
# Before (limited)
count: "{{ steps.query.output.count }}"

# After (enhanced)
count: "{{ steps.query.output.count + 1 }}"
message: "{{ ctx.status == 'all' ? 'All Items' : 'Filtered Items' }}"
```

#### **Step 3: Response System Enhancement**
Implement specialized response builders:
```php
class PanelResponseBuilder
class ToastResponseBuilder
class SystemResponseBuilder
```

### **2. Command-by-Command Migration**

#### **Migration Pattern**
For each command:
1. **Analyze current implementation**
2. **Map to DSL capabilities**
3. **Implement missing DSL features**
4. **Create YAML equivalent**
5. **Side-by-side testing**
6. **Switch over**
7. **Remove hardcoded version**

#### **Example: Clear Command Migration**

**Current Implementation**:
```php
class ClearCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        return new CommandResponse(
            type: 'system',
            shouldResetChat: true,
            message: '🧹 Chat cleared',
        );
    }
}
```

**YAML Implementation**:
```yaml
name: "Clear Chat"
slug: clear
version: 1.0.0
triggers:
  slash: "/clear"
  aliases: []
  input_mode: "inline"
reserved: true

steps:
  - id: clear-chat
    type: notify
    with:
      message: "🧹 Chat cleared"
      level: "info"
      response_data:
        shouldResetChat: true
        type: "system"
```

### **3. Testing Strategy**

#### **Parallel Testing**
During migration, run both systems side-by-side:
```php
// In CommandController, test both implementations
$hardcodedResult = $this->executeHardcoded($command);
$yamlResult = $this->executeYaml($command);
$this->compareResults($hardcodedResult, $yamlResult);
```

#### **Automated Validation**
Create test suite for functional parity:
```php
class CommandMigrationTest extends TestCase
{
    public function test_clear_command_parity()
    {
        // Test both implementations produce same result
    }
    
    public function test_help_command_parity()
    {
        // Verify help output matches
    }
}
```

### **4. Conflict Resolution Detailed Plan**

#### **`recall` Command Unification**
```yaml
name: "Recall Fragments"
slug: recall
version: 2.0.0
triggers:
  slash: "/recall"
  aliases: []
  input_mode: "inline"

steps:
  - id: determine-action
    type: condition
    condition: "{{ ctx.type | length > 0 }}"
    then:
      # Query existing fragments (hardcoded behavior)
      - id: query-fragments
        type: fragment.query
        with:
          type: "{{ ctx.type }}"
          limit: "{{ ctx.limit | default: 5 }}"
      - id: show-results
        type: response.panel
        with:
          type: "recall"
          panel_data:
            type: "{{ ctx.type }}"
            fragments: "{{ steps.query-fragments.output.results }}"
    else:
      # Create recall fragment (YAML behavior)
      - id: create-recall
        type: fragment.create
        with:
          type: "log"
          content: "{{ ctx.body | default: ctx.selection }}"
          tags: ["recall"]
      - id: notify-created
        type: response.toast
        with:
          message: "✅ Recall created successfully"
          type: "success"
```

#### **`todo` Command Unification**
```yaml
name: "Todo Management"
slug: todo
version: 2.0.0
triggers:
  slash: "/todo"
  aliases: ["/t"]
  input_mode: "inline"

steps:
  - id: parse-action
    type: condition
    condition: "{{ ctx.identifier == 'list' or ctx.status | length > 0 }}"
    then:
      # List functionality (hardcoded behavior)
      - id: query-todos
        type: fragment.query
        with:
          type: "todo"
          filters:
            state.status: "{{ ctx.status | default: 'open' }}"
          search: "{{ ctx.search }}"
          tags: "{{ ctx.tags }}"
          limit: "{{ ctx.limit | default: 25 }}"
      - id: show-todos
        type: response.panel
        with:
          type: "recall"
          panel_data:
            type: "todo"
            fragments: "{{ steps.query-todos.output.results }}"
    else:
      # Creation functionality (enhanced with AI)
      - id: parse-todo-content
        type: ai.generate
        prompt: |
          Parse this todo request and extract structured data:
          User input: {{ ctx.identifier }}
        expect: json
      - id: create-todo
        type: fragment.create
        with:
          type: "todo"
          title: "{{ steps.parse-todo-content.output.title }}"
          content: "{{ ctx.identifier }}"
          state:
            status: "open"
            priority: "{{ steps.parse-todo-content.output.priority | default: 'medium' }}"
          tags: ["todo"]
      - id: notify-created
        type: response.toast
        with:
          message: "✅ Todo created: {{ steps.parse-todo-content.output.title }}"
          type: "success"
```

### **5. Implementation Timeline**

#### **Week 1: Foundation (ENG-08-02 Start)**
- Day 1-2: Implement core DSL extensions (`fragment.query`, `fragment.update`, `condition`)
- Day 3: Enhance template engine with expression evaluation
- Day 4: Implement `response.panel` step type
- Day 5: Migrate `clear` and `help` commands

#### **Week 2: Simple Commands (ENG-08-02 Complete)**
- Day 1: Migrate `name` command
- Day 2-3: Implement state management (`state.get`, `state.set`)
- Day 4: Migrate `session` command (simplified version)
- Day 5: Migrate `bookmark` command

#### **Week 3: Medium Commands (ENG-08-03 Start)**
- Day 1-2: Migrate `frag`, `join`, `channels`, `routing` commands
- Day 3: Implement advanced DSL features (`loop`, enhanced error handling)
- Day 4-5: Begin complex command analysis (`vault`, `project`, `context`)

#### **Week 4: Complex & Conflicts (ENG-08-03 Complete)**
- Day 1-2: Migrate complex commands
- Day 3-4: Resolve command conflicts (unified implementations)
- Day 5: Integration testing and validation

#### **Week 5: Cleanup (ENG-08-04)**
- Day 1-2: Remove hardcoded system
- Day 3: Performance optimization
- Day 4: Documentation updates
- Day 5: Final testing and deployment preparation

## Success Metrics

### **Functional Requirements**
- ✅ All 18 commands migrated to YAML DSL
- ✅ No functionality regression
- ✅ Consistent command response patterns
- ✅ Proper error handling

### **Performance Requirements**
- ⚡ Command execution time < 200ms (same as current)
- 🧠 Memory usage within 10% of current system
- 📊 Database queries optimized or equivalent

### **Quality Requirements**  
- 🧪 100% test coverage for new DSL components
- 📝 Complete documentation for unified system
- 🔧 Maintainable and extensible architecture
- 🚀 Easy command addition process

### **User Experience Requirements**
- 🎯 Identical command behavior for users
- 📱 Consistent UI responses and feedback
- ⚠️ Clear error messages and validation
- 🔄 Smooth migration with no downtime

## Risk Mitigation

### **Technical Risks**
1. **Performance degradation** - Mitigation: Parallel testing and optimization
2. **Functionality gaps** - Mitigation: Comprehensive analysis and incremental approach
3. **Complex logic migration** - Mitigation: Enhanced DSL framework first

### **Project Risks**
1. **Timeline overrun** - Mitigation: Phase-based approach with clear milestones
2. **Scope creep** - Mitigation: Fixed command list and well-defined requirements
3. **Integration issues** - Mitigation: Side-by-side testing and gradual rollout

### **User Impact Risks**
1. **Breaking changes** - Mitigation: Backward compatibility and careful testing
2. **Performance issues** - Mitigation: Load testing and monitoring
3. **Feature regression** - Mitigation: Comprehensive functional testing

## Rollback Plan

### **Per-Command Rollback**
- Keep hardcoded commands until YAML equivalent is fully validated
- Feature flags for gradual rollout
- Quick switch-back capability in CommandController

### **Full System Rollback**
- Git branch strategy with clear rollback points
- Database migration rollback scripts
- Configuration rollback procedures

This migration plan provides a systematic approach to unifying the command system while minimizing risk and ensuring quality outcomes.