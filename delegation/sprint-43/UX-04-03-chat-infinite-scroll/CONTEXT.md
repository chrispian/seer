# Chat Infinite Scroll Context

## Current Chat Architecture

### Frontend Components
```typescript
// ChatIsland.tsx (resources/js/islands/chat/ChatIsland.tsx)
- Loads ALL messages from sessionDetailsQuery.data.session.messages
- Creates ChatMessage[] array with unique React keys
- Uses saveMessagesToSession for message persistence
- Real-time streaming via EventSource for new messages

// useChatSessionDetails hook
- Fetches complete session data including all messages
- Currently no pagination or limiting
- Uses React Query for caching and state management

// Message Structure
interface ChatMessage {
  id: string              # React key (session-{sessionId}-{type}-{msgId})
  role: 'user' | 'assistant'
  md: string             # Message content
  messageId?: string     # Server message ID
  fragmentId?: string    # Associated fragment ID
  isBookmarked?: boolean
}
```

### Backend API Structure
```php
// Current Session API (no pagination)
GET /api/chat/sessions/{id}
- Returns complete session with ALL messages
- No limit or offset parameters
- May include hundreds of messages for long chats

// Message Storage
- Messages stored in chat_sessions.messages JSON column
- No separate messages table for pagination
- Limited querying capabilities for large datasets
```

## Target Infinite Scroll Architecture

### Message Pagination Strategy
```typescript
// Enhanced message loading with pagination
interface MessagePage {
  messages: ChatMessage[]
  hasMore: boolean
  nextCursor?: string
  totalCount: number
}

interface InfiniteMessageState {
  pages: MessagePage[]
  pageParams: (string | null)[]
  hasNextPage: boolean
  isFetching: boolean
  isFetchingNextPage: boolean
}
```

### API Enhancement Requirements
```php
// Enhanced Session API with pagination
GET /api/chat/sessions/{id}/messages?limit=10&before=cursor
- limit: Number of messages to return (default: 10)
- before: Cursor for pagination (message timestamp or ID)
- Returns: MessagePage with pagination metadata

// Response Structure
{
  "messages": [...],
  "pagination": {
    "hasMore": true,
    "nextCursor": "2024-01-01T12:00:00Z",
    "totalCount": 150,
    "limit": 10
  }
}
```

### React Query Infinite Implementation
```typescript
// useInfiniteChatMessages hook
import { useInfiniteQuery } from '@tanstack/react-query'

export const useInfiniteChatMessages = (sessionId: string) => {
  return useInfiniteQuery({
    queryKey: ['chat-messages', sessionId],
    queryFn: ({ pageParam }) => fetchChatMessages(sessionId, {
      limit: 20,
      before: pageParam
    }),
    initialPageParam: null,
    getNextPageParam: (lastPage) => lastPage.pagination.nextCursor,
    select: (data) => ({
      pages: data.pages,
      messages: data.pages.flatMap(page => page.messages).reverse(),
      hasMore: data.pages[data.pages.length - 1]?.pagination.hasMore ?? false
    })
  })
}
```

## Scroll Detection Implementation

### Intersection Observer Strategy
```typescript
// useScrollTrigger hook for detecting near-top scroll
export const useScrollTrigger = (
  onTrigger: () => void,
  threshold = 100 // pixels from top
) => {
  const triggerRef = useRef<HTMLDivElement>(null)
  
  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          onTrigger()
        }
      },
      {
        root: null,
        rootMargin: `${threshold}px 0px 0px 0px`,
        threshold: 0
      }
    )
    
    if (triggerRef.current) {
      observer.observe(triggerRef.current)
    }
    
    return () => observer.disconnect()
  }, [onTrigger, threshold])
  
  return triggerRef
}
```

