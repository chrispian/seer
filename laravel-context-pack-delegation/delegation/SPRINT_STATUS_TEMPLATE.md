# {PROJECT_NAME} - Sprint Status Dashboard

## 📊 Current Sprint Overview

**Active Sprint**: {CURRENT_SPRINT_STATUS}  
**Sprint Focus**: {CURRENT_SPRINT_FOCUS}  
**Start Date**: {CURRENT_SPRINT_START}  
**Target Completion**: {CURRENT_SPRINT_TARGET}  

## 🎯 Sprint Priorities

### Sprint 001: {SPRINT_001_TITLE}
**Status**: `{SPRINT_001_STATUS}` | **Estimated**: {SPRINT_001_DURATION} | **Priority**: `{SPRINT_001_PRIORITY}`

**Objective**: {SPRINT_001_OBJECTIVE}

**Key Focus Areas**:
- {SPRINT_001_FOCUS_1}
- {SPRINT_001_FOCUS_2}
- {SPRINT_001_FOCUS_3}
- {SPRINT_001_FOCUS_4}
- {SPRINT_001_FOCUS_5}

### Sprint 002: {SPRINT_002_TITLE}
**Status**: `{SPRINT_002_STATUS}` | **Estimated**: {SPRINT_002_DURATION} | **Priority**: `{SPRINT_002_PRIORITY}`

**Objective**: {SPRINT_002_OBJECTIVE}

**Dependencies**: {SPRINT_002_DEPENDENCIES}

### Sprint 003: {SPRINT_003_TITLE}  
**Status**: `{SPRINT_003_STATUS}` | **Estimated**: {SPRINT_003_DURATION} | **Priority**: `{SPRINT_003_PRIORITY}`

**Objective**: {SPRINT_003_OBJECTIVE}

**Dependencies**: {SPRINT_003_DEPENDENCIES}

### Sprint 004: {SPRINT_004_TITLE}
**Status**: `{SPRINT_004_STATUS}` | **Estimated**: {SPRINT_004_DURATION} | **Priority**: `{SPRINT_004_PRIORITY}`

**Objective**: {SPRINT_004_OBJECTIVE}

**Dependencies**: {SPRINT_004_DEPENDENCIES}

### Sprint 005: {SPRINT_005_TITLE}
**Status**: `{SPRINT_005_STATUS}` | **Estimated**: {SPRINT_005_DURATION} | **Priority**: `{SPRINT_005_PRIORITY}`

**Objective**: {SPRINT_005_OBJECTIVE}

**Dependencies**: {SPRINT_005_DEPENDENCIES}

## 👥 Agent Allocation

**Available Agents**: {ACTIVE_AGENT_COUNT} active  
**Agent Templates**: {AVAILABLE_TEMPLATES} specialized roles available

**Ready Agent Templates**:
- `backend-engineer` - {BACKEND_AGENT_DESCRIPTION}
- `frontend-engineer` - {FRONTEND_AGENT_DESCRIPTION}
- `project-manager` - Coordination and task management
- `qa-engineer` - Testing and quality validation
- `ux-designer` - User experience and interface design

**Active Agents**:
- {ACTIVE_AGENT_1} - {AGENT_1_STATUS} - Working on: {AGENT_1_TASK}
- {ACTIVE_AGENT_2} - {AGENT_2_STATUS} - Working on: {AGENT_2_TASK}
- {ACTIVE_AGENT_3} - {AGENT_3_STATUS} - Working on: {AGENT_3_TASK}

## 📋 Task Status Tracking

### Sprint {CURRENT_SPRINT_NUM} Tasks
**Total Tasks**: {TOTAL_TASKS} | **Completed**: {COMPLETED_TASKS} | **In Progress**: {IN_PROGRESS_TASKS} | **Todo**: {TODO_TASKS}

| Task ID | Title | Agent | Status | Priority | Est. Hours |
|---------|-------|-------|--------|----------|------------|
| {TASK_1_ID} | {TASK_1_TITLE} | {TASK_1_AGENT} | `{TASK_1_STATUS}` | {TASK_1_PRIORITY} | {TASK_1_HOURS} |
| {TASK_2_ID} | {TASK_2_TITLE} | {TASK_2_AGENT} | `{TASK_2_STATUS}` | {TASK_2_PRIORITY} | {TASK_2_HOURS} |
| {TASK_3_ID} | {TASK_3_TITLE} | {TASK_3_AGENT} | `{TASK_3_STATUS}` | {TASK_3_PRIORITY} | {TASK_3_HOURS} |
| {TASK_4_ID} | {TASK_4_TITLE} | {TASK_4_AGENT} | `{TASK_4_STATUS}` | {TASK_4_PRIORITY} | {TASK_4_HOURS} |
| {TASK_5_ID} | {TASK_5_TITLE} | {TASK_5_AGENT} | `{TASK_5_STATUS}` | {TASK_5_PRIORITY} | {TASK_5_HOURS} |

**Task Status Legend**:
- `todo` - Ready to start, not yet assigned
- `in-progress` - Currently being worked on
- `review` - Completed, pending review/integration
- `done` - Completed and integrated
- `blocked` - Waiting on dependencies or decisions

## 🔄 Development Workflow Status

### Git Worktrees
**Status**: `{WORKTREE_STATUS}`  
**Available Environments**: {ACTIVE_WORKTREES}  
**Setup Command**: `./delegation/setup-worktree.sh {CURRENT_SPRINT_NUM}`

