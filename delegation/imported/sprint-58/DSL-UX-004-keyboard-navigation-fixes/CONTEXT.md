# DSL-UX-004: Keyboard Navigation Fixes - Context

## Current Problem Analysis

### TipTap SlashCommand Navigation Issues
**Location**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`

**Current Issue**: Arrow keys close the autocomplete suggestions instead of navigating through them.

**Root Cause**: Missing `preventDefault()` calls in keyboard event handlers, causing TipTap's default key handling to interfere with autocomplete navigation.

### Expected Behavior vs Current Behavior

#### Expected Behavior:
```
User types "/se" → Suggestions appear
User presses ↓ → Highlights next suggestion  
User presses ↑ → Highlights previous suggestion
User presses Enter → Selects highlighted suggestion
User presses Escape → Closes suggestions without selection
```

#### Current Behavior:
```
User types "/se" → Suggestions appear
User presses ↓ → Suggestions close (TipTap default behavior)
User presses ↑ → Suggestions close (TipTap default behavior)
```

### TipTap Architecture Context

#### Slash Command Extension Structure
**File**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`

```typescript
// Current structure (approximate)
export const SlashCommand = Extension.create({
  name: 'slashCommand',
  
  addCommands() {
    return {
      slashCommand: () => ({ commands }) => {
        // Command execution logic
      }
    }
  },
  
  addKeyboardShortcuts() {
    return {
      '/': () => {
        // Trigger autocomplete
        return this.editor.commands.slashCommand()
      }
    }
  }
})
```

#### Suggestion Component Integration
**Component**: `SlashCommandList` (likely location)

```typescript
// Current implementation issues
const SlashCommandList = ({ items, command }) => {
  const [selectedIndex, setSelectedIndex] = useState(0)
  
  // PROBLEM: Missing useEffect for keyboard event handling
  // PROBLEM: No preventDefault() calls
  // PROBLEM: Event handlers not properly cleaning up
  
  const handleKeyDown = (event: KeyboardEvent) => {
    switch (event.key) {
      case 'ArrowDown':
        // MISSING: event.preventDefault()
        setSelectedIndex((prev) => (prev + 1) % items.length)
        break
      case 'ArrowUp':
        // MISSING: event.preventDefault()
        setSelectedIndex((prev) => (prev - 1 + items.length) % items.length)
        break
      case 'Enter':
        // MISSING: event.preventDefault()
        command(items[selectedIndex])
        break
      case 'Escape':
        // MISSING: event.preventDefault()
        closePopover()
        break
    }
  }
  
  // MISSING: Event listener setup and cleanup
  
  return (
    <div className="slash-command-list">
      {items.map((item, index) => (
        <div key={item.slug} className={index === selectedIndex ? 'selected' : ''}>
          {item.name}
        </div>
      ))}
    </div>
  )
}
```

### TipTap Event Handling Hierarchy

#### Event Flow Sequence:
1. **User Input**: User presses arrow key
2. **Browser Event**: KeyboardEvent fires
3. **TipTap Processing**: TipTap extension keyboard shortcuts run first
4. **Custom Handlers**: Our suggestion list handlers (if they prevent default)
5. **Default Behavior**: Browser default behavior (if not prevented)

**Current Problem**: Our handlers don't prevent default, so TipTap's default key handling closes the suggestions.

### Component Architecture Context

#### Autocomplete Flow:
```
User types "/" → TipTap triggers slash command
→ SlashCommand extension fetches suggestions
→ SlashCommandList component renders suggestions  
→ User navigates with keyboard → SlashCommandList handles events
→ User selects suggestion → Command executes
```

#### State Management:
- **Suggestion Visibility**: Controlled by TipTap extension state
- **Selected Index**: Local state in SlashCommandList component
- **Command List**: Fetched from autocomplete API (DSL-UX-002)
- **Loading States**: Loading indicators during API calls

### Performance Context

#### Current API Integration:
**File**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`

```typescript
// Current fetchCommands implementation
const fetchCommands = async (query: string) => {
  // PROBLEM: No debouncing - fires on every keystroke
  const response = await fetch(`/api/autocomplete/commands?query=${query}`)
  return response.json()
}

