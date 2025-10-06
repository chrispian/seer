# Command Mode Restoration - Implementation Checklist

## 📋 Phase 1: Framework Investigation & Fixes

### **1.1 Condition Step Analysis** ⏳
- [ ] **Investigate condition template bug** (45min)
  - [ ] Create minimal reproduction case with failing template condition
  - [ ] Trace code path: CommandRunner.renderStepConfig() → ConditionStep.execute()
  - [ ] Identify where template rendering fails for condition values
  - [ ] Document exact failure point and error messages

- [ ] **Analyze template engine integration** (30min)
  - [ ] Check if TemplateEngine.render() is being called on condition strings
  - [ ] Verify context is properly passed to template rendering
  - [ ] Test if step references work in conditions: `{{ steps.input.output == 'list' }}`

- [ ] **Document current vs expected behavior** (15min)
  - [ ] Create test cases showing what should work
  - [ ] Document workarounds currently used (literal conditions)
  - [ ] Identify impact on command complexity

### **1.2 Framework Fix Implementation** ⏳
- [ ] **Fix condition template rendering** (2h)
  - [ ] Ensure CommandRunner properly renders condition strings before passing to ConditionStep
  - [ ] Verify ConditionStep receives rendered condition, not template
  - [ ] Test with both context variables (`ctx.body`) and step references (`steps.input.output`)
  - [ ] Add error handling for invalid template expressions in conditions

- [ ] **Test framework fixes** (45min)
  - [ ] Create test command with working template conditions
  - [ ] Test edge cases: null values, empty strings, complex expressions
  - [ ] Verify no regressions in existing condition step functionality
  - [ ] Run full command test suite to ensure compatibility

- [ ] **Add debugging support** (15min)
  - [ ] Add logging for condition template rendering process
  - [ ] Include rendered condition values in error messages
  - [ ] Add validation warnings for common template mistakes

### **1.3 Enhanced Template Filters** ⏳
- [ ] **Add string manipulation filters** (45min)
  - [ ] `startswith` filter: `{{ ctx.body | startswith: 'show' }}`
  - [ ] `contains` filter: `{{ ctx.body | contains: 'forget' }}`
  - [ ] `substring` filter: `{{ ctx.body | substring: 5, 10 }}`
  - [ ] `split` filter: `{{ ctx.body | split: ' ' }}`

- [ ] **Add pattern matching filters** (15min)
  - [ ] `match` filter for regex: `{{ ctx.body | match: '^#\w+$' }}`
  - [ ] `extract` filter: `{{ ctx.body | extract: 'show (.+)' }}`

- [ ] **Test new filters** (15min)
  - [ ] Test all filters with various input types
  - [ ] Handle null/empty string edge cases
  - [ ] Update template engine tests

---

## 📋 Phase 2: Command Mode Implementation

### **2.1 Bookmark Command Restoration** ⏳

#### **Setup and Argument Parsing**
- [ ] **Update bookmark command structure** (30min)
  - [ ] Add argument parsing step: extract user input and detect mode
  - [ ] Create condition logic to route between modes: create/list/show/forget
  - [ ] Test argument detection with sample inputs

#### **List Mode Implementation**
- [ ] **Implement bookmark listing** (45min)
  - [ ] Add fragment.query step to fetch all bookmarks
  - [ ] Create template to format bookmark list with metadata
  - [ ] Handle empty bookmark list case
  - [ ] Test bookmark list display in response.panel

#### **Show Mode Implementation**  
- [ ] **Implement bookmark display** (45min)
  - [ ] Parse bookmark name from "show <name>" pattern
  - [ ] Query specific bookmark with partial name matching
  - [ ] Format bookmark details with associated fragments
  - [ ] Handle "bookmark not found" error case
  - [ ] Test with various bookmark name patterns

#### **Forget Mode Implementation**
- [ ] **Implement bookmark deletion** (30min)
  - [ ] Parse bookmark name from "forget <name>" pattern
  - [ ] Find bookmark by partial name matching
  - [ ] Delete bookmark and show confirmation message
  - [ ] Handle deletion errors and "not found" cases
  - [ ] Test deletion workflow and confirmations

