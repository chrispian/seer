# UX-04-04 Custom Slash Commands UI Agent Profile

## Mission
Create a comprehensive CRUD interface for custom slash commands with settings management, following the established Widget drawer pattern. Include AI response toggles, notification settings, help system integration, and a visual flow editor for command creation.

## Workflow
- Analyze existing command system architecture and widget patterns
- Create CRUD interface using established Sheet/drawer patterns
- Implement command settings with AI response and notification toggles
- Build visual flow editor for command creation and editing
- Integrate with existing help system for command registration
- Add validation, testing, and preview capabilities for commands
- Follow existing Fragments Engine UI patterns and shadcn components

## Quality Standards
- UI follows existing CustomizationPanel and widget drawer patterns
- Uses Shadcn components throughout for consistency
- Implements proper form validation and error handling
- Integrates seamlessly with existing command execution system
- Provides intuitive command creation and management experience
- Maintains performance with large numbers of custom commands
- Follows accessibility standards and responsive design

## Deliverables
- CustomSlashCommandsPanel component with full CRUD interface
- Command creation wizard with step-by-step guidance
- Settings management for AI responses and notifications
- Visual flow editor for command step definition
- Help system integration for command documentation
- Command validation and testing interface
- Import/export functionality for command sharing

## Key Features to Implement
- **CRUD Interface**: Create, read, update, delete custom commands
- **Settings Panel**: AI response toggles, success/fail notifications
- **Flow Editor**: Visual command step editor with drag-drop
- **Help Integration**: Automatic help system registration
- **Validation**: Command syntax and logic validation
- **Testing**: Command preview and testing interface
- **Import/Export**: Command sharing and backup capabilities

## Technical Integration Points
- Uses existing command registry and execution system
- Integrates with current YAML DSL parser and step types
- Leverages existing notification and toast systems
- Follows established API patterns for command management
- Uses existing modal and sheet UI patterns
- Integrates with help command system for documentation

## Safety Notes
- Validate command syntax and prevent malicious code execution
- Ensure command permissions and security constraints
- Prevent conflicts with existing system commands
- Handle command execution errors gracefully
- Maintain backward compatibility with existing commands

## Communication
- Report UI development progress and integration challenges
- Document new API endpoints and command system extensions
- Provide testing results with various command scenarios
- Confirm accessibility and responsive design compliance
- Deliver component ready for production deployment