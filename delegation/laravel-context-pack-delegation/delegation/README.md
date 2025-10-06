# Laravel Context Pack - Delegation System

## Overview

This delegation system enables coordinated multi-agent development for the Laravel Context Pack project. It provides structured task management, agent specialization, and parallel development workflows.

## Quick Start

### 1. Agent Creation
Use the available agent templates to create specialized agents:

```bash
# Available agent types:
- backend-engineer      # Laravel package development, APIs, testing
- frontend-engineer     # UI components, build tools, frontend integration  
- project-manager       # Coordination, task management, quality oversight
- qa-engineer          # Testing, validation, package quality assurance
- ux-designer          # Developer experience, CLI design, documentation UX
```

### 2. Sprint Structure
Development is organized into focused sprints:

```
sprint-001/   # Foundation and core architecture
sprint-002/   # Core functionality development
sprint-003/   # Frontend components and tooling
sprint-004/   # Documentation and examples
sprint-005/   # Testing, optimization, and distribution
```

### 3. Task Pack Structure
Each task follows a standardized structure:

```
{TASK-ID}/
├── AGENT.md      # Specialized agent profile with mission and context
├── CONTEXT.md    # Technical context and integration requirements
├── PLAN.md       # Implementation phases with time estimates
└── TODO.md       # Granular checklist for execution
```

## Agent Specializations

### Backend Engineer
**Focus**: Laravel package architecture, service providers, testing
- Package structure and organization
- Service provider development and registration
- Artisan command development
- Database integration and testing
- Composer package standards

### Frontend Engineer  
**Focus**: UI components, build integration, asset compilation
- React/TypeScript component development
- Build tool integration (Vite, Webpack)
- NPM package development and distribution
- Laravel asset integration
- Frontend testing and optimization

### Project Manager
**Focus**: Coordination, delegation, quality oversight
- Task prioritization and sprint management
- Agent coordination and dependency tracking
- Quality gate enforcement
- Progress monitoring and reporting
- Risk identification and mitigation

### QA Engineer
**Focus**: Testing, validation, package quality
- Laravel Testbench integration testing
- Multi-version compatibility testing
- Performance and memory usage validation
- Package installation and configuration testing
- CI/CD pipeline development

### UX Designer
**Focus**: Developer experience, CLI design, documentation
- CLI interface design and usability
- Documentation structure and presentation
- Developer workflow optimization
- Error message design and clarity
- Installation and setup experience

## Development Workflows

### Parallel Development with Git Worktrees
Set up isolated development environments:

```bash
# Create worktrees for Sprint 001
./delegation/setup-worktree.sh 001

# Results in:
laravel-context-pack-backend-sprint001/     # Backend development
laravel-context-pack-frontend-sprint001/    # Frontend development  
laravel-context-pack-integration-sprint001/ # Integration and testing

# Cleanup when done
./delegation/cleanup-worktree.sh 001
```

### Task Delegation Process
1. **Task Analysis**: Review requirements and create task pack
2. **Agent Selection**: Choose appropriate agent specialization
3. **Context Setup**: Provide agent with complete task pack
4. **Execution**: Agent follows structured implementation plan
5. **Integration**: Coordinate results with other agents
6. **Quality Review**: Validate against established standards

### MCP Integration
Use MCP commands for system management:

```bash
# Sprint status and management
/delegation-system sprint/status
/delegation-system sprint/analyze sprint=001

# Agent management
/delegation-system agent/create role=backend-engineer name=alice
/delegation-system agent/assign agent=alice-backendengineer-001 task=ENG-001

# Task analysis and tracking
/delegation-system task/analyze task=ENG-001-package-structure
/delegation-system worktree/setup sprint=001
```

## Quality Standards

### Laravel Package Standards
- **PSR Compliance**: PSR-4, PSR-12 for all package code
- **Laravel Conventions**: Follow Laravel naming and structure patterns
- **Testing**: Minimum 85% test coverage with Testbench
- **Documentation**: Complete API documentation with examples
- **Compatibility**: Support Laravel LTS versions

### Code Quality Gates
- [ ] PSR-12 compliance validated
- [ ] All public APIs have comprehensive tests
- [ ] Package integrates cleanly with Laravel applications
- [ ] Documentation includes installation and usage examples
- [ ] No breaking changes without major version increment
- [ ] Performance benchmarks meet established standards

### Integration Requirements
- [ ] Clean Composer installation process
- [ ] Service providers register correctly
- [ ] Configuration merges properly with defaults
- [ ] Artisan commands follow Laravel conventions
- [ ] Database migrations are reversible
- [ ] Frontend assets compile and integrate correctly

## Sprint Planning

### Sprint 001: Foundation
**Duration**: 2-3 weeks  
**Focus**: Core package structure and Laravel integration

**Key Deliverables**:
- Laravel package scaffold with proper structure
- Service provider with configuration management
- Basic CLI commands for package management
- Testing foundation with Laravel Testbench
- Initial documentation structure

### Sprint 002: Core Functionality
**Duration**: 3-4 weeks  
**Focus**: Core context management features

**Key Deliverables**:
- Context creation, storage, and retrieval
- Multiple storage backend support
- API layer for context operations
- Comprehensive test coverage
- Performance optimization

### Sprint 003: Frontend Integration
**Duration**: 2-3 weeks  
**Focus**: Frontend tooling and component development

**Key Deliverables**:
- Build tool integration with Laravel
- React component library for context management
- NPM package with TypeScript definitions
- Frontend testing setup
- Asset compilation and distribution

### Sprint 004: Documentation & Examples
**Duration**: 2 weeks  
**Focus**: User-facing documentation and examples

**Key Deliverables**:
- Comprehensive package documentation
- Interactive examples and tutorials
- API reference documentation
- CLI help system enhancement
- Installation and setup guides

### Sprint 005: Quality & Distribution
**Duration**: 2 weeks  
**Focus**: Final testing, optimization, and release preparation

**Key Deliverables**:
- Multi-version compatibility testing
- Performance optimization and profiling
- Package distribution setup (Packagist, NPM)
- CI/CD pipeline implementation
- Release candidate preparation

## File Organization

```
delegation/
├── agents/
│   ├── templates/          # Agent role templates
│   └── active/            # Generated agent instances
├── sprint-001/            # Foundation sprint
├── sprint-002/            # Core functionality sprint
├── sprint-003/            # Frontend integration sprint
├── sprint-004/            # Documentation sprint
├── sprint-005/            # Quality and distribution sprint
├── archived/              # Completed sprints and tasks
├── backlog/              # Future tasks and ideas
├── setup-worktree.sh     # Worktree management script
├── cleanup-worktree.sh   # Worktree cleanup script
├── README.md             # This file
└── SPRINT_STATUS.md      # Live sprint tracking dashboard
```

## Getting Started

1. **Review Project Context**: Understand Laravel Context Pack goals and architecture
2. **Select Sprint**: Choose appropriate sprint based on current priorities
3. **Create Agent**: Use agent template to create specialized agent
4. **Setup Environment**: Use worktree script for isolated development
5. **Execute Tasks**: Follow task pack structure for systematic implementation
6. **Coordinate Integration**: Work with project manager for integration
7. **Validate Quality**: Ensure all quality gates are met before completion

## Support and Documentation

- **Agent Templates**: `delegation/agents/templates/`
- **Sprint Planning**: `delegation/SPRINT_STATUS.md`
- **MCP Commands**: Use `/delegation-system` commands for system management
- **Quality Standards**: See individual sprint documentation for specific requirements

This delegation system ensures high-quality, coordinated development while maintaining Laravel ecosystem standards and best practices.