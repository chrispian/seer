# DSL-UX-004: Keyboard Navigation Fixes - Implementation Plan

## Overview
Fix TipTap SlashCommand keyboard navigation issues by implementing proper event handling, client-side caching, and performance optimizations.

**Dependencies**: None (can run in parallel with other DSL-UX tasks)  
**Estimated Time**: 4-6 hours  
**Priority**: HIGH (critical for usability)

## Implementation Phases

### Phase 1: Event Handling Fixes (2-3 hours)

#### 1.1 Locate and Analyze Current Implementation (30 minutes)
**Files to Examine**:
- `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`
- Related suggestion list components
- TipTap extension configuration

**Analysis Tasks**:
- Map current keyboard event handling
- Identify where preventDefault() is missing
- Understand TipTap extension integration points
- Document current suggestion list component structure

#### 1.2 Fix Event Prevention (1 hour)
**Current Problem**:
```typescript
// Missing preventDefault() causes TipTap to handle events
const handleKeyDown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'ArrowDown':
      // MISSING: event.preventDefault()
      setSelectedIndex(prev => (prev + 1) % items.length)
      break
  }
}
```

**Solution Implementation**:
```typescript
const handleKeyDown = (event: KeyboardEvent) => {
  if (!isVisible || items.length === 0) return
  
  let handled = false
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      event.stopPropagation()
      setSelectedIndex(prev => (prev + 1) % items.length)
      handled = true
      break
      
    case 'ArrowUp':
      event.preventDefault()
      event.stopPropagation()
      setSelectedIndex(prev => (prev - 1 + items.length) % items.length)
      handled = true
      break
      
    case 'Enter':
      event.preventDefault()
      event.stopPropagation()
      selectCurrentItem()
      handled = true
      break
      
    case 'Escape':
      event.preventDefault()
      event.stopPropagation()
      closePopover()
      handled = true
      break
      
    case 'Tab':
      // Allow tab to close suggestions and continue normal flow
      closePopover()
      // Don't prevent default - allow normal tab behavior
      break
  }
  
  return handled
}
```

#### 1.3 Implement Event Listener Management (1 hour)
**Proper Event Setup and Cleanup**:
```typescript
import { useEffect, useCallback } from 'react'

const SlashCommandList = ({ items, isVisible, onSelect, onClose }) => {
  const [selectedIndex, setSelectedIndex] = useState(0)
  
  const handleKeyDown = useCallback((event: KeyboardEvent) => {
    // Implementation from 1.2
  }, [isVisible, items, selectedIndex])
  
  useEffect(() => {
    if (!isVisible) return
    
    // Add event listener when suggestions are visible
    document.addEventListener('keydown', handleKeyDown, { capture: true })
    
    // Cleanup on unmount or when suggestions close
    return () => {
      document.removeEventListener('keydown', handleKeyDown, { capture: true })
    }
  }, [isVisible, handleKeyDown])
  
  // Reset selection when items change
  useEffect(() => {
    setSelectedIndex(0)
  }, [items])
  
  return (
    // Component JSX
  )
}
```

#### 1.4 Handle Edge Cases (30 minutes)
**Edge Case Handling**:
```typescript
const handleKeyDown = (event: KeyboardEvent) => {
  // Only handle when suggestions are visible and have items
  if (!isVisible || items.length === 0) return
  
  // Ignore events when input is focused elsewhere
  if (!isSlashCommandActive()) return
  
  // Handle circular navigation
  const navigateDown = () => {
    setSelectedIndex(prev => {
      const next = prev + 1
      return next >= items.length ? 0 : next
    })
  }
  
  const navigateUp = () => {
    setSelectedIndex(prev => {
      const next = prev - 1
      return next < 0 ? items.length - 1 : next
    })
  }
  
  // Handle empty state transitions
  const selectCurrentItem = () => {
    if (items[selectedIndex]) {
      onSelect(items[selectedIndex])
    }
  }
}
```

### Phase 2: Performance Optimization (1-2 hours)

#### 2.1 Implement Debouncing (45 minutes)
**Debounced API Calls**:
```typescript
import { debounce } from 'lodash'
import { useCallback, useRef } from 'react'

const useDebouncedFetch = (delay: number = 300) => {
  const abortControllerRef = useRef<AbortController | null>(null)
  
  const debouncedFetch = useCallback(
    debounce(async (query: string, callback: (results: Command[]) => void) => {
      // Cancel previous request
      if (abortControllerRef.current) {
        abortControllerRef.current.abort()
      }
      
      // Skip fetch for very short queries
      if (query.length < 2) {
        callback([])
        return
      }
      
      // Create new abort controller
      abortControllerRef.current = new AbortController()
      
      try {
        const cached = getFromCache(query)
        if (cached) {
          callback(cached)
          return
        }
        
        const response = await fetch(`/api/autocomplete/commands?query=${query}`, {
          signal: abortControllerRef.current.signal
        })
        
        const data = await response.json()
        const commands = data.commands || []
        
        // Cache the results
        setCache(query, commands)
        callback(commands)
        
      } catch (error) {
        if (error.name !== 'AbortError') {
          console.error('Autocomplete fetch error:', error)
          callback([])
        }
      }
    }, delay),
    [delay]
  )
  
  return debouncedFetch
}
```

