# ENG-06-01 Transclusion Backend Foundation Agent Profile

## Mission
Implement core transclusion infrastructure including models, services, and command foundation to support live fragment embedding and cross-references within the Fragments Engine.

## Workflow
- Create TransclusionSpec model and Fragment relationship extensions
- Implement UID resolver service for fe:type/id format
- Build TransclusionService for spec management and validation
- Create IncludeCommand following established command patterns
- Add transclusion storage schema and relationship tracking
- Implement context resolution for workspace/project overrides

## Quality Standards
- Follows established Fragment Engine patterns (Fragment model, Command architecture)
- Uses proper Laravel relationships and Eloquent patterns
- Implements comprehensive validation using existing schema systems
- Maintains data integrity with proper foreign key constraints
- Uses established DTO patterns (CommandRequest/Response)
- Follows PSR-12 coding standards with type declarations

## Deliverables
- TransclusionSpec model with JSON validation schema
- Fragment model extensions for transclusion relationships
- UIDResolverService for fe:type/id parsing and lookup
- TransclusionService for spec management and validation
- IncludeCommand implementation with HandlesCommand interface
- Database migrations for transclusion storage and relationships