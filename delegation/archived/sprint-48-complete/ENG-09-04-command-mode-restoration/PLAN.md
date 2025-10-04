# Command Mode Restoration - Implementation Plan

## 📅 Project Timeline

**Total Estimated Time**: 16-22 hours (2-3 days)
**Critical Path**: Framework fixes → Bookmark → Join → Others
**Dependencies**: Sprint 47 completion (✅ Done)

---

## 🎯 Phase 1: Framework Investigation & Fixes
**Duration**: 4-6 hours | **Priority**: Critical

### **Phase 1.1: Condition Step Analysis** (1-2h)
**Objective**: Understand and fix template rendering in condition steps

**Tasks**:
- Investigate why `condition: "{{ ctx.body == 'list' }}"` fails
- Trace template rendering flow in CommandRunner → ConditionStep
- Identify exact failure point in template to condition processing
- Document current behavior vs expected behavior

**Success Criteria**:
- Clear understanding of condition step template bug
- Reproducible test case demonstrating the issue

### **Phase 1.2: Framework Fix Implementation** (2-3h)
**Objective**: Fix condition step template rendering

**Tasks**:
- Implement fix for template rendering in condition steps
- Ensure step references like `{{ steps.input.output == 'list' }}` work
- Add support for complex template expressions in conditions
- Test framework fixes with simple command examples

**Success Criteria**:
- Condition steps work with template expressions
- Both context and step references functional in conditions
- No regressions in existing condition step functionality

### **Phase 1.3: Enhanced Template Filters** (1h)
**Objective**: Add argument parsing template filters

**Tasks**:
- Add `startswith`, `contains`, `substring` filters to template engine
- Create `match` filter for regex pattern matching
- Add `split` and `extract` filters for argument parsing
- Test filters with various input patterns

**Success Criteria**:
- Rich set of string manipulation filters available
- Filters handle edge cases (null, empty strings) gracefully
- Documentation updated with filter examples

---

## 🎯 Phase 2: Command Mode Implementation
**Duration**: 10-14 hours | **Priority**: High

### **Phase 2.1: Bookmark Command Restoration** (2-3h)
**Objective**: Implement all bookmark modes with database operations

**Current Issue**: Only creates bookmarks, missing list/show/forget modes
**Approach**: Use fixed condition steps + fragment.query for bookmark operations

**Tasks**:
1. **Argument Parsing** (30min)
   - Implement condition logic to detect `list`, `show <name>`, `forget <name>` patterns
   - Handle edge cases like empty input, malformed arguments

2. **List Mode Implementation** (45min)
   - Query all bookmarks using fragment.query
   - Format bookmark list with metadata (fragment count, dates)
   - Return response.panel with bookmark listing

3. **Show Mode Implementation** (45min)
   - Parse bookmark name from `show <name>` pattern
   - Query specific bookmark by name (with partial matching)
   - Display bookmark details with associated fragments
   - Handle "bookmark not found" error case

4. **Forget Mode Implementation** (30min)
   - Parse bookmark name from `forget <name>` pattern  
   - Find bookmark by name (partial matching)
   - Delete bookmark and show confirmation
   - Handle "bookmark not found" error case

**Success Criteria**:
- `/bookmark` creates bookmark (existing functionality preserved)
- `/bookmark list` shows all bookmarks with proper formatting
- `/bookmark show project` finds and displays bookmark containing "project"
- `/bookmark forget old-bookmark` deletes bookmark with confirmation
- All error cases handled gracefully with appropriate messages

### **Phase 2.2: Join Command Restoration** (3-4h)
**Objective**: Implement channel joining with search and navigation

**Current Issue**: Only shows help, missing all join functionality
**Approach**: Complex argument parsing + channel search + navigation responses

**Tasks**:
1. **Argument Pattern Detection** (1h)
   - Detect empty input → show help
   - Detect `#` pattern → list all channels
   - Detect `#c5` pattern → direct channel join
   - Detect `#custom` pattern → join by custom name
   - Detect search query → search channels

2. **Channel Operations** (1h)
   - Implement channel listing (all channels)
   - Implement direct channel join by short code
   - Implement channel join by custom name
   - Handle channel not found errors

3. **Search Functionality** (1h)
   - Implement channel search by query
   - Handle single result (auto-join) vs multiple results (show options)
   - Format search results for user selection

4. **Response Integration** (30min)
   - Generate proper response.panel data for channel lists
   - Create navigation responses for successful joins
   - Implement toast notifications for feedback

**Success Criteria**:
- `/join` shows comprehensive help with examples
- `/join #` lists all available channels
- `/join #c5` joins channel c5 directly (if exists)
- `/join project` searches and shows channels containing "project"
- Single search results trigger auto-join, multiple show selection
- All response types maintain frontend compatibility

