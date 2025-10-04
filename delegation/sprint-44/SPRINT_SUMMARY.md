# Sprint 44: Transclusion System Implementation

## Overview
Implement comprehensive `/include` transclusion system enabling live fragment embedding and cross-references within the Fragments Engine, based on the agent-pack-transclusion specification.

## Sprint Goals
- Enable users to embed fragments within other fragments using `/include` command
- Support both single-item and list transclusions with multiple layouts
- Provide live reference updates and copy mode functionality
- Implement Obsidian-compatible markdown import/export
- Create management tools for maintaining transclusion health
- Establish foundation for advanced knowledge management workflows

## Task Packs

### **ENG-06-01-transclusion-backend-foundation** (8-12 hours)
**Priority**: Critical - Foundation for all other packs
**Dependencies**: None

Core transclusion infrastructure including models, services, and command foundation.

**Key Deliverables**:
- TransclusionSpec model with JSON validation schema
- Fragment model extensions for transclusion relationships
- UIDResolverService for fe:type/id parsing and lookup
- TransclusionService for spec management and validation
- IncludeCommand implementation with HandlesCommand interface
- Database migrations for transclusion storage and relationships

### **UX-05-01-include-command-interface** (12-16 hours)
**Priority**: High - User-facing command implementation
**Dependencies**: ENG-06-01

Comprehensive /include slash command interface integrated with TipTap editor.

**Key Deliverables**:
- Extended SlashCommand with /include entries and aliases
- Target picker component with search and UID input
- Mode and layout selection interface
- Transclusion spec insertion logic
- Updated autocomplete system with /include support
- Help system integration and documentation

### **UX-05-02-transclusion-renderer-system** (15-20 hours)
**Priority**: High - Core rendering functionality
**Dependencies**: UX-05-01, ENG-06-02

TipTap transclusion node and comprehensive rendering system.

**Key Deliverables**:
- TipTap TransclusionNode with complete schema definition
- Live reference renderer with real-time updates
- Layout components (TransclusionChecklist, Table, Cards)
- Copy mode renderer with materialized content display
- State synchronization system for interactive elements
- Error handling and fallback rendering components

### **ENG-06-02-fragment-query-engine** (10-14 hours)
**Priority**: High - Required for list transclusions
**Dependencies**: ENG-06-01

Mini-query parser and execution engine supporting list transclusions.

**Key Deliverables**:
- QueryParser service for mini-query syntax parsing
- QueryExecutor service for Fragment model query execution
- Filtering system supporting type, tag, and field conditions
- Sorting and pagination implementation
- Context resolution for workspace/project scoping
- Query optimization and caching system

### **UX-05-03-transclusion-management-interface** (8-12 hours)
**Priority**: Medium - Management and maintenance tools
**Dependencies**: All previous packs

Management interface for viewing, editing, and maintaining transclusions.

**Key Deliverables**:
- TransclusionManagementModal with comprehensive feature set
- Broken link detection and reporting system
- Refresh controls and update mechanisms
- Conflict resolution interface and workflow
- Relationship visualization components
- Batch operation tools for maintenance tasks

### **ENG-06-03-obsidian-markdown-compatibility** (6-8 hours)
**Priority**: Medium - Import/export functionality
**Dependencies**: All previous packs

Markdown import/export system with Obsidian-style embed syntax support.

**Key Deliverables**:
- Obsidian syntax parser for [[]] embeds and ^ anchors
- TransclusionSpec conversion system
- Markdown export generator with Obsidian compatibility
- Round-trip preservation system for UID anchors
- Import validation and error handling
- Compatibility testing suite and documentation

## Implementation Strategy

### Phase 1: Foundation (8-12 hours)
1. **ENG-06-01** - Complete backend foundation
   - Essential for all other development
   - Establishes data models and core services

### Phase 2: Core Features (22-30 hours)
2. **UX-05-01** - Command interface implementation
3. **ENG-06-02** - Query engine for list support
4. **UX-05-02** - Rendering system with all layouts

### Phase 3: Management & Polish (14-20 hours)
5. **UX-05-03** - Management interface
6. **ENG-06-03** - Obsidian compatibility

## Technical Architecture

### Database Schema
- `transclusion_specs` table with Fragment relationships
- Extended `fragment_links` for transclusion tracking
- JSON companion files for deterministic rehydration

### Command System
- `/include` and `/inc` slash commands in TipTap
- IncludeCommand following HandlesCommand pattern
- Integration with existing command palette

### Frontend Components
- TipTap TransclusionNode with multiple layouts
- React components for rendering and management
- Shadcn UI components for consistent styling

### Query System
- Mini-query syntax: `type:todo where:done=false sort:due limit:20`
- Context resolution with workspace/project scoping
- Performance optimization with caching and indexing

## Success Metrics

### Functional Requirements
- [ ] `/include` command works in TipTap with autocomplete
- [ ] Single-item transclusions render live content
- [ ] List transclusions support checklist, table, and cards layouts
- [ ] Todo checkboxes sync with canonical records
- [ ] Copy mode creates materialized content
- [ ] Obsidian markdown imports/exports correctly
- [ ] Management interface provides health monitoring

### Performance Targets
- [ ] Query execution < 100ms for typical list transclusions
- [ ] Real-time updates propagate within 1 second
- [ ] Large list rendering maintains 60fps with virtualization
- [ ] Markdown import/export handles files up to 10MB efficiently

### User Experience Goals
- [ ] Intuitive `/include` command discovery and usage
- [ ] Seamless integration with existing Fragment workflows
- [ ] Clear visual distinction between live and copy modes
- [ ] Helpful error messages for broken references
- [ ] Effective conflict resolution for simultaneous edits

## Risk Mitigation

### Technical Risks
- **Complex state synchronization**: Use established React Query patterns and optimistic updates
- **Performance with large datasets**: Implement virtualization and caching early
- **Circular reference detection**: Build validation into core services

### Integration Risks
- **TipTap compatibility**: Follow existing extension patterns closely
- **Backend complexity**: Leverage established Fragment and Command patterns
- **User adoption**: Provide comprehensive help system and examples

## Dependencies and Prerequisites

### Internal Dependencies
- Fragment model and relationship system
- Command registration and execution framework
- TipTap editor with existing SlashCommand patterns
- React Query for data management
- Shadcn UI component system

### External Dependencies
- None - completely self-contained within Fragments Engine

## Estimated Timeline
**Total Duration**: 59-82 hours (7-10 development days)
**Target Completion**: End of development sprint cycle
**Milestone Reviews**: After each phase completion

## Post-Sprint Considerations

### Future Enhancements
- Advanced query syntax with joins and aggregations
- Visual query builder interface
- Transclusion templates and macros
- Real-time collaborative editing
- Advanced conflict resolution strategies

### Integration Opportunities
- Integration with external knowledge management tools
- API endpoints for third-party applications
- Advanced analytics and usage tracking
- Cross-workspace transclusion sharing