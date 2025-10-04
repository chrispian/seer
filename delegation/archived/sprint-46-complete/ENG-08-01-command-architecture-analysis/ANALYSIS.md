# Command Architecture Analysis

## Current State Overview

The system currently operates with a **dual command architecture**:

### 1. Hardcoded Commands (Legacy System)
- **Location**: `app/Actions/Commands/*.php` + `app/Services/CommandRegistry.php`
- **Count**: 18 hardcoded command classes
- **Execution**: Direct PHP class instantiation via `CommandController.php`
- **Features**: Full access to Laravel ecosystem, complex logic, direct database access

### 2. YAML DSL Commands (New System)
- **Location**: `fragments/commands/*/command.yaml` + DSL framework
- **Count**: 15 YAML command definitions
- **Execution**: DSL CommandRunner with step-based pipeline
- **Features**: Declarative, templated, limited but extensible step types

## Command Inventory & Conflicts

### **DIRECT CONFLICTS** (Same slug in both systems)
1. **`recall`** - Both hardcoded class and YAML file exist
   - **Hardcoded**: Complex query with type/limit filtering, panel display
   - **YAML**: Simple content creation with recall tag
   - **Behavior**: Different functionality, same name

2. **`todo`** - Both hardcoded class and YAML file exist  
   - **Hardcoded**: Full CRUD operations (create, list, complete, search)
   - **YAML**: Simple creation with AI parsing
   - **Behavior**: Overlapping but different capabilities

3. **`inbox`** - Both hardcoded class and YAML file exist
   - **Hardcoded**: Multiple views (pending, bookmarked, todos, all)
   - **YAML**: Simple API info display (inbox-api slug)
   - **Behavior**: Different functionality (actual vs. documentation)

4. **`search`** - Both hardcoded class and YAML file exist
   - **Hardcoded**: Advanced search with multiple filters
   - **YAML**: Basic search with simple query
   - **Behavior**: Different complexity levels

### **YAML-Only Commands** (15 total)
- `accept`, `inbox-ui`, `link`, `news-digest`, `note`, `remind`, `scheduler-ui`, `settings`, `setup`, `shell-test`, `types-ui`

### **Hardcoded-Only Commands** (14 remaining after conflicts)
- `session`, `bookmark`, `help`, `clear`, `frag`, `join`, `channels`, `name`, `routing`, `vault`, `project`, `context`, `compose`

## DSL Framework Capabilities Analysis

### **Available Step Types**
1. **`transform`** - Template rendering and data transformation
2. **`ai.generate`** - AI-powered content generation
3. **`fragment.create`** - Create fragments with metadata
4. **`search.query`** - Search functionality
5. **`notify`** - User notifications with panel support
6. **`tool.call`** - External tool execution

### **DSL Strengths**
- ✅ Declarative command definition
- ✅ Template engine with context interpolation
- ✅ Modular step-based execution
- ✅ Built-in error handling
- ✅ Dry run support
- ✅ Panel navigation support (via notify step)
- ✅ AI integration capabilities

### **DSL Gaps Identified**

#### **1. Complex Data Operations**
- **Missing**: Advanced database queries with complex WHERE clauses
- **Current Limitation**: Fragment creation only, no update/delete operations
- **Required For**: Session management, bookmarking, vault operations

#### **2. Response Type Diversity**
- **Missing**: Multiple response types (recall, inbox, system)
- **Current**: Only basic notify responses
- **Required For**: Panel data structures, toast notifications

#### **3. Conditional Logic**
- **Missing**: Complex branching and conditional execution
- **Current**: Linear step execution only
- **Required For**: Todo completion, help system, context commands

#### **4. State Management**
- **Missing**: Command-specific state persistence
- **Current**: No session or state tracking
- **Required For**: Session commands, routing commands

#### **5. Authentication & Security**
- **Missing**: User context and permissions
- **Current**: No built-in user awareness
- **Required For**: User-specific data operations

## Migration Complexity Assessment

### **Simple Migrations** (Low Complexity)
- ✅ `clear` - Simple action, minimal logic
- ✅ `help` - Static content display
- ✅ `name` - Simple text operations

### **Medium Migrations** (Medium Complexity)
- 🔄 `bookmark` - Requires fragment relationship operations
- 🔄 `frag` - Basic fragment operations with some complexity

### **Complex Migrations** (High Complexity)
- ❌ `session` - Complex state management
- ❌ `vault`/`project`/`context` - Advanced querying and filtering
- ❌ `compose` - Multi-step workflow management
- ❌ `join`/`channels`/`routing` - System-level operations

### **Conflict Resolution Required**
- 🔥 `recall`, `todo`, `inbox`, `search` - Need strategy for handling dual implementations

## Recommendations

### **Phase 1: Foundation Enhancement**
1. **Extend DSL Framework** with missing step types:
   - `fragment.query` - Advanced database operations
   - `fragment.update` - Fragment modification
   - `condition` - Conditional logic step
   - `state.get`/`state.set` - State management

2. **Enhance Response System** for multiple response types
3. **Add User Context** to execution environment

### **Phase 2: Simple Command Migration**
1. Start with `clear`, `help`, `name` (low complexity)
2. Validate DSL framework with real migrations
3. Refine tooling and patterns

### **Phase 3: Complex Command Analysis**
1. Detailed analysis of complex commands
2. DSL framework extensions for advanced use cases
3. Migration strategy refinement

### **Phase 4: Conflict Resolution**
1. Decide on unified behavior for conflicted commands
2. Merge or replace implementations
3. Ensure backward compatibility

## Next Steps

1. ✅ **Complete architecture analysis** (current task)
2. 🔄 **Document DSL gaps in detail** (next)
3. 📋 **Create specific migration plans** for each command
4. 🛠️ **Extend DSL framework** with required capabilities
5. 🚀 **Begin systematic migration** starting with simple commands

## Success Metrics

- **Target**: Single unified command system (YAML DSL only)
- **Quality**: No functionality regression
- **Performance**: Equivalent or better execution times
- **Maintainability**: Easier to add/modify commands
- **Consistency**: Uniform command definition pattern