### Scroll Position Management
```typescript
// useScrollPosition hook for maintaining position during loads
export const useScrollPosition = () => {
  const scrollElementRef = useRef<HTMLDivElement>(null)
  const savedScrollHeight = useRef<number>(0)
  
  const saveScrollPosition = () => {
    if (scrollElementRef.current) {
      savedScrollHeight.current = scrollElementRef.current.scrollHeight
    }
  }
  
  const restoreScrollPosition = () => {
    if (scrollElementRef.current) {
      const newScrollHeight = scrollElementRef.current.scrollHeight
      const heightDifference = newScrollHeight - savedScrollHeight.current
      scrollElementRef.current.scrollTop += heightDifference
    }
  }
  
  return {
    scrollElementRef,
    saveScrollPosition,
    restoreScrollPosition
  }
}
```

## Enhanced ChatIsland Implementation

### Component Structure
```typescript
const ChatIsland = () => {
  // Replace current message loading with infinite query
  const {
    data: messageData,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isLoading
  } = useInfiniteChatMessages(currentSessionId)
  
  const messages = messageData?.messages ?? []
  const { scrollElementRef, saveScrollPosition, restoreScrollPosition } = useScrollPosition()
  
  // Scroll trigger for loading more messages
  const triggerRef = useScrollTrigger(() => {
    if (hasNextPage && !isFetchingNextPage) {
      saveScrollPosition()
      fetchNextPage().then(() => {
        // Small delay to ensure DOM update
        setTimeout(restoreScrollPosition, 10)
      })
    }
  })
  
  return (
    <div className="flex flex-col h-full">
      <div ref={scrollElementRef} className="flex-1 min-h-0 pb-3">
        {/* Loading indicator at top */}
        {isFetchingNextPage && (
          <div className="flex justify-center p-4">
            <LoadingSpinner />
          </div>
        )}
        
        {/* Scroll trigger element */}
        <div ref={triggerRef} className="h-1" />
        
        <ChatTranscript
          messages={messages}
          onMessageDelete={handleMessageDelete}
          onMessageBookmarkToggle={handleMessageBookmarkToggle}
        />
      </div>
      
      <div className="flex-shrink-0 border-t border-border">
        <ChatComposer ... />
      </div>
    </div>
  )
}
```

## Performance Optimization

### Message Virtualization
```typescript
// For very long chat histories (1000+ messages)
import { FixedSizeList as List } from 'react-window'

const VirtualizedMessageList = ({ messages }: { messages: ChatMessage[] }) => {
  const Row = ({ index, style }: { index: number, style: React.CSSProperties }) => (
    <div style={style}>
      <MessageComponent message={messages[index]} />
    </div>
  )
  
  return (
    <List
      height={600} // Container height
      itemCount={messages.length}
      itemSize={100} // Estimated message height
      itemData={messages}
    >
      {Row}
    </List>
  )
}
```

### Memory Management
```typescript
// Limit total messages in memory (optional optimization)
const MAX_MESSAGES_IN_MEMORY = 200

const useMessageMemoryManagement = (messages: ChatMessage[]) => {
  return useMemo(() => {
    if (messages.length <= MAX_MESSAGES_IN_MEMORY) {
      return messages
    }
    
    // Keep most recent messages
    return messages.slice(-MAX_MESSAGES_IN_MEMORY)
  }, [messages])
}
```

## Backend Implementation

### Database Optimization
```sql
-- Add indexes for message pagination
ALTER TABLE chat_sessions ADD COLUMN message_count INTEGER DEFAULT 0;

-- Create view for message pagination (if separate messages table created)
CREATE VIEW chat_messages_paginated AS
SELECT 
  cs.id as session_id,
  msg.content,
  msg.type,
  msg.created_at,
  msg.id as message_id
FROM chat_sessions cs
CROSS JOIN LATERAL jsonb_array_elements(cs.messages) WITH ORDINALITY AS msg(content, position)
ORDER BY cs.id, msg.position DESC;
```

