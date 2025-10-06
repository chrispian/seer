# DSL-UX-004: Keyboard Navigation Fixes - TODO

## Prerequisites
- [ ] **Locate TipTap files**: Find SlashCommand extension and related components
- [ ] **Test current behavior**: Reproduce keyboard navigation issues
- [ ] **Understand TipTap integration**: How extensions and event handling work

## Pre-Implementation Analysis (30 minutes)

### File Location and Structure Analysis
- [ ] **Find main extension**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`
- [ ] **Locate suggestion components**: Find SlashCommandList or similar components
- [ ] **Check package.json**: Verify TipTap version and dependencies
- [ ] **Map component hierarchy**: Understand parent-child relationships
- [ ] **Document current event flow**: How keyboard events currently flow

### Current Behavior Testing
- [ ] **Reproduce issue**: Confirm arrow keys close suggestions
- [ ] **Test other keys**: Enter, Escape, Tab behavior
- [ ] **Check browser differences**: Test in Chrome, Firefox, Safari
- [ ] **Mobile testing**: Verify behavior on mobile devices
- [ ] **Accessibility testing**: Test with screen readers

## Phase 1: Event Handling Fixes (2-3 hours)

### 1.1 Locate Current Implementation (30 minutes)
**File Identification**:
- [ ] **Main extension file**: `resources/js/islands/chat/tiptap/extensions/SlashCommand.tsx`
- [ ] **Suggestion list component**: SlashCommandList or equivalent
- [ ] **Event handler utilities**: Any existing keyboard handling code
- [ ] **TipTap configuration**: How extension is registered and configured

**Code Analysis**:
- [ ] **Current event handlers**: Document existing keyboard event handling
- [ ] **Event binding location**: Where event listeners are attached
- [ ] **State management**: How suggestion state is managed
- [ ] **Integration points**: How extension integrates with TipTap core

### 1.2 Implement Event Prevention (1 hour)
**Fix Event Handling**:
```typescript
const handleKeyDown = (event: KeyboardEvent) => {
  if (!isVisible || items.length === 0) return false

  let handled = false

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      event.stopPropagation()
      navigateDown()
      handled = true
      break
      
    case 'ArrowUp':
      event.preventDefault()
      event.stopPropagation()
      navigateUp()
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
  }

  return handled
}
```

**Implementation Tasks**:
- [ ] **Add preventDefault()**: Prevent TipTap default handling for navigation keys
- [ ] **Add stopPropagation()**: Prevent event bubbling
- [ ] **Conditional handling**: Only handle when suggestions are visible
- [ ] **Return handling status**: Indicate whether event was handled
- [ ] **Preserve other keys**: Allow normal handling for non-navigation keys

### 1.3 Implement Event Listener Management (1 hour)
**Proper Event Setup**:
```typescript
import { useEffect, useCallback } from 'react'

const SlashCommandList = ({ items, isVisible, onSelect, onClose }) => {
  const [selectedIndex, setSelectedIndex] = useState(0)
  
  const handleKeyDown = useCallback((event: KeyboardEvent) => {
    // Implementation from 1.2
  }, [isVisible, items, selectedIndex, onSelect, onClose])
  
  useEffect(() => {
    if (!isVisible) return
    
    // Use capture phase to handle before TipTap
    document.addEventListener('keydown', handleKeyDown, { capture: true })
    
    return () => {
      document.removeEventListener('keydown', handleKeyDown, { capture: true })
    }
  }, [isVisible, handleKeyDown])
  
  // Reset selection when items change
  useEffect(() => {
    setSelectedIndex(0)
  }, [items])
}
```

**Implementation Tasks**:
- [ ] **Add useCallback**: Memoize event handler to prevent re-registration
- [ ] **Use capture phase**: Handle events before TipTap sees them
- [ ] **Cleanup listeners**: Remove event listeners on unmount
- [ ] **Dependency management**: Proper useEffect dependencies
- [ ] **Selection reset**: Reset to first item when suggestions change

### 1.4 Implement Navigation Logic (30 minutes)
**Navigation Functions**:
```typescript
const navigateDown = useCallback(() => {
  setSelectedIndex(prev => {
    const next = prev + 1
    return next >= items.length ? 0 : next
  })
}, [items.length])

const navigateUp = useCallback(() => {
  setSelectedIndex(prev => {
    const next = prev - 1  
    return next < 0 ? items.length - 1 : next
  })
}, [items.length])

const selectCurrentItem = useCallback(() => {
  if (items[selectedIndex]) {
    onSelect(items[selectedIndex])
  }
}, [items, selectedIndex, onSelect])

