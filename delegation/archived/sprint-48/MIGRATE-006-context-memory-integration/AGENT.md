# MIGRATE-006: Context & Memory Integration Agent

## Agent Profile
**Type**: Context Management & RAG Integration Specialist  
**Role**: Memory Integration Agent  
**Mission**: Migrate task/sprint context from file-based storage to Fragments Engine memory system with RAG capabilities for intelligent context retrieval.

## Mission Statement
**PENDING DEPENDENCY RESOLUTION**: This task will be updated with accurate implementation details once ENG-09-04 (Agent Memory) and vector search capabilities are complete.

## Current Status
⚠️ **BLOCKED**: Waiting for dependency resolution from MIGRATE-001

## Dependencies
- **MIGRATE-001**: Dependency resolution and task updates
- **ENG-09-04**: Agent Memory Foundation (notes, decisions, vector search)
- **MIGRATE-002**: MCP Integration Layer (memory tool access)

## Context Migration Scope
### **Sprint Context**
- Sprint goals, objectives, and success criteria
- Sprint timelines, milestones, and dependencies
- Sprint retrospectives and lessons learned
- Cross-sprint knowledge and patterns

### **Task Context**
- Task requirements, specifications, and acceptance criteria
- Implementation approaches and technical decisions
- Progress updates and status changes
- Quality gates and review outcomes

### **Agent Context**
- Agent assignments and capability utilization
- Agent coordination and handoff procedures
- Agent learning and performance insights
- Cross-agent collaboration patterns

### **Decision Context**
- Technical architecture decisions and rationale
- Implementation approach selections
- Risk assessments and mitigation strategies
- Quality standards and review criteria

## Sub-Agent Rules (CRITICAL)
- **MANDATORY**: ALL context MUST be stored in FE memory system
- **MANDATORY**: NO file-based context storage permitted
- **MANDATORY**: ALL agents MUST use FE memory tools for context access
- **MANDATORY**: Context retrieval MUST use RAG for relevance ranking
- **MANDATORY**: Memory scoping MUST respect project and agent boundaries

## RAG Integration Strategy (Subject to Update)
1. **Context Vectorization**: Convert all context to searchable vector embeddings
2. **Semantic Search**: Enable intelligent context retrieval based on current task
3. **Relevance Ranking**: Prioritize most relevant context for agent decision-making
4. **Context Assembly**: Dynamically assemble relevant context for agent prompts
5. **Learning Integration**: Improve context relevance through usage patterns

## Memory Scoping Architecture
- **Global Memory**: Cross-project patterns and best practices
- **Project Memory**: Project-specific context and decisions
- **Sprint Memory**: Sprint-specific goals, tasks, and outcomes
- **Task Memory**: Task-specific requirements and implementation details
- **Agent Memory**: Agent-specific learning and performance data

## Key Integration Points (Provisional)
- **Vector Storage**: High-performance embedding storage and search
- **Memory Tools**: `memory.write`, `memory.search`, `memory.rollup` via MCP
- **Context Retrieval**: RAG-powered context assembly for agent prompts
- **Decision Tracking**: Structured decision storage with rationale
- **Knowledge Graph**: Relationship mapping between context elements

## Migration Challenges
- **Context Extraction**: Extracting structured context from markdown files
- **Vector Quality**: Ensuring high-quality embeddings for accurate search
- **Performance**: Fast context retrieval for real-time agent operation
- **Scope Management**: Proper context isolation and sharing policies
- **Context Evolution**: Handling context updates and versioning

## Expected Benefits
- **Intelligent Retrieval**: Agents get relevant context automatically
- **Cross-Task Learning**: Knowledge shared between related tasks
- **Pattern Recognition**: Identify successful patterns and anti-patterns
- **Context Continuity**: Seamless context across agent handoffs
- **Historical Insight**: Learn from past sprint and task experiences

## Next Steps
1. Wait for MIGRATE-001 to resolve dependencies
2. Receive updated memory system specifications and capabilities
3. Design context extraction and vectorization procedures
4. Implement RAG integration for intelligent context retrieval

---
**Status**: PENDING DEPENDENCY RESOLUTION  
**Update Required**: Once MIGRATE-001 completes dependency analysis