# Todo Command (Unified)

## Overview
This unified todo command merges the functionality of the hardcoded `TodoCommand.php` with the YAML DSL `todo/command.yaml`, providing both simple AI-assisted creation and full CRUD management capabilities.

## Features Merged

### From Hardcoded Version
- **Full CRUD Operations**: Create, list, complete todos
- **Status Management**: Open, completed todo tracking
- **Advanced Querying**: Status filters, search, tag filtering
- **Position/Keyword Completion**: Complete todos by position or keyword
- **Fragment Integration**: Full Laravel model integration

### From YAML Version  
- **AI-Assisted Creation**: Intelligent todo parsing and structuring
- **Template-Based Processing**: Clean, declarative workflow
- **Enhanced Response Format**: Improved user experience

## Usage

### Create Todo (AI-Assisted)
```
/todo Fix the login bug
/todo "Prepare presentation for Monday #urgent"
```

### List Todos
```
/todo list           # List open todos
/todo list completed # List completed todos
/todo                # Default to list open
```

### Complete Todos
```
/todo complete 1          # Complete todo at position 1
/todo complete login      # Complete todo matching "login"
```

## Unification Strategy

### Mode Detection
The command uses conditional logic to detect the intended operation:
1. **Empty input** → Default list mode
2. **"list" keyword** → List mode with status parsing
3. **"complete" keyword** → Completion mode
4. **Other text** → Creation mode

### Feature Preservation
- All original hardcoded functionality preserved
- AI parsing enhanced for better todo structure
- Response format unified for consistent UX
- Error handling comprehensive across all modes

## Technical Implementation

### DSL Steps Used
- `condition` - Mode detection and branching
- `fragment.create` - Todo creation with metadata
- `fragment.query` - Advanced todo querying and filtering
- `fragment.update` - Status updates for completion
- `ai.generate` - Intelligent todo parsing
- `response.panel` - Rich UI panel responses

### Template Features
- Expression evaluation for dynamic logic
- Control structures for complex conditionals  
- Advanced filtering and data transformation

## Migration Notes
- Maintains 100% backward compatibility
- Preserves all original command arguments and responses
- Enhances UX through AI-assisted parsing
- Zero functionality regression

This represents the successful unification pattern from Sprint 46, applied to resolve the todo command conflict while enhancing capabilities.