#### **Testing and Validation**
- [ ] **Test all bookmark modes** (30min)
  - [ ] Test `/bookmark` (create) - existing functionality
  - [ ] Test `/bookmark list` with various bookmark states
  - [ ] Test `/bookmark show <name>` with partial matching
  - [ ] Test `/bookmark forget <name>` with confirmations
  - [ ] Update sample files with comprehensive test cases

### **2.2 Join Command Restoration** ⏳

#### **Argument Pattern Detection**
- [ ] **Implement input parsing** (1h)
  - [ ] Detect empty input → help mode
  - [ ] Detect `#` → list all channels mode
  - [ ] Detect `#c5` pattern → direct channel join mode
  - [ ] Detect `#custom` pattern → custom name join mode
  - [ ] Detect search query → channel search mode
  - [ ] Test pattern detection with edge cases

#### **Channel Operations**
- [ ] **Implement channel listing** (30min)
  - [ ] Add step to query all available channels
  - [ ] Format channel list with short codes and names
  - [ ] Handle empty channel list case

- [ ] **Implement direct channel join** (30min)
  - [ ] Parse channel identifier from `#c5` pattern
  - [ ] Find channel by short code or custom name
  - [ ] Generate navigation response for successful join
  - [ ] Handle "channel not found" error

#### **Search Functionality**
- [ ] **Implement channel search** (1h)
  - [ ] Query channels matching search term
  - [ ] Handle single result → auto-join behavior
  - [ ] Handle multiple results → show selection list
  - [ ] Format search results for user selection
  - [ ] Test search with various query patterns

#### **Response Integration**
- [ ] **Create proper response formats** (30min)
  - [ ] Generate response.panel data for channel lists
  - [ ] Create navigation responses for channel joins
  - [ ] Add success/error toast notifications
  - [ ] Maintain frontend compatibility

#### **Testing and Validation**
- [ ] **Test all join modes** (30min)
  - [ ] Test `/join` help display
  - [ ] Test `/join #` channel listing
  - [ ] Test `/join #c5` direct channel join
  - [ ] Test `/join project` channel search
  - [ ] Update sample files and documentation

### **2.3 Channels Command Enhancement** ⏳

#### **Dynamic Channel Query**
- [ ] **Replace static content** (45min)
  - [ ] Remove hardcoded channel examples
  - [ ] Add fragment.query or database step for real channels
  - [ ] Query channel metadata (activity, member count)
  - [ ] Handle empty channel list gracefully

#### **Rich Channel Display**
- [ ] **Format dynamic channel data** (45min)
  - [ ] Display real activity timestamps
  - [ ] Include channel metadata in listings
  - [ ] Maintain existing response.panel structure
  - [ ] Add usage instructions and helpful tips

#### **Testing and Validation**
- [ ] **Test channel display** (15min)
  - [ ] Verify real channel data appears
  - [ ] Test with empty channel list
  - [ ] Validate UI integration
  - [ ] Update sample files

### **2.4 Session Command Restoration** ⏳

#### **Session Mode Detection**
- [ ] **Implement argument parsing** (30min)
  - [ ] Parse `show`, `list`, `start`, `end` arguments
  - [ ] Handle empty input (default to current session status)
  - [ ] Create condition logic for mode routing

#### **Session Information Display**
- [ ] **Implement session status** (45min)
  - [ ] Show current session with real data
  - [ ] Display vault, type, tags, started time
  - [ ] Format session metadata properly
  - [ ] Handle "no active session" case

- [ ] **Implement session listing** (30min)
  - [ ] Query recent session history
  - [ ] Format session list with activity
  - [ ] Include session status and metadata

#### **Session Operations**
- [ ] **Implement session lifecycle** (1h)
  - [ ] Add session start functionality
  - [ ] Add session end functionality
  - [ ] Handle session state transitions
  - [ ] Validate session operations

#### **Error Handling**
- [ ] **Add comprehensive error handling** (30min)
  - [ ] Handle "no active session" cases
  - [ ] Validate session operations
  - [ ] Provide helpful error messages
  - [ ] Guide users on proper session commands

#### **Testing and Validation**
- [ ] **Test all session modes** (30min)
  - [ ] Test `/session` current status
  - [ ] Test `/session show` detailed info
  - [ ] Test `/session list` history
  - [ ] Test `/session start` and `/session end`
  - [ ] Update sample files

### **2.5 Routing Command Implementation** ⏳

