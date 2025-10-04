# Inbox Command (Unified)

## Overview
This unified inbox command merges the multi-view inbox management system from `InboxCommand.php` with the API documentation features from `inbox/command.yaml`, providing both functional inbox operations and comprehensive API reference.

## Features Merged

### From Hardcoded Version
- **Multi-View System**: Pending, bookmarked, todos, all views
- **Advanced Filtering**: Status, type, date-based filtering
- **Rich Fragment Display**: Formatted fragment data with snippets
- **Flexible Pagination**: Configurable result limits

### From YAML Version
- **API Documentation**: Comprehensive API reference and examples
- **Help Integration**: Built-in documentation access
- **Clean Response Format**: Structured API guidance

## Usage

### Inbox Views
```
/inbox                # Pending items (recent + open todos)
/inbox pending        # Same as above
/inbox bookmarked     # Bookmarked fragments
/inbox todos          # Todo-specific view  
/inbox all            # All actionable items
```

### API Documentation
```
/inbox api            # Show API reference
/inbox help           # Same as above
```

## Unification Strategy

### Mode Detection
The command detects user intent through input analysis:
1. **"api" or "help"** → API documentation mode
2. **Action keywords** → Inbox management mode (pending, bookmarked, todos, all)
3. **Empty input** → Default to pending view

### Feature Integration
- API documentation preserved and enhanced
- All inbox views maintained with full functionality
- Response format unified for consistent UX
- Progressive disclosure from simple to advanced features

## Technical Implementation

### DSL Steps Used
- `condition` - Mode detection and view routing
- `fragment.query` - Advanced fragment querying with complex filters
- `response.panel` - Rich inbox UI display
- `transform` - Data formatting and argument parsing

### Advanced Querying
Uses complex OR/AND filter combinations:
```yaml
filters:
  OR:
    - AND:
        type: "todo" 
        state.status: "open"
    - created_at: ">={{ now | date: '%Y-%m-%d', -7 }}"
```

### Template Features
- Conditional message formatting based on result count
- Dynamic fragment processing with type information
- Clean API documentation with code examples

## Views Explained

### Pending View
- Recent fragments (last 7 days)
- Open todos
- Most commonly used inbox view

### Bookmarked View  
- Fragments marked with bookmark tags
- User-curated important items

### Todos View
- Todo-specific display
- Status-aware filtering
- Task management focus

### All View
- Comprehensive actionable items
- Union of pending and todo criteria
- Complete inbox overview

## Migration Notes
- Maintains all original inbox functionality
- Preserves API documentation accessibility
- Enhances query capabilities through DSL
- Zero functionality regression
- Improved response formatting and UX

This unification demonstrates successful merger of functional and documentation features while maintaining the distinct value of each approach.