### **Phase 2.3: Channels Command Enhancement** (1-2h)
**Objective**: Replace static content with dynamic channel data

**Current Issue**: Shows hardcoded example channels
**Approach**: Database query + real channel formatting

**Tasks**:
1. **Dynamic Channel Query** (45min)
   - Replace static content with fragment.query or direct database access
   - Query active channels with metadata (activity, member count)
   - Handle empty channel list case

2. **Rich Channel Display** (45min)
   - Format channels with real activity timestamps
   - Include channel metadata (member count, last activity)
   - Maintain existing response.panel structure
   - Add helpful usage instructions

**Success Criteria**:
- Shows real channel data instead of static examples
- Includes accurate activity timestamps and metadata
- Handles empty channel list gracefully
- Maintains existing UI integration

### **Phase 2.4: Session Command Restoration** (2-3h)
**Objective**: Implement full session lifecycle management

**Current Issue**: Only shows static session info
**Approach**: Session operations + dynamic session data

**Tasks**:
1. **Session Mode Detection** (30min)
   - Parse `show`, `list`, `start`, `end` arguments
   - Handle empty input (default to current session status)

2. **Session Information Display** (45min)
   - Show current session with real data
   - Display session metadata (vault, type, tags, started time)
   - Format session list with activity and status

3. **Session Operations** (1h)
   - Implement session start functionality
   - Implement session end functionality  
   - Handle session state transitions and validation

4. **Error Handling** (30min)
   - Handle "no active session" cases
   - Validate session operations (can't end non-existent session)
   - Provide helpful error messages and guidance

**Success Criteria**:
- `/session` shows current session status with real data
- `/session show` provides detailed session information
- `/session list` shows recent session history
- `/session start` creates new session (if possible)
- `/session end` terminates current session with confirmation
- All error cases handled with helpful guidance

### **Phase 2.5: Routing Command Implementation** (1-2h)
**Objective**: Add basic routing rule management

**Current Issue**: Only shows placeholder information
**Approach**: Routing service integration + management UI

**Tasks**:
1. **Routing Information Display** (1h)
   - Query current routing rules
   - Display routing configuration
   - Show vault and project routing setup

2. **Management Interface** (1h)
   - Provide routing rule management options
   - Link to full routing configuration UI
   - Handle routing service integration

**Success Criteria**:
- Shows actual routing rules and configuration
- Provides access to routing management functionality
- Maintains integration with existing routing service

---

## 🎯 Phase 3: Testing & Validation
**Duration**: 2-3 hours | **Priority**: High

### **Phase 3.1: Comprehensive Testing** (1-2h)
**Objective**: Validate all command modes and edge cases

**Tasks**:
- Test all argument patterns for each command
- Verify error handling for malformed inputs
- Test edge cases (empty inputs, special characters, long arguments)
- Validate response data structures match frontend expectations
- Performance test to ensure no regressions

### **Phase 3.2: Documentation Updates** (1h)
**Objective**: Update documentation with complete usage examples

**Tasks**:
- Update README files for each command
- Add comprehensive usage examples
- Document new conditional patterns for future use
- Update test samples to cover all modes

---

## 🚨 Risk Mitigation

### **High Risk: Condition Step Framework Issues**
**Risk**: Template rendering in conditions may require deep framework changes
**Mitigation**: 
- Start with framework investigation immediately
- Have fallback plans using alternative conditional patterns
- Consider workarounds if framework fixes prove too complex

### **Medium Risk: Database Access Limitations**
**Risk**: fragment.query may not support all required database operations
**Mitigation**:
- Analyze database query requirements early
- Consider extending DSL framework with new step types if needed
- Plan for gradual feature restoration if some operations aren't possible

### **Low Risk: Frontend Integration Changes**
**Risk**: Enhanced command responses may not integrate properly
**Mitigation**:
- Maintain existing response data structures
- Test frontend integration thoroughly
- Coordinate with frontend team if changes needed

---

## 📊 Success Metrics

### **Functional Completeness**
- ✅ All original command modes restored and functional
- ✅ No regressions in existing DSL command functionality
- ✅ Error handling matches or exceeds original command quality

### **Performance Standards**
- ✅ Command execution time remains <10ms for non-database operations
- ✅ Database operations optimized and properly indexed
- ✅ Template processing performance maintained or improved

### **User Experience**
- ✅ All original user workflows continue to work
- ✅ Error messages are helpful and actionable
- ✅ Response formatting maintains existing UI integration

### **Code Quality**
- ✅ Clean, maintainable YAML command definitions
- ✅ Reusable conditional patterns documented
- ✅ Comprehensive test coverage for all modes
- ✅ Updated documentation with complete examples