### Controller Enhancement
```php
// ChatSessionController enhancement
class ChatSessionController extends Controller 
{
    public function getMessages(Request $request, $sessionId) 
    {
        $limit = $request->get('limit', 10);
        $before = $request->get('before');
        
        $session = ChatSession::findOrFail($sessionId);
        $messages = collect($session->messages ?? []);
        
        // Apply cursor-based pagination
        if ($before) {
            $beforeTimestamp = Carbon::parse($before);
            $messages = $messages->filter(function ($message) use ($beforeTimestamp) {
                return Carbon::parse($message['created_at'])->lt($beforeTimestamp);
            });
        }
        
        // Sort by newest first and take limit
        $paginatedMessages = $messages
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
            
        $hasMore = $messages->count() > $limit;
        $nextCursor = $hasMore ? $paginatedMessages->last()['created_at'] : null;
        
        return response()->json([
            'messages' => $paginatedMessages,
            'pagination' => [
                'hasMore' => $hasMore,
                'nextCursor' => $nextCursor,
                'totalCount' => $messages->count(),
                'limit' => $limit
            ]
        ]);
    }
}
```

## Real-time Integration

### Preserving Message Streaming
```typescript
// Ensure new streamed messages append to existing infinite query
const handleNewStreamedMessage = (newMessage: ChatMessage) => {
  queryClient.setQueryData(
    ['chat-messages', currentSessionId],
    (oldData: InfiniteData<MessagePage>) => {
      if (!oldData) return oldData
      
      // Add new message to the most recent page
      const newPages = [...oldData.pages]
      if (newPages.length > 0) {
        newPages[0] = {
          ...newPages[0],
          messages: [newMessage, ...newPages[0].messages]
        }
      }
      
      return {
        ...oldData,
        pages: newPages
      }
    }
  )
}
```

## Testing Strategy

### Performance Testing
```typescript
// Test with various message counts
const testCases = [
  { messageCount: 10, expectedLoadTime: '<100ms' },
  { messageCount: 100, expectedLoadTime: '<200ms' },
  { messageCount: 1000, expectedLoadTime: '<500ms' },
  { messageCount: 5000, expectedLoadTime: '<1000ms' }
]

// Memory usage testing
const measureMemoryUsage = () => {
  if (performance.memory) {
    return {
      used: performance.memory.usedJSHeapSize,
      total: performance.memory.totalJSHeapSize,
      limit: performance.memory.jsHeapSizeLimit
    }
  }
}
```

### Scroll Behavior Testing
```typescript
// Test scroll position maintenance
const testScrollMaintenance = async () => {
  const scrollPosition = container.scrollTop
  await triggerLoadMore()
  await waitFor(() => expect(container.scrollTop).toBeGreaterThan(scrollPosition))
}
```

## Error Handling

### Graceful Degradation
```typescript
// Fallback to full message loading if infinite scroll fails
const useChatMessagesWithFallback = (sessionId: string) => {
  const infiniteQuery = useInfiniteChatMessages(sessionId)
  const fallbackQuery = useChatSessionDetails(sessionId, {
    enabled: infiniteQuery.isError
  })
  
  if (infiniteQuery.isError && fallbackQuery.data) {
    return {
      messages: fallbackQuery.data.session.messages,
      isLoading: fallbackQuery.isLoading,
      error: null
    }
  }
  
  return {
    messages: infiniteQuery.data?.messages ?? [],
    isLoading: infiniteQuery.isLoading,
    error: infiniteQuery.error
  }
}
```

## Accessibility Considerations

### Keyboard Navigation
- Ensure tab navigation works properly with virtualized content
- Provide keyboard shortcuts for jumping to top/bottom of chat
- Announce loading states to screen readers
- Maintain focus when new messages load

### Screen Reader Support
```typescript
// Add live region for new message announcements
<div aria-live="polite" aria-label="New messages">
  {isFetchingNextPage && "Loading more messages..."}
</div>
```