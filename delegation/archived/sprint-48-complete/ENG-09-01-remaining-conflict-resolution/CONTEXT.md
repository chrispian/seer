# ENG-09-01: Remaining Conflict Resolution - Context

## Background
Sprint 46 successfully established a mature DSL framework and demonstrated effective conflict resolution with the `recall` command unification. Sprint 47 continues this success by resolving the remaining 3 command conflicts using proven patterns.

## Current Command Conflicts

### **1. `todo` Command Conflict**

#### **Hardcoded Version** (`app/Actions/Commands/TodoCommand.php`)
- **Full CRUD Operations**: Create, read, update, delete todos
- **Status Management**: Open, in progress, completed, cancelled states
- **Fragment Integration**: Link todos to fragments and contexts
- **Filtering and Search**: Advanced query capabilities
- **Batch Operations**: Multiple todo management

#### **YAML Version** (`fragments/commands/todo/command.yaml`)
- **Simple Creation**: AI-assisted todo generation from input
- **Basic Templating**: Response formatting and display
- **Minimal Workflow**: Single-step creation process

#### **Unification Strategy**
Create parameter-based mode selection:
- **Simple Mode**: Use existing YAML AI-assisted creation
- **Management Mode**: Full CRUD operations using enhanced DSL steps
- **Unified Interface**: Seamless transition between modes

### **2. `inbox` Command Conflict**

#### **Hardcoded Version** (`app/Actions/Commands/InboxCommand.php`)
- **Multi-View System**: Different inbox perspectives and filters
- **Item Management**: Process, archive, delete inbox items
- **Status Tracking**: Read/unread, priority, category management
- **Batch Processing**: Bulk operations on multiple items
- **Integration**: Connect with notification and fragment systems

#### **YAML Version** (`fragments/commands/inbox/command.yaml`)
- **API Documentation**: Inbox API reference and usage
- **Simple Display**: Basic inbox item listing
- **Documentation Focus**: Help and guidance content

#### **Unification Strategy**
Merge management and documentation:
- **Documentation Mode**: Preserve API reference functionality
- **Management Mode**: Full inbox operations using DSL
- **Contextual Help**: Integrate documentation within management interface

### **3. `search` Command Conflict**

#### **Hardcoded Version** (`app/Actions/Commands/SearchCommand.php`)
- **Advanced Filtering**: Complex query building and filters
- **Multi-Type Search**: Fragments, users, projects, contexts
- **Performance Optimization**: Efficient database queries
- **Result Formatting**: Rich search result display
- **Search History**: Previous search tracking

#### **YAML Version** (`fragments/commands/search/command.yaml`)
- **Basic Fragment Search**: Simple text-based fragment lookup
- **Template Display**: Basic result formatting
- **Simplified Interface**: Streamlined search experience

#### **Unification Strategy**
Enhance YAML with advanced capabilities:
- **Basic Mode**: Preserve simple search experience
- **Advanced Mode**: Add filtering and complex queries
- **Progressive Disclosure**: Start simple, expose advanced features as needed

## Technical Foundation from Sprint 46

### **Available DSL Steps for Conflict Resolution**
- **`condition`**: Mode selection and branching logic
- **`fragment.query`**: Advanced database queries with filters
- **`fragment.update`**: State and metadata management
- **`database.update`**: Direct CRUD operations
- **`validate`**: Input validation and error handling
- **`response.panel`**: UI panel responses for complex interfaces

### **Proven Unification Pattern**
From Sprint 46 `recall` command success:
1. **Parameter Analysis**: Detect mode based on input parameters
2. **Conditional Branching**: Use `condition` steps for mode routing
3. **Feature Preservation**: Maintain all original capabilities
4. **Enhanced UX**: Improve user experience through unification

### **Template Engine Capabilities**
- **Expression Evaluation**: Dynamic parameter processing
- **Control Structures**: Complex conditional logic
- **Advanced Filters**: Rich data transformation

## Implementation Approach

### **Phase 1: Feature Analysis & Design** (30 minutes)
- Document all features from both implementations
- Design unified workflows with parameter-based routing
- Plan DSL step utilization for complex operations

### **Phase 2: Implementation** (90-120 minutes)
- Create unified YAML commands using proven patterns
- Implement conditional branching for mode selection
- Add comprehensive error handling and validation

### **Phase 3: Testing & Validation** (60 minutes)
- Test all original features from both implementations
- Validate backward compatibility and user workflows
- Performance testing and optimization

## Expected Challenges

### **1. Feature Complexity Balance**
- **Challenge**: Advanced features may stress DSL capabilities
- **Mitigation**: Use proven DSL extensions from Sprint 46
- **Fallback**: Hybrid approach if pure DSL insufficient

### **2. User Experience Consistency**
- **Challenge**: Unified interface must not confuse existing users
- **Mitigation**: Parameter-based mode detection preserves workflows
- **Testing**: Comprehensive user workflow validation

### **3. Performance Considerations**
- **Challenge**: Unified commands may have complex execution paths
- **Mitigation**: Optimize DSL execution and database queries
- **Monitoring**: Benchmark against original implementations

## Success Metrics

### **Technical Success**
- [ ] 3 unified commands replace 6 conflicting implementations
- [ ] All original features preserved and functional
- [ ] Performance maintained or improved
- [ ] Zero functionality regression

### **User Experience Success**
- [ ] Backward compatibility maintained
- [ ] Mode selection intuitive and seamless
- [ ] Enhanced features discoverable but not overwhelming
- [ ] Documentation clear and comprehensive

### **Strategic Success**
- [ ] Conflict resolution pattern proven scalable
- [ ] DSL framework capability validated
- [ ] Team confidence in systematic approach
- [ ] Foundation ready for complex command migrations

## Risk Mitigation

### **Technical Risks**
- **DSL Limitations**: Enhanced framework from Sprint 46 reduces risk
- **Performance Impact**: Continuous benchmarking and optimization
- **Complexity Escalation**: Strict adherence to proven patterns

### **User Experience Risks**
- **Workflow Disruption**: Comprehensive backward compatibility testing
- **Feature Discovery**: Gradual progressive disclosure design
- **Documentation**: Clear migration guides and feature explanations

This task builds directly on Sprint 46's proven success, applying established patterns to complete the conflict resolution phase of command system unification.