# ENG-06-01 Transclusion Backend Foundation Context

## Technical Architecture

### Database Schema
```sql
-- transclusion_specs table
CREATE TABLE transclusion_specs (
    id BIGINT UNSIGNED PRIMARY KEY,
    fragment_id BIGINT UNSIGNED,
    kind ENUM('single', 'list'),
    mode ENUM('ref', 'copy', 'live', 'snapshot'),
    target_uid VARCHAR(255),
    query TEXT,
    context JSON,
    layout VARCHAR(50),
    columns JSON,
    options JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (fragment_id) REFERENCES fragments(id) ON DELETE CASCADE
);

-- fragment_links table extension for transclusion relationships
ALTER TABLE fragment_links ADD COLUMN link_type ENUM('reference', 'transclusion', 'copy') DEFAULT 'reference';
```

### Integration Points
- **Fragment Model**: app/Models/Fragment.php - Add transclusion relationships
- **Command System**: app/Actions/Commands/* - Follow IncludeCommand pattern
- **UID System**: New UIDResolverService for fe:type/id resolution
- **Validation**: Extend existing schema validation system
- **Storage**: JSON companion files for deterministic rehydration

### Dependencies
- Fragment model and relationship system
- Command registration and execution framework
- JSON schema validation system
- Context stack (workspace → project → session) resolution

### Existing Patterns to Follow
- **Command Pattern**: app/Actions/Commands/TodoCommand.php
- **Model Pattern**: app/Models/Fragment.php
- **Service Pattern**: app/Services/ structure
- **DTO Pattern**: app/DTOs/CommandRequest.php, CommandResponse.php
- **Migration Pattern**: database/migrations/ structure

### UID Format Specification
- Format: `fe:<type>/<base62>`
- Examples: `fe:note/7Q2M9K`, `fe:todo/AB12CD`, `fe:bookmark/XY789Z`
- Resolution: UIDResolverService maps to Fragment records by type and ID