# Sprint 54: Context & Tool Brokers

## Overview
Implement Context Broker and Tool Broker services to replace hard-coded chat context with dynamic prompt assembly and enable natural language intent classification for tool/command routing.

## Sprint Goals
1. **Context Broker Implementation**: Dynamic prompt assembly with system prompts, history, memory, and project context
2. **Tool Broker MVP**: Natural language intent classification for automatic tool/command routing
3. **Attachment Handling**: File upload persistence, metadata extraction, and embeddings integration
4. **Enhanced Chat Experience**: Rich context and intelligent tool suggestions transform chat interactions

## Current State Analysis
- **Hard-coded Context**: Chat uses static system prompts instead of dynamic assembly
- **Manual Tool Routing**: Requires explicit slash commands instead of natural language
- **No Attachment Processing**: Uploaded files are ignored server-side
- **Limited Memory**: No integration with chat history, embeddings, or project context
- **Static Prompts**: System messages don't adapt to user, project, or task context

## Task Packs

### **CONTEXT-001**: Context Broker Implementation
**Objective**: Replace hard-coded chat context with dynamic `ContextBroker` service that assembles system prompts, history, memory, and project context.

### **TOOL-001**: Tool Broker MVP
**Objective**: Implement natural language intent classification that automatically routes to DSL commands and tool registry without requiring slash prefixes.

### **CONTEXT-002**: Attachment Handling Foundation
**Objective**: Enable file upload persistence, metadata extraction, and integration with embeddings pipeline for comprehensive attachment processing.

## Success Metrics
- **Dynamic Context**: Chat context assembled dynamically based on user, project, and conversation
- **Intent Recognition**: 80% accuracy for common tool/command intents from natural language
- **Attachment Processing**: Files persist with metadata and searchable content
- **Response Quality**: Enhanced context improves AI response relevance and accuracy
- **Developer Experience**: Context broker provides debugging and observability
- **Performance**: Context assembly completes within 100ms

## Dependencies
- Sprint 53: Unified processing pipeline for attachment handling
- Existing DSL framework and tool registry
- Fragment embeddings system (enabled in Sprint 53)
- Chat API infrastructure and session management

## Sprint Deliverables
1. **Context Broker Service** - Dynamic prompt assembly with configurable context sources
2. **Tool Broker System** - Intent classification and automatic command routing
3. **Attachment Processing Pipeline** - File persistence, metadata, and embeddings
4. **Enhanced Chat Controller** - Integration with brokers for richer chat experience
5. **Configuration Framework** - Flexible settings for context and tool routing
6. **Monitoring & Analytics** - Observability for broker decisions and performance

## Integration Architecture
```
Chat Request → Context Broker → Tool Broker → Enhanced Response
     ↓              ↓              ↓              ↓
File Upload → Attachment Handler → Fragment Pipeline → Embeddings
     ↓              ↓              ↓              ↓
Context Assembly ← Memory/History ← Project Context ← User Preferences
```

## Risk Mitigation
- **Performance Impact**: Async context assembly for non-critical components
- **Intent Accuracy**: Deterministic rules + AI classification with confidence thresholds
- **Attachment Security**: Strict validation and sandboxed processing
- **Backward Compatibility**: Gradual rollout with feature flags
- **Context Complexity**: Configurable context sources with sensible defaults

This sprint transforms chat from a simple request/response interface into an intelligent, context-aware system that understands user intent and provides relevant tools and information automatically.