#### **Routing Information Display**
- [ ] **Implement routing data query** (1h)
  - [ ] Query current routing rules
  - [ ] Display routing configuration
  - [ ] Show vault and project routing setup
  - [ ] Handle routing service integration

#### **Management Interface**
- [ ] **Add routing management** (1h)
  - [ ] Provide routing rule management options
  - [ ] Link to full routing configuration UI
  - [ ] Handle routing service integration
  - [ ] Maintain existing routing workflow

#### **Testing and Validation**
- [ ] **Test routing functionality** (15min)
  - [ ] Verify routing rules display
  - [ ] Test management interface links
  - [ ] Validate service integration

---

## 📋 Phase 3: Testing & Validation

### **3.1 Comprehensive Testing** ⏳

#### **Command Mode Testing**
- [ ] **Test all restored commands** (1h)
  - [ ] Bookmark: create, list, show, forget modes
  - [ ] Join: help, list, direct join, search modes
  - [ ] Channels: dynamic listing with real data
  - [ ] Session: status, show, list, start, end modes
  - [ ] Routing: information display and management

#### **Edge Case Testing**
- [ ] **Test error conditions** (30min)
  - [ ] Malformed inputs (special characters, empty args)
  - [ ] Non-existent resources (bookmarks, channels, sessions)
  - [ ] Permission and state validation errors
  - [ ] Network/database error conditions

#### **Performance Testing**
- [ ] **Validate performance standards** (30min)
  - [ ] Run benchmark on all commands
  - [ ] Ensure <10ms execution for non-database operations
  - [ ] Verify database operations are optimized
  - [ ] Check memory usage and template caching

#### **Integration Testing**
- [ ] **Test frontend compatibility** (30min)
  - [ ] Verify response.panel data structures
  - [ ] Test toast notification integration
  - [ ] Validate navigation responses
  - [ ] Check command palette integration

### **3.2 Documentation Updates** ⏳

#### **Command Documentation**
- [ ] **Update README files** (45min)
  - [ ] Bookmark command: all modes with examples
  - [ ] Join command: all argument patterns
  - [ ] Channels command: dynamic features
  - [ ] Session command: complete lifecycle
  - [ ] Routing command: management capabilities

#### **Sample File Updates**
- [ ] **Create comprehensive samples** (15min)
  - [ ] Add sample files for each command mode
  - [ ] Include edge cases and error conditions
  - [ ] Test all samples work correctly

#### **Pattern Documentation**
- [ ] **Document reusable patterns** (15min)
  - [ ] Conditional logic patterns for argument parsing
  - [ ] Template patterns for mode detection
  - [ ] Error handling patterns
  - [ ] Response formatting patterns

---

## ✅ Quality Checkpoints

### **Functional Validation**
- [ ] All original command modes work exactly as in hardcoded versions
- [ ] No regressions in existing DSL command functionality
- [ ] Error handling is comprehensive and user-friendly
- [ ] Response data structures maintain frontend compatibility

### **Performance Validation**
- [ ] Command execution times meet performance standards
- [ ] Database queries are optimized and efficient
- [ ] Template caching works correctly
- [ ] Memory usage is reasonable and stable

### **Code Quality**
- [ ] YAML command definitions are clean and maintainable
- [ ] Conditional patterns are reusable and well-documented
- [ ] Test coverage is comprehensive
- [ ] Documentation is complete and accurate

### **User Experience**
- [ ] All user workflows continue to work seamlessly
- [ ] Command help and guidance is clear and helpful
- [ ] Error messages provide actionable guidance
- [ ] UI integration works correctly

---

## 🎯 Completion Criteria

### **Phase 1 Complete When:**
- ✅ Condition steps work with template expressions
- ✅ Enhanced template filters are available and tested
- ✅ Framework changes are validated with no regressions

### **Phase 2 Complete When:**
- ✅ All five commands support their original modes
- ✅ Database operations work correctly
- ✅ Response formats maintain frontend compatibility

### **Phase 3 Complete When:**
- ✅ All commands pass comprehensive testing
- ✅ Documentation is updated and accurate
- ✅ Performance standards are met

### **Task Complete When:**
- ✅ Users can use `/bookmark list`, `/join #c5`, `/session start`, etc.
- ✅ No regressions in existing command functionality
- ✅ All original command capabilities are restored
- ✅ Code quality and documentation standards are met