# MIGRATE-007: Universal Access Framework Agent

## Agent Profile
**Type**: Universal Integration & Cross-Platform Specialist  
**Role**: Universal Access Agent  
**Mission**: Create framework enabling any Claude Code agent instance to access Fragments Engine orchestration capabilities across any project type or environment.

## Mission Statement
**PENDING DEPENDENCY RESOLUTION**: This task will be updated with accurate implementation details once MCP server architecture and authentication systems are complete.

## Current Status
⚠️ **BLOCKED**: Waiting for dependency resolution from MIGRATE-001

## Dependencies
- **MIGRATE-001**: Dependency resolution and task updates
- **MIGRATE-002**: MCP Integration Layer (authentication and communication)
- **All Previous Migration Tasks**: Foundation for universal access

## Universal Access Requirements
### **Cross-Platform Compatibility**
- Works from any Claude Code instance regardless of host system
- Consistent behavior across macOS, Linux, and Windows environments
- No platform-specific dependencies or requirements
- Graceful degradation for limited-capability environments

### **Project-Agnostic Operation**
- Functions across any project type (Laravel, React, Python, etc.)
- No assumptions about project structure or tooling
- Adapts to different development workflows and practices
- Maintains consistency while respecting project conventions

### **Authentication & Authorization**
- Secure authentication to Fragments Engine from any location
- Proper credential management and rotation
- Project-level permissions and access controls
- Agent-level capability restrictions and quotas

### **Network & Connectivity**
- Reliable operation across various network conditions
- Proper retry and error handling for network issues
- Offline capability where possible with sync when connected
- Efficient data transfer and caching strategies

## Sub-Agent Rules (CRITICAL)
- **MANDATORY**: ALL agents MUST authenticate through universal access framework
- **MANDATORY**: NO direct FE database access outside framework
- **MANDATORY**: ALL cross-project operations MUST respect project boundaries
- **MANDATORY**: Universal access MUST enforce all security and approval policies
- **MANDATORY**: Framework MUST work identically across all supported platforms

## Framework Architecture (Subject to Update)
### **Authentication Layer**
- Secure credential management and storage
- Multi-factor authentication support
- Token refresh and rotation automation
- Project-level permission validation

### **Communication Layer**
- Reliable MCP client with retry logic
- Connection pooling and optimization
- Request/response caching where appropriate
- Network failure detection and recovery

### **Abstraction Layer**
- Consistent API regardless of underlying FE capabilities
- Version compatibility and feature detection
- Graceful degradation for missing features
- Plugin architecture for extensibility

### **Security Layer**
- Request validation and sanitization
- Rate limiting and quota enforcement
- Audit logging for all operations
- Approval gate integration for destructive operations

## Key Features (Provisional)
- **Auto-Discovery**: Automatic detection of FE capabilities
- **Credential Management**: Secure storage and rotation of access credentials
- **Offline Mode**: Limited functionality when FE is unavailable
- **Performance Optimization**: Caching and request batching
- **Error Recovery**: Intelligent retry and fallback mechanisms
- **Cross-Project Sync**: Coordination across multiple project instances

## Integration Challenges
- **Network Reliability**: Handling various network conditions and failures
- **Authentication Complexity**: Secure authentication across diverse environments
- **Performance Variability**: Consistent performance across different network conditions
- **Feature Compatibility**: Handling different FE versions and capabilities
- **Security Enforcement**: Maintaining security across distributed access points

## Success Criteria
- **Universal Compatibility**: Works from any Claude Code instance
- **Consistent Performance**: Reliable response times regardless of location
- **Security Compliance**: Maintains all security and approval requirements
- **Feature Parity**: Identical functionality across all access points
- **Error Resilience**: Graceful handling of network and system failures

## Next Steps
1. Wait for MIGRATE-001 to resolve dependencies
2. Receive updated MCP server specifications and authentication requirements
3. Design universal access architecture and security framework
4. Implement and test across various Claude Code environments

---
**Status**: PENDING DEPENDENCY RESOLUTION  
**Update Required**: Once MIGRATE-001 completes dependency analysis