#### 2.2 Implement Client-Side Caching (45 minutes)
**LRU Cache Implementation**:
```typescript
interface CacheEntry {
  commands: Command[]
  timestamp: number
}

class AutocompleteCache {
  private cache = new Map<string, CacheEntry>()
  private readonly maxSize = 50
  private readonly ttl = 5 * 60 * 1000 // 5 minutes
  
  get(query: string): Command[] | null {
    const entry = this.cache.get(query)
    
    if (!entry) return null
    
    // Check if expired
    if (Date.now() - entry.timestamp > this.ttl) {
      this.cache.delete(query)
      return null
    }
    
    // Move to end (LRU)
    this.cache.delete(query)
    this.cache.set(query, entry)
    
    return entry.commands
  }
  
  set(query: string, commands: Command[]): void {
    // Remove oldest if at capacity
    if (this.cache.size >= this.maxSize) {
      const firstKey = this.cache.keys().next().value
      this.cache.delete(firstKey)
    }
    
    this.cache.set(query, {
      commands,
      timestamp: Date.now()
    })
  }
  
  clear(): void {
    this.cache.clear()
  }
  
  // Cleanup expired entries
  cleanup(): void {
    const now = Date.now()
    for (const [key, entry] of this.cache.entries()) {
      if (now - entry.timestamp > this.ttl) {
        this.cache.delete(key)
      }
    }
  }
}

// Global cache instance
const autocompleteCache = new AutocompleteCache()

// Periodic cleanup
setInterval(() => autocompleteCache.cleanup(), 60000) // Every minute
```

#### 2.3 Integrate Cache with Fetch Logic (30 minutes)
**Cache Integration**:
```typescript
const fetchCommands = async (query: string): Promise<Command[]> => {
  // Check cache first
  const cached = autocompleteCache.get(query)
  if (cached) {
    return cached
  }
  
  // Fetch from API
  const response = await fetch(`/api/autocomplete/commands?query=${query}`)
  const data = await response.json()
  const commands = data.commands || []
  
  // Cache the results
  autocompleteCache.set(query, commands)
  
  return commands
}
```

### Phase 3: Visual and UX Improvements (1 hour)

#### 3.1 Enhance Selection Visual Feedback (30 minutes)
**CSS Improvements**:
```css
/* Enhanced selection styling */
.slash-command-item {
  @apply px-3 py-2 cursor-pointer transition-colors duration-150;
}

.slash-command-item.selected {
  @apply bg-blue-100 dark:bg-blue-900;
}

.slash-command-item:hover {
  @apply bg-gray-100 dark:bg-gray-800;
}

/* Keyboard navigation indicator */
.slash-command-item.selected::before {
  content: '▶';
  @apply text-blue-500 mr-2;
}

/* Loading state */
.slash-command-loading {
  @apply px-3 py-2 text-gray-500 italic;
}
```

**Component Updates**:
```typescript
const SlashCommandItem = ({ command, isSelected, onClick }) => {
  return (
    <div
      className={`slash-command-item ${isSelected ? 'selected' : ''}`}
      onClick={onClick}
      role="option"
      aria-selected={isSelected}
    >
      <div className="command-header">
        <span className="command-name font-semibold">{command.name}</span>
        <span className="command-aliases text-sm text-gray-500">
          {command.aliases?.join(', ')}
        </span>
      </div>
      <div className="command-summary text-sm text-gray-600">
        {command.summary}
      </div>
    </div>
  )
}
```

#### 3.2 Add Loading States (30 minutes)
**Loading State Management**:
```typescript
const SlashCommandList = ({ query, onSelect, onClose }) => {
  const [commands, setCommands] = useState<Command[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [selectedIndex, setSelectedIndex] = useState(0)
  
  const debouncedFetch = useDebouncedFetch(300)
  
  useEffect(() => {
    if (!query || query.length < 2) {
      setCommands([])
      setIsLoading(false)
      return
    }
    
    setIsLoading(true)
    debouncedFetch(query, (results) => {
      setCommands(results)
      setIsLoading(false)
      setSelectedIndex(0)
    })
  }, [query, debouncedFetch])
  
  return (
    <div className="slash-command-popover" role="listbox">
      {isLoading && (
        <div className="slash-command-loading">
          Searching commands...
        </div>
      )}
      
      {!isLoading && commands.length === 0 && query.length >= 2 && (
        <div className="slash-command-empty">
          No commands found for "{query}"
        </div>
      )}
      
      {!isLoading && commands.map((command, index) => (
        <SlashCommandItem
          key={command.slug}
          command={command}
          isSelected={index === selectedIndex}
          onClick={() => onSelect(command)}
        />
      ))}
    </div>
  )
}
```

## Testing Strategy

### Unit Tests
**File**: `resources/js/__tests__/SlashCommand.test.tsx`