**Active Worktrees**:
- `{PROJECT_NAME}-backend-sprint{CURRENT_SPRINT_NUM}` - {BACKEND_WORKTREE_STATUS}
- `{PROJECT_NAME}-frontend-sprint{CURRENT_SPRINT_NUM}` - {FRONTEND_WORKTREE_STATUS}
- `{PROJECT_NAME}-integration-sprint{CURRENT_SPRINT_NUM}` - {INTEGRATION_WORKTREE_STATUS}

### CI/CD Pipeline
**Status**: `{PIPELINE_STATUS}`  
**Testing Matrix**: {TESTING_MATRIX}  
**Coverage Target**: {COVERAGE_TARGET}

### Quality Standards
**Code Standards Compliance**: {CODE_STANDARDS_STATUS}  
**Test Coverage**: {TEST_COVERAGE_CURRENT}  
**Documentation Coverage**: {DOCS_COVERAGE_CURRENT}

## ⚠️ Blockers and Dependencies

**Current Blockers**: 
- {BLOCKER_1} - {BLOCKER_1_DESCRIPTION} - Assigned to: {BLOCKER_1_OWNER}
- {BLOCKER_2} - {BLOCKER_2_DESCRIPTION} - Assigned to: {BLOCKER_2_OWNER}

**External Dependencies**: 
- {DEPENDENCY_1} - {DEPENDENCY_1_STATUS}
- {DEPENDENCY_2} - {DEPENDENCY_2_STATUS}
- {DEPENDENCY_3} - {DEPENDENCY_3_STATUS}

**Resolved This Sprint**:
- {RESOLVED_1} - {RESOLVED_1_DATE}
- {RESOLVED_2} - {RESOLVED_2_DATE}

## 📈 Progress Metrics

**Overall Progress**: {OVERALL_PROGRESS_PERCENT}% ({COMPLETED_SPRINTS}/{TOTAL_SPRINTS} sprints completed)  
**Current Sprint Progress**: {CURRENT_SPRINT_PROGRESS}% ({COMPLETED_TASKS}/{TOTAL_TASKS} tasks completed)  
**Quality Gates Passed**: {QUALITY_GATES_PASSED}/{QUALITY_GATES_TOTAL} defined standards  
**Test Coverage**: {TEST_COVERAGE_PERCENT}% (Target: {TEST_COVERAGE_TARGET}%)

**Sprint Velocity**:
- Sprint {PREV_SPRINT_NUM}: {PREV_SPRINT_VELOCITY} tasks completed
- Sprint {CURRENT_SPRINT_NUM}: {CURRENT_SPRINT_VELOCITY} tasks completed (projected)
- Average Velocity: {AVERAGE_VELOCITY} tasks per sprint

**Risk Indicators**:
- 🟢 **Low Risk**: {LOW_RISK_COUNT} items
- 🟡 **Medium Risk**: {MEDIUM_RISK_COUNT} items  
- 🔴 **High Risk**: {HIGH_RISK_COUNT} items

## 🎯 Next Steps

### Immediate Actions Needed:
1. **{ACTION_1}**: {ACTION_1_DESCRIPTION}
2. **{ACTION_2}**: {ACTION_2_DESCRIPTION}
3. **{ACTION_3}**: {ACTION_3_DESCRIPTION}
4. **{ACTION_4}**: {ACTION_4_DESCRIPTION}
5. **{ACTION_5}**: {ACTION_5_DESCRIPTION}

### Upcoming Sprint Planning:
- **Sprint {NEXT_SPRINT_NUM}**: {NEXT_SPRINT_PLANNING_STATUS}
- **Focus Area**: {NEXT_SPRINT_FOCUS}
- **Estimated Start**: {NEXT_SPRINT_START}

### Resource Allocation Needs:
- {RESOURCE_NEED_1}
- {RESOURCE_NEED_2}
- {RESOURCE_NEED_3}

## 📝 Recent Updates

### {UPDATE_DATE_1}
- {UPDATE_1_ITEM_1}
- {UPDATE_1_ITEM_2}
- {UPDATE_1_ITEM_3}

### {UPDATE_DATE_2}
- {UPDATE_2_ITEM_1}
- {UPDATE_2_ITEM_2}
- {UPDATE_2_ITEM_3}

### {UPDATE_DATE_3}
- {UPDATE_3_ITEM_1}
- {UPDATE_3_ITEM_2}
- {UPDATE_3_ITEM_3}

## 🔍 Sprint Retrospective (Previous Sprint)

### What Went Well:
- {RETRO_POSITIVE_1}
- {RETRO_POSITIVE_2}
- {RETRO_POSITIVE_3}

### What Could Be Improved:
- {RETRO_IMPROVEMENT_1}
- {RETRO_IMPROVEMENT_2}
- {RETRO_IMPROVEMENT_3}

### Action Items for Next Sprint:
- {RETRO_ACTION_1}
- {RETRO_ACTION_2}
- {RETRO_ACTION_3}

---

**Last Updated**: {LAST_UPDATE_DATE}  
**Next Review**: {NEXT_REVIEW_DATE}  
**Status Reporter**: {STATUS_REPORTER}

---

**Template Instructions for Agents**:
When using this template, replace all `{PLACEHOLDER}` values with actual project data:

- `{PROJECT_NAME}` - Actual project name
- `{SPRINT_XXX_TITLE}` - Real sprint names and objectives
- `{TASK_X_ID}` - Actual task identifiers and details
- `{AGENT_X}` - Real agent names and assignments
- `{STATUS}` values - Current actual statuses
- `{PERCENTAGE}` - Real progress percentages
- All date placeholders - Actual dates
- All descriptive placeholders - Real project information

Remove this template instructions section when creating the actual SPRINT_STATUS.md.