// Called on every input change
editor.on('update', ({ editor }) => {
  const text = editor.getText()
  if (text.startsWith('/')) {
    const query = text.slice(1)
    fetchCommands(query).then(setCommands)
  }
})
```

**Performance Issues**:
1. **No debouncing**: API called on every keystroke
2. **No caching**: Same queries fetch data repeatedly  
3. **No request cancellation**: Multiple requests in flight
4. **No loading states**: Poor UX during API calls

### Browser Compatibility Context

#### Keyboard Event Handling Differences:
- **Safari**: Different event timing for some keys
- **Firefox**: Slightly different preventDefault() behavior
- **Chrome**: Most consistent behavior
- **Mobile**: Touch vs keyboard considerations

#### Required Cross-Browser Support:
- All modern desktop browsers (Chrome, Firefox, Safari, Edge)
- Mobile Safari and Chrome (for tablet usage)
- Consistent behavior across platforms

## Technical Integration Points

### TipTap Extension System
**Integration Requirements**:
- Work within TipTap's plugin architecture
- Respect TipTap's command system
- Handle focus management properly
- Integrate with TipTap's undo/redo system

### State Management
**State Coordination**:
- TipTap editor state for selection and cursor position
- React component state for suggestion list
- API request state for loading and caching
- Keyboard navigation state for selected index

### CSS and Styling
**Visual Requirements**:
- Highlight selected suggestion clearly
- Smooth transitions between selections
- Responsive design for different screen sizes
- Dark mode compatibility
- Accessibility compliance (ARIA states)

## Event Handling Requirements

### Keyboard Events to Handle:
- **ArrowDown**: Navigate to next suggestion
- **ArrowUp**: Navigate to previous suggestion  
- **Enter**: Select current suggestion
- **Escape**: Close suggestions without selection
- **Tab**: Close suggestions and continue editing
- **Backspace**: Handle when reducing query length

### Event Prevention Strategy:
```typescript
const handleKeyDown = (event: KeyboardEvent) => {
  if (!isVisible) return // Only handle when suggestions visible
  
  switch (event.key) {
    case 'ArrowDown':
    case 'ArrowUp':  
    case 'Enter':
    case 'Escape':
      event.preventDefault() // Critical: Prevent TipTap default handling
      event.stopPropagation() // Prevent event bubbling
      // Handle navigation logic
      break
    default:
      // Let other keys pass through normally
      break
  }
}
```

### Focus Management:
- Maintain TipTap editor focus during navigation
- Handle focus loss/gain events
- Ensure screen reader accessibility

## Caching and Performance Requirements

### Client-Side Caching Strategy:
**Cache Structure**:
```typescript
interface CommandCache {
  [query: string]: {
    commands: Command[]
    timestamp: number
    ttl: number
  }
}

// LRU cache with TTL
const cache = new Map<string, CacheEntry>()
const MAX_CACHE_SIZE = 50
const CACHE_TTL = 5 * 60 * 1000 // 5 minutes
```

### Debouncing Implementation:
```typescript
import { debounce } from 'lodash'

const debouncedFetch = debounce(async (query: string) => {
  // Check cache first
  const cached = getFromCache(query)
  if (cached && !isExpired(cached)) {
    return cached.commands
  }
  
  // Fetch from API
  const commands = await fetchCommands(query)
  
  // Update cache
  setCache(query, commands)
  
  return commands
}, 300) // 300ms delay
```

### Request Cancellation:
```typescript
let currentRequest: AbortController | null = null

const fetchCommands = async (query: string) => {
  // Cancel previous request
  if (currentRequest) {
    currentRequest.abort()
  }
  
  // Create new request
  currentRequest = new AbortController()
  
  try {
    const response = await fetch(`/api/autocomplete/commands?query=${query}`, {
      signal: currentRequest.signal
    })
    return response.json()
  } catch (error) {
    if (error.name === 'AbortError') {
      // Request cancelled, ignore
      return []
    }
    throw error
  }
}
```

## Testing Context

### User Interaction Testing:
- Keyboard navigation flows
- Mouse interaction fallbacks
- Touch/mobile interactions
- Focus management testing

### Performance Testing:
- Debouncing behavior verification
- Cache hit/miss tracking
- API request cancellation
- Memory leak prevention

### Cross-Browser Testing:
- Keyboard event handling consistency
- Visual rendering differences
- Performance variations
- Accessibility compliance

## Risk Assessment

### Interaction Conflicts:
- **Risk**: TipTap shortcuts conflicting with navigation
- **Mitigation**: Proper event prevention and testing
- **Testing**: Comprehensive keyboard interaction tests

### Performance Impact:
- **Risk**: Client-side caching memory leaks
- **Mitigation**: LRU cache with size limits and TTL cleanup
- **Monitoring**: Memory usage tracking in development

### Browser Compatibility:
- **Risk**: Inconsistent keyboard behavior across browsers
- **Mitigation**: Comprehensive cross-browser testing
- **Fallbacks**: Graceful degradation for unsupported browsers

This context provides the technical foundation for implementing reliable keyboard navigation in the TipTap slash command system with proper performance optimization and cross-browser compatibility.