# MIGRATE-004: System Project Manager Agent

## Agent Profile
**Type**: System Project Management & Orchestration Specialist  
**Role**: Internal System Agent  
**Mission**: Implement internal/system project manager agent that handles incoming requests, assigns tasks, and coordinates agent workflows using Fragments Engine orchestration.

## Mission Statement
**PENDING DEPENDENCY RESOLUTION**: This task will be updated with accurate implementation details once ENG-09-05 (Prompt Orchestrator) and agent coordination systems are complete.

## Current Status
⚠️ **BLOCKED**: Waiting for dependency resolution from MIGRATE-001

## Dependencies
- **MIGRATE-001**: Dependency resolution and task updates
- **ENG-09-05**: Prompt Orchestrator (dynamic prompt assembly, memory integration)
- **ENG-09-04**: Agent Memory Foundation (context storage and retrieval)
- **UX-04-02**: Agent Manager System (agent profiles and coordination)

## System Agent Concept
The Project Manager will be an **internal/system agent** that:
- **Receives Requests**: Handles incoming work requests from users or external agents
- **Task Assignment**: Intelligently assigns tasks to appropriate specialist agents
- **Progress Monitoring**: Tracks task progress and coordinates handoffs
- **Context Management**: Maintains project context and ensures continuity
- **Quality Assurance**: Ensures work meets standards before completion

## Sub-Agent Rules (CRITICAL)
- **MANDATORY**: ALL sub-agents MUST use Fragments Engine agent profiles
- **MANDATORY**: NO sub-agent can operate outside FE orchestration system
- **MANDATORY**: ALL task assignment goes through Project Manager agent
- **MANDATORY**: ALL agents check for assignments rather than self-directing
- **MANDATORY**: ALL agent coordination uses FE memory and communication systems

## Planned Agent Workflow (Subject to Update)
1. **Request Intake**: Receive and analyze incoming work requests
2. **Task Decomposition**: Break down complex requests into manageable tasks
3. **Agent Selection**: Choose appropriate specialist agents based on capabilities
4. **Task Assignment**: Assign tasks with proper context and requirements
5. **Progress Monitoring**: Track task progress and identify blockers
6. **Coordination**: Handle agent handoffs and cross-task dependencies
7. **Quality Review**: Validate completed work before final delivery
8. **Context Preservation**: Maintain project memory and learning

## Key Responsibilities
- **Intelligent Task Routing**: Match tasks to optimal agent capabilities
- **Context Management**: Ensure agents have necessary context and memory
- **Progress Tracking**: Monitor all active tasks and identify issues
- **Agent Coordination**: Facilitate communication and handoffs between agents
- **Quality Assurance**: Implement quality gates and review processes
- **Learning**: Improve task assignment based on agent performance history

## Integration Points (Provisional)
- **Prompt Orchestrator**: Dynamic prompt assembly for agent coordination
- **Agent Memory**: Context storage and retrieval for task continuity
- **Agent Profiles**: Agent capability assessment and selection
- **Task Management**: Sprint and task creation/tracking via FE
- **Communication**: Agent notification and coordination systems

## Next Steps
1. Wait for MIGRATE-001 to resolve dependencies
2. Receive updated prompt orchestrator and memory system specifications
3. Design agent coordination workflows and decision algorithms
4. Implement system agent with proper FE integration

---
**Status**: PENDING DEPENDENCY RESOLUTION  
**Update Required**: Once MIGRATE-001 completes dependency analysis