const closePopover = useCallback(() => {
  onClose()
}, [onClose])
```

**Implementation Tasks**:
- [ ] **Circular navigation**: Wrap around at beginning/end of list
- [ ] **Bounds checking**: Handle empty arrays and out-of-bounds indices
- [ ] **Callback optimization**: Use useCallback for performance
- [ ] **Selection validation**: Ensure selected item exists before using
- [ ] **State management**: Update selectedIndex state properly

## Phase 2: Performance Optimization (1-2 hours)

### 2.1 Implement Debouncing (45 minutes)
**Debounced Fetch Hook**:
```typescript
import { debounce } from 'lodash'
import { useCallback, useRef } from 'react'

const useDebouncedFetch = (delay: number = 300) => {
  const abortControllerRef = useRef<AbortController | null>(null)
  
  const debouncedFetch = useCallback(
    debounce(async (query: string, callback: (results: Command[]) => void) => {
      // Implementation details
    }, delay),
    [delay]
  )
  
  return debouncedFetch
}
```

**Implementation Tasks**:
- [ ] **Install lodash**: Ensure lodash is available for debounce
- [ ] **Create debounced function**: Wrap API calls with debounce
- [ ] **Handle cancellation**: Cancel previous requests when new ones start
- [ ] **Query validation**: Skip API calls for very short queries (< 2 chars)
- [ ] **Error handling**: Handle network errors and aborted requests

### 2.2 Implement Client-Side Caching (45 minutes)
**Cache Class Implementation**:
```typescript
class AutocompleteCache {
  private cache = new Map<string, CacheEntry>()
  private readonly maxSize = 50
  private readonly ttl = 5 * 60 * 1000 // 5 minutes
  
  get(query: string): Command[] | null { /* Implementation */ }
  set(query: string, commands: Command[]): void { /* Implementation */ }
  clear(): void { /* Implementation */ }
  cleanup(): void { /* Implementation */ }
}
```

**Implementation Tasks**:
- [ ] **LRU cache logic**: Implement least-recently-used eviction
- [ ] **TTL handling**: Expire entries after time limit
- [ ] **Size limits**: Limit cache to prevent memory issues
- [ ] **Cache key normalization**: Consistent key format (lowercase, trimmed)
- [ ] **Periodic cleanup**: Remove expired entries regularly

### 2.3 Integrate Cache with API (30 minutes)
**Cache Integration**:
```typescript
const fetchCommands = async (query: string): Promise<Command[]> => {
  // Check cache first
  const cached = autocompleteCache.get(query)
  if (cached) return cached
  
  // Fetch from API
  const response = await fetch(`/api/autocomplete/commands?query=${query}`)
  const data = await response.json()
  const commands = data.commands || []
  
  // Cache results
  autocompleteCache.set(query, commands)
  return commands
}
```

**Implementation Tasks**:
- [ ] **Cache-first strategy**: Always check cache before API call
- [ ] **Cache population**: Store API results in cache
- [ ] **Error handling**: Handle API failures gracefully
- [ ] **Response validation**: Ensure API response has expected structure
- [ ] **Empty result caching**: Cache empty results to prevent repeated calls

## Phase 3: Visual and UX Improvements (1 hour)

### 3.1 Enhanced Visual Feedback (30 minutes)
**CSS Improvements**:
```css
.slash-command-item {
  @apply px-3 py-2 cursor-pointer transition-colors duration-150;
}

.slash-command-item.selected {
  @apply bg-blue-100 dark:bg-blue-900;
}

.slash-command-item:hover {
  @apply bg-gray-100 dark:bg-gray-800;
}