```typescript
describe('SlashCommand Keyboard Navigation', () => {
  test('arrow down navigates to next item', () => {
    render(<SlashCommandList items={mockCommands} isVisible={true} />)
    
    fireEvent.keyDown(document, { key: 'ArrowDown' })
    
    expect(screen.getByText('Command 2')).toHaveClass('selected')
  })
  
  test('arrow up navigates to previous item', () => {
    render(<SlashCommandList items={mockCommands} isVisible={true} />)
    
    // Navigate down first
    fireEvent.keyDown(document, { key: 'ArrowDown' })
    fireEvent.keyDown(document, { key: 'ArrowUp' })
    
    expect(screen.getByText('Command 1')).toHaveClass('selected')
  })
  
  test('enter selects current item', () => {
    const onSelect = jest.fn()
    render(<SlashCommandList items={mockCommands} onSelect={onSelect} />)
    
    fireEvent.keyDown(document, { key: 'Enter' })
    
    expect(onSelect).toHaveBeenCalledWith(mockCommands[0])
  })
  
  test('escape closes suggestions', () => {
    const onClose = jest.fn()
    render(<SlashCommandList onClose={onClose} />)
    
    fireEvent.keyDown(document, { key: 'Escape' })
    
    expect(onClose).toHaveBeenCalled()
  })
})

describe('Performance Optimization', () => {
  test('debounces API calls', async () => {
    const fetchSpy = jest.spyOn(global, 'fetch')
    
    render(<SlashCommandInput />)
    
    // Type quickly
    fireEvent.change(input, { target: { value: '/s' } })
    fireEvent.change(input, { target: { value: '/se' } })
    fireEvent.change(input, { target: { value: '/sea' } })
    
    // Wait for debounce
    await waitFor(() => {
      expect(fetchSpy).toHaveBeenCalledTimes(1)
    })
  })
  
  test('uses cached results', () => {
    // Test cache hit behavior
  })
  
  test('cancels previous requests', () => {
    // Test request cancellation
  })
})
```

### Integration Tests
**File**: `cypress/integration/slash-command-navigation.spec.ts`

```typescript
describe('Slash Command Navigation', () => {
  it('navigates suggestions with keyboard', () => {
    cy.visit('/chat')
    cy.get('[data-testid="chat-input"]').type('/')
    
    // Wait for suggestions
    cy.get('[data-testid="suggestion-list"]').should('be.visible')
    
    // Navigate down
    cy.get('body').type('{downarrow}')
    cy.get('[data-testid="suggestion-item"]').eq(1).should('have.class', 'selected')
    
    // Navigate up
    cy.get('body').type('{uparrow}')
    cy.get('[data-testid="suggestion-item"]').eq(0).should('have.class', 'selected')
    
    // Select with enter
    cy.get('body').type('{enter}')
    cy.get('[data-testid="chat-input"]').should('contain.value', '/search')
  })
  
  it('closes suggestions with escape', () => {
    cy.visit('/chat')
    cy.get('[data-testid="chat-input"]').type('/')
    cy.get('[data-testid="suggestion-list"]').should('be.visible')
    
    cy.get('body').type('{esc}')
    cy.get('[data-testid="suggestion-list"]').should('not.exist')
  })
})
```

## Performance Targets

### Keyboard Response
- **Navigation delay**: < 16ms for smooth 60fps experience
- **Selection change**: Immediate visual feedback
- **Event handling**: No dropped keystrokes during rapid navigation

### API Performance
- **Debounce delay**: 300ms (balance responsiveness vs load)
- **Cache hit ratio**: > 70% for repeated queries
- **Request cancellation**: < 50ms to cancel and start new request

### Memory Usage
- **Cache size**: < 2MB for autocomplete data
- **Cleanup frequency**: Expired entries removed every 60 seconds
- **Memory leaks**: Zero memory leaks during extended usage

## Risk Mitigation

### Event Conflicts
- **Risk**: TipTap shortcuts conflicting with navigation
- **Mitigation**: Proper event capture and preventDefault()
- **Testing**: Comprehensive keyboard event testing

### Performance Impact
- **Risk**: Client-side cache memory growth
- **Mitigation**: LRU cache with size limits and TTL
- **Monitoring**: Cache size and hit ratio tracking

### Browser Compatibility
- **Risk**: Inconsistent keyboard behavior
- **Mitigation**: Cross-browser testing and graceful fallbacks
- **Support**: Focus on modern browsers with fallbacks

## Success Criteria

### Functional Requirements
- [ ] Arrow keys navigate suggestions without closing popover
- [ ] Enter key selects highlighted suggestion
- [ ] Escape key closes suggestions cleanly
- [ ] Circular navigation works (wrap around at ends)
- [ ] Visual feedback clear for selected item

### Performance Requirements  
- [ ] API calls debounced to 300ms delay
- [ ] Cache hit ratio > 70% for repeated queries
- [ ] Navigation response time < 16ms
- [ ] No memory leaks during extended usage
- [ ] Request cancellation works properly

### Quality Requirements
- [ ] Works consistently across all supported browsers
- [ ] Accessible with screen readers
- [ ] Mobile-friendly (if applicable)
- [ ] No JavaScript errors in console
- [ ] Smooth visual transitions

This plan ensures reliable keyboard navigation with excellent performance and user experience.