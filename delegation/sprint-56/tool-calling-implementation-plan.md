# Tool Calling Implementation Plan for Fragments Engine

## Executive Summary
Transform Fragments Engine chat interface into a command execution platform capable of running tools and displaying output, serving as the primary interface for managing AI agents. This combines work from Sprint 43 (Agent Manager) and Sprint 47 (Tool SDK Foundation).

## Current State Analysis

### Existing Components
1. **Chat Infrastructure**
   - `ChatApiController` handles messages and streaming
   - Chat sessions with model provider/selection
   - Fragment creation for user/assistant messages
   - Real-time streaming support

2. **Command System**
   - Slash command parser (`ParseSlashCommand`)
   - Command pack loader with YAML DSL
   - Step-based command execution (`CommandRunner`)
   - Various step types (AI, notify, search, etc.)

3. **MCP Support**
   - Laravel MCP 0.2.0 installed
   - laravel-tool-crate ready for integration
   - Supports local stdio MCP servers

## Implementation Strategy

### Phase 1: MVP Tool Calling (Today's Goal)

#### 1.1 Integrate laravel-tool-crate
- Add as local composer package
- Configure tool-crate MCP server
- Register in routes/ai.php
- Test basic tool availability

#### 1.2 Enhance Chat Command Processing
- Detect tool commands in chat input
- Route to appropriate executor (slash commands vs MCP tools)
- Execute commands and capture output
- Stream results back to chat interface

#### 1.3 Create Tool Execution Pipeline
```php
ChatMessage -> ParseCommand -> RouteToExecutor -> ExecuteTool -> CaptureOutput -> StreamResponse
```

#### 1.4 Basic UI Updates
- Display command execution status
- Show tool output in chat
- Handle errors gracefully
- Add loading/progress indicators

### Phase 2: Fragments Engine MCP Server

#### 2.1 Core Functions Server
- Create fragments
- Recall fragments
- Search fragments
- Update/delete fragments

#### 2.2 Memory Server
- Store agent memories
- Retrieve context
- Log agent interactions
- Manage conversation history

#### 2.3 Agent Orchestration Server
- Manage sprints/todos
- Agent assignment
- Task delegation
- Progress tracking

### Phase 3: Advanced Features

#### 3.1 Tool Discovery & Help
- List available tools
- Show tool documentation
- Parameter hints
- Usage examples

#### 3.2 Tool Composition
- Chain multiple tools
- Pass outputs between tools
- Conditional execution
- Error recovery

#### 3.3 External Agent Integration
- Claude/OpenAI integration
- Agent memory persistence
- Cross-agent communication
- Unified interface

## Technical Architecture

### Tool Execution Flow
```
1. User enters command in chat
2. ChatApiController receives message
3. Detect if message is tool command (starts with / or contains tool syntax)
4. Parse command and parameters
5. Route to appropriate executor:
   a. Slash commands -> CommandRunner
   b. MCP tools -> MCPToolExecutor (new)
6. Execute tool with context
7. Capture output and errors
8. Stream results to chat
9. Create fragment for tool output
10. Update chat session history
```

### New Components Needed

#### MCPToolExecutor Service
```php
class MCPToolExecutor {
    public function execute(string $tool, array $params, array $context): ToolResult
    public function listTools(): array
    public function getToolSchema(string $tool): array
}
```

#### ToolCommandDetector
```php
class ToolCommandDetector {
    public function isToolCommand(string $message): bool
    public function extractToolCall(string $message): ?ToolCall
}
```

#### ToolResultFormatter
```php
class ToolResultFormatter {
    public function formatForChat(ToolResult $result): string
    public function formatForFragment(ToolResult $result): array
}
```

## Implementation Steps (MVP Today)

### Step 1: Setup laravel-tool-crate (30 min)
- [ ] Add to composer.json as path repository
- [ ] Run composer update
- [ ] Publish config
- [ ] Create routes/ai.php if needed
- [ ] Register tool-crate server

### Step 2: Create Tool Execution Infrastructure (1 hour)
- [ ] Create MCPToolExecutor service
- [ ] Create ToolCommandDetector
- [ ] Create ToolResultFormatter
- [ ] Add tool execution to ChatApiController

### Step 3: Update Chat Flow (1 hour)
- [ ] Modify message processing to detect tools
- [ ] Add tool execution branch
- [ ] Stream tool output
- [ ] Handle errors gracefully

### Step 4: Basic Testing (30 min)
- [ ] Test help.index tool
- [ ] Test file.read tool
- [ ] Test with slash commands
- [ ] Verify output in chat

### Step 5: Create Fragments MCP Server Foundation (1 hour)
- [ ] Create fragments-mcp package structure
- [ ] Implement basic fragment.create tool
- [ ] Implement fragment.search tool
- [ ] Test with chat interface

## Success Criteria

### MVP (Today)
- [ ] Can execute MCP tools from chat
- [ ] Tool output displays in chat
- [ ] Slash commands still work
- [ ] Basic error handling
- [ ] At least 3 working tools

### Near Term (This Week)
- [ ] Full laravel-tool-crate integration
- [ ] Fragments MCP server operational
- [ ] Memory persistence working
- [ ] 10+ tools available

### Long Term (Sprint Completion)
- [ ] Full agent orchestration
- [ ] External agent integration
- [ ] Complete tool ecosystem
- [ ] Production ready

## Risk Mitigation
1. **Compatibility**: Test each tool thoroughly before exposing
2. **Security**: Validate all inputs, sandbox execution
3. **Performance**: Async execution for long-running tools
4. **UX**: Clear feedback on tool execution status

## Notes
- Start simple, iterate quickly
- Focus on developer experience
- Maintain backward compatibility
- Document as we go