.slash-command-item.selected::before {
  content: '▶';
  @apply text-blue-500 mr-2;
}
```

**Implementation Tasks**:
- [ ] **Selection highlighting**: Clear visual indication of selected item
- [ ] **Hover states**: Different styling for mouse hover vs keyboard selection
- [ ] **Dark mode support**: Ensure styling works in dark theme
- [ ] **Smooth transitions**: Add CSS transitions for selection changes
- [ ] **Keyboard indicator**: Visual indicator that item is keyboard-selected

### 3.2 Loading States and Empty States (30 minutes)
**Component Enhancements**:
```typescript
const SlashCommandList = ({ query, onSelect, onClose }) => {
  const [commands, setCommands] = useState<Command[]>([])
  const [isLoading, setIsLoading] = useState(false)
  
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
      
      {/* Command items */}
    </div>
  )
}
```

**Implementation Tasks**:
- [ ] **Loading indicators**: Show loading state during API calls
- [ ] **Empty state messaging**: Helpful message when no results found
- [ ] **Loading state management**: Track loading status accurately
- [ ] **Accessibility**: Proper ARIA labels for states
- [ ] **Responsive design**: Ensure states look good on all screen sizes

## Testing Implementation (throughout development)

### Unit Tests (1 hour)
**File**: `resources/js/__tests__/SlashCommand.test.tsx`

**Test Implementation**:
- [ ] **Navigation tests**: Arrow key navigation in all directions
- [ ] **Selection tests**: Enter key selects correct item
- [ ] **Escape handling**: Escape key closes suggestions
- [ ] **Circular navigation**: Wrap-around at list boundaries
- [ ] **Event prevention**: Verify preventDefault() called correctly
- [ ] **Debouncing tests**: API calls properly debounced
- [ ] **Cache tests**: Cache hit/miss behavior
- [ ] **Loading states**: Loading indicators show/hide correctly

### Integration Tests (30 minutes)
**File**: `cypress/integration/slash-command.spec.ts`

**Test Implementation**:
- [ ] **End-to-end navigation**: Full keyboard navigation flow
- [ ] **Cross-browser testing**: Consistent behavior across browsers
- [ ] **Performance testing**: Response times meet requirements
- [ ] **Accessibility testing**: Screen reader compatibility
- [ ] **Mobile testing**: Touch device compatibility

### Performance Testing (30 minutes)
**Performance Validation**:
- [ ] **Memory leak testing**: Extended usage doesn't leak memory
- [ ] **Cache performance**: Hit ratio meets targets
- [ ] **API call reduction**: Debouncing reduces API calls by >70%
- [ ] **Response time testing**: Navigation response under 16ms
- [ ] **Load testing**: Performance under rapid navigation

## Quality Assurance

### Code Review Checklist
- [ ] **Event handling**: Proper preventDefault() and stopPropagation()
- [ ] **Memory management**: No memory leaks in event listeners or cache
- [ ] **Performance**: Debouncing and caching implemented correctly
- [ ] **Accessibility**: Proper ARIA roles and keyboard navigation
- [ ] **Error handling**: Graceful handling of API failures and edge cases

### Browser Compatibility Testing
- [ ] **Chrome**: Full functionality works correctly
- [ ] **Firefox**: Consistent behavior with Chrome
- [ ] **Safari**: macOS and iOS Safari compatibility  
- [ ] **Edge**: Windows compatibility
- [ ] **Mobile browsers**: Touch device compatibility

### Accessibility Validation
- [ ] **Screen reader**: Suggestions announced correctly
- [ ] **Keyboard navigation**: Works without mouse
- [ ] **Focus management**: Focus stays in editor during navigation
- [ ] **ARIA labels**: Proper roles and states for suggestions
- [ ] **Color contrast**: Selection highlighting meets WCAG standards

## Success Metrics Validation

### Functional Validation
- [ ] **Arrow navigation**: Up/down arrows navigate without closing popover
- [ ] **Selection**: Enter key selects highlighted suggestion
- [ ] **Dismissal**: Escape key closes suggestions cleanly
- [ ] **Circular navigation**: Wraps around at list boundaries
- [ ] **Visual feedback**: Clear indication of selected item

### Performance Validation
- [ ] **Debouncing**: 300ms delay before API calls
- [ ] **Cache hit ratio**: >70% for repeated queries during testing
- [ ] **Navigation response**: <16ms for keyboard navigation
- [ ] **Memory usage**: Cache stays under 2MB
- [ ] **API reduction**: >70% reduction in API calls with debouncing

### User Experience Validation
- [ ] **Smooth navigation**: No lag or stuttering during navigation
- [ ] **Clear feedback**: Users understand which item is selected
- [ ] **Fast response**: Suggestions appear quickly for new queries
- [ ] **Error recovery**: Graceful handling of network issues
- [ ] **Cross-platform**: Consistent experience across devices/browsers

## Deployment Preparation

### Pre-Deployment Checklist
- [ ] **All tests pass**: Unit, integration, and performance tests
- [ ] **Cross-browser validation**: Tested in all supported browsers
- [ ] **Performance benchmarks**: All performance targets met
- [ ] **Accessibility compliance**: WCAG guidelines followed
- [ ] **Error monitoring**: Proper error logging and monitoring

### Deployment Strategy
- [ ] **Feature flag**: Deploy behind feature flag for gradual rollout
- [ ] **Performance monitoring**: Monitor keyboard navigation performance
- [ ] **Error tracking**: Track any keyboard navigation errors
- [ ] **User feedback**: Collect feedback on navigation experience
- [ ] **Rollback plan**: Plan for quick rollback if issues discovered

### Post-Deployment Monitoring
- [ ] **Performance metrics**: Track navigation response times
- [ ] **Error rates**: Monitor keyboard event handling errors
- [ ] **User behavior**: Track usage patterns for further optimization
- [ ] **Browser analytics**: Monitor cross-browser usage and issues
- [ ] **Accessibility feedback**: Monitor accessibility compliance

This comprehensive TODO ensures reliable keyboard navigation with excellent performance and user experience across all supported platforms.