# MIGRATE-003: Agent Profile Migration Agent

## Agent Profile
**Type**: Data Migration & Agent Management Specialist  
**Role**: Migration Coordination Agent  
**Mission**: Migrate our 7 created agent profiles to Fragments Engine agent management system, ensuring proper capability mapping and scope configuration.

## Mission Statement
**PENDING DEPENDENCY RESOLUTION**: This task will be updated with accurate implementation details once UX-04-02 (Agent Manager) and related agent profile systems are complete.

## Current Status
⚠️ **BLOCKED**: Waiting for dependency resolution from MIGRATE-001

## Dependencies
- **MIGRATE-001**: Dependency resolution and task updates
- **UX-04-02**: Agent Manager System (agent profiles, modes, scope resolution)
- **ENG-09-01**: Tool SDK Foundation (capability validation)

## Agent Profiles to Migrate
1. **Senior Engineer/Code Reviewer**: Automated PR reviews, security analysis
2. **DevRel Specialist**: Developer community, technical communication
3. **Technical Writer (DevRel)**: Developer documentation, API guides
4. **Technical Writer (User Docs)**: User guides, help documentation
5. **Website Copywriter**: Marketing copy, conversion optimization
6. **SEO Specialist**: Search optimization, content strategy
7. **Infrastructure Specialist**: Laravel/TALL hosting, DevOps

## Sub-Agent Rules
- **MANDATORY**: All migrated agents MUST operate through FE agent profiles
- **MANDATORY**: Agent capabilities MUST be properly scoped and validated
- **MANDATORY**: No agent can operate outside FE orchestration system
- **MANDATORY**: All agent memory and context MUST use FE memory system

## Planned Migration Approach (Subject to Update)
1. **Profile Schema Mapping**: Map template profiles to FE agent schema
2. **Capability Translation**: Convert role descriptions to FE capability definitions
3. **Scope Configuration**: Set up proper scope hierarchy for each agent
4. **Tool Permission Mapping**: Configure tool access and permissions
5. **Testing & Validation**: Ensure migrated profiles function correctly

## Key Migration Considerations
- **Mode Assignment**: Determine appropriate modes for each agent type
- **Scope Hierarchy**: Configure Vault > Project > Chat > Message > Task cascade
- **Tool Permissions**: Map agent capabilities to tool access rights
- **Memory Scoping**: Configure memory access and sharing policies
- **Performance**: Ensure profile loading and resolution is optimized

## Next Steps
1. Wait for MIGRATE-001 to resolve dependencies
2. Receive updated agent profile schema and APIs
3. Begin systematic migration of each agent profile
4. Coordinate with system project manager implementation

---
**Status**: PENDING DEPENDENCY RESOLUTION  
**Update Required**: Once MIGRATE-001 completes dependency analysis