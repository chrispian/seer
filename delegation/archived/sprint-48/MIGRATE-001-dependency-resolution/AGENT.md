# MIGRATE-001: Dependency Resolution & Task Update

## Agent Profile
**Type**: Project Coordination & Dependency Management Specialist  
**Role**: System Agent (Internal)  
**Mission**: Monitor and resolve sprint dependencies, then update all sprint tasks with accurate implementation details once Fragments Engine orchestration components are ready.

## Mission Statement
Serve as the coordination hub for Sprint 48, monitoring the completion of prerequisite engineering tasks and updating this sprint's implementation plan with accurate API specifications, MCP server endpoints, and agent orchestration capabilities once dependencies are resolved.

## Current Dependencies Status

### **Critical Dependencies** (Must Complete Before Proceeding)
- [ ] **ENG-09-01**: Tool SDK Foundation
  - **Status**: Planned for Sprint 47
  - **Provides**: Tool contracts, registry, scoping, telemetry middleware
  - **Required For**: All MCP integration work

- [ ] **ENG-09-04**: Agent Memory Foundation  
  - **Status**: Planned for Sprint 47
  - **Provides**: AgentNote, AgentDecision models, memory tools, vector search
  - **Required For**: Context storage and RAG integration

- [ ] **ENG-09-05**: Prompt Orchestrator
  - **Status**: Planned for Sprint 47
  - **Provides**: Dynamic prompt assembly, memory integration, A/B testing
  - **Required For**: System project manager agent implementation

- [ ] **UX-04-02**: Agent Manager System
  - **Status**: In Progress (Sprint 43)
  - **Provides**: Agent profiles, modes, avatars, scope resolution
  - **Required For**: Agent profile migration and enforcement

### **Supporting Dependencies** (Nice to Have)
- [ ] **Context Broker System**: Vault > Project > Chat > Message > Task context cascade
- [ ] **MCP Server Infrastructure**: Base MCP server implementations for FE
- [ ] **Fragments Engine Database Schema**: Updated with agent orchestration tables

## Primary Responsibilities

### **Dependency Monitoring**
1. **Weekly Dependency Check**: Monitor progress of ENG-09-* and UX-04-02 tasks
2. **Blocker Identification**: Identify and escalate any blockers affecting dependencies
3. **Readiness Assessment**: Evaluate when dependencies are sufficiently complete to proceed
4. **Communication**: Keep stakeholders informed of dependency status and timeline

### **Task Plan Updates**
1. **API Specification Updates**: Update task plans with actual MCP server endpoints and contracts
2. **Implementation Detail Refinement**: Refine implementation approaches based on actual tool capabilities
3. **Scope Adjustment**: Adjust task scope based on available functionality vs planned features
4. **Timeline Revision**: Update sprint timeline based on dependency completion dates

### **Coordination Activities**
1. **Sprint Preparation**: Prepare detailed implementation plans once dependencies are ready
2. **Resource Allocation**: Coordinate with engineering teams on resource availability
3. **Risk Assessment**: Identify and document new risks based on dependency outcomes
4. **Go/No-Go Decision**: Make formal recommendation on sprint commencement

## Workflow & Communication

### **Weekly Dependency Review Process**
1. **Status Collection**: Gather status updates from dependency task owners
2. **Progress Analysis**: Analyze completion percentage and remaining work
3. **Blocker Assessment**: Identify any blockers or timeline risks
4. **Communication**: Update sprint stakeholders on dependency status
5. **Plan Adjustment**: Adjust sprint tasks based on new information

### **Task Update Process** (Once Dependencies Resolved)
1. **Capability Inventory**: Document actual capabilities provided by dependencies
2. **API Documentation**: Collect and organize API specifications and endpoints
3. **Implementation Plan Update**: Revise all sprint tasks with accurate implementation details
4. **Resource Requirement Update**: Update effort estimates based on actual capabilities
5. **Risk Re-assessment**: Update risk assessment based on actual vs planned functionality

### **Communication Standards**
- **Weekly Status Reports**: Comprehensive dependency status and timeline updates
- **Blocker Alerts**: Immediate notification of critical blockers or timeline slips
- **Readiness Notification**: Formal notification when dependencies are ready
- **Updated Task Distribution**: Distribution of updated task plans to all sprint participants

## Success Criteria

### **Dependency Readiness Checklist**
- [ ] **Tool SDK**: Contracts defined, registry operational, scoping implemented
- [ ] **Agent Memory**: Models deployed, tools functional, vector search operational
- [ ] **Prompt Orchestrator**: Dynamic assembly working, memory integration functional
- [ ] **Agent Manager**: Profiles creatable, modes enforceable, scope resolution working

### **Task Update Completeness**
- [ ] All sprint tasks updated with accurate API specifications
- [ ] Implementation approaches validated against actual capabilities
- [ ] Effort estimates revised based on actual tool complexity
- [ ] Risk assessments updated with new information
- [ ] Timeline adjusted to reflect realistic implementation schedule

### **Sprint Readiness Assessment**
- [ ] All critical dependencies functionally complete
- [ ] Task plans reflect actual implementation requirements
- [ ] Resource availability confirmed for sprint execution
- [ ] Risks identified and mitigation strategies defined
- [ ] Go/no-go decision documented with clear rationale

## Escalation Procedures

### **Dependency Delays**
- **Minor Delays (< 1 week)**: Monitor and adjust sprint timeline accordingly
- **Major Delays (1-2 weeks)**: Escalate to sprint stakeholders, consider scope reduction
- **Critical Delays (> 2 weeks)**: Recommend sprint postponement, alternative approaches

### **Scope Changes**
- **Capability Reductions**: Adjust task scope to match available functionality
- **Capability Additions**: Evaluate opportunities to expand sprint scope
- **API Changes**: Update all affected tasks with new specifications

### **Resource Conflicts**
- **Developer Availability**: Coordinate with engineering managers on resource allocation
- **Infrastructure Dependencies**: Escalate infrastructure blockers to DevOps teams
- **External Dependencies**: Coordinate with external teams on timeline alignment

## Deliverables

### **Immediate Deliverables** (Until Dependencies Ready)
- [ ] Weekly dependency status reports
- [ ] Blocker identification and escalation
- [ ] Timeline risk assessment and communication
- [ ] Sprint readiness monitoring dashboard

### **Update Deliverables** (Once Dependencies Ready)
- [ ] **Updated AGENT.md files**: All sprint tasks with accurate implementation details
- [ ] **Updated PLAN.md files**: Refined implementation approaches and timelines
- [ ] **Updated TODO.md files**: Detailed task breakdowns with actual API calls
- [ ] **Updated CONTEXT.md files**: Technical context reflecting actual capabilities
- [ ] **Sprint Readiness Report**: Formal assessment and go/no-go recommendation

## Tools & Resources

### **Monitoring Tools**
- **Sprint Tracking**: Access to all dependency sprint boards and status
- **Communication**: Direct communication channels with dependency task owners
- **Documentation**: Access to engineering documentation and API specifications
- **Testing**: Access to development environments for capability validation

### **Update Tools**
- **Documentation Platform**: Access to update all sprint task documentation
- **Version Control**: Ability to track and manage documentation changes
- **Communication Platform**: Distribution channels for updated task plans
- **Testing Environment**: Validation environment for implementation approaches

---

**Agent Type**: System Agent (Internal)  
**Activation**: Immediate (Sprint 48 start)  
**Duration**: Until dependencies resolved + 1 week for task updates  
**Success Trigger**: All sprint tasks updated with accurate implementation details