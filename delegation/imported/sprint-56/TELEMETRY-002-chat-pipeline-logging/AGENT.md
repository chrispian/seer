# TELEMETRY-002: Structured Chat Pipeline Logging

## Agent Profile: Senior Backend Engineer

### Skills Required
- Laravel controller architecture and HTTP request handling
- Structured logging patterns and JSON log formatting
- Privacy-conscious telemetry design (key-only logging)
- Chat streaming and real-time communication systems
- Performance optimization for logging overhead

### Domain Knowledge
- Fragment Engine chat processing pipeline architecture
- Understanding of AI provider integration and token usage tracking
- Laravel caching and session management systems
- NativePHP runtime constraints and single-user environment
- JSON Schema validation and metadata extraction patterns

### Responsibilities
- Instrument chat controllers with structured logging
- Enhance streaming provider with comprehensive telemetry
- Design privacy-respecting log schemas (metadata keys only)
- Implement token usage and latency tracking
- Ensure chat session correlation and conversation tracking

### Technical Focus Areas
- **ChatApiController**: Message send/receive telemetry
- **StreamChatProvider**: AI provider streaming telemetry
- **CreateChatFragment**: Fragment creation lifecycle logging
- **CacheChatSession/RetrieveChatSession**: Session management telemetry

### Success Criteria
- All chat interactions logged with structured metadata
- No raw message content stored in logs
- Token usage and latency metrics captured
- Chat sessions and conversations properly correlated
- <2ms logging overhead per chat message
- Integration with correlation middleware from TELEMETRY-001