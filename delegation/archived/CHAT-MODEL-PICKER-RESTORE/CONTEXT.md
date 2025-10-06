# Chat Model Picker Restoration Context

## Current State

### What We Have
- **ModelSelectionService**: Fully implemented in `app/Services/AI/ModelSelectionService.php` from ENG-03
- **Chat Sessions**: Support `model_provider` and `model_name` columns
- **AI Provider Infrastructure**: Configured providers (OpenAI, Anthropic, Ollama) in `config/fragments.php`
- **Stashed Changes**: Complete backend integration in `stash@{0}` on `feature/chat-model-picker`

### What's Missing
- **ChatToolbar Component**: UI component for model selection dropdown
- **useModelSelection Hook**: React hook for managing model state
- **ModelController**: API controller for `/api/models/available` endpoint
- **Integration**: Proper wiring between UI and backend model selection

### Stashed Changes Analysis
From `git stash show stash@{0} -p`, the stash contains:

**Backend Changes**:
- `ChatApiController`: Session-specific model handling in chat API
- `ChatSessionController`: Model update endpoint (`updateModel` method)
- `AICredential`: Minor encryption fix
- API routes for model management

**Frontend Changes**:
- `ChatComposer`: Integration with ChatToolbar component
- `ChatIsland`: Model selection state management
- `useChatSessions`: Optimized cache invalidation
- CSS changes (reverted markdown styling)

**Missing Components** (referenced but not in stash):
- `@/components/ChatToolbar`
- `@/hooks/useModelSelection`
- `ModelController` class

## Target Architecture

### Model Selection Flow
1. **User Interface**: Dropdown in ChatToolbar shows available models
2. **State Management**: useModelSelection hook manages selected model
3. **API Integration**: ModelController provides available models list
4. **Session Persistence**: Selected model stored in chat_sessions table
5. **Chat Integration**: Selected model used in chat API calls

### Component Structure
```
ChatIsland (manages session model state)
├── ChatComposer (renders toolbar)
    ├── ChatToolbar (model selection UI)
    │   ├── Model Dropdown (shadcn Select)
    │   └── Model Badge (current selection)
    └── TipTap Editor (existing)
```

## Integration Points

### Existing Systems
- **ModelSelectionService**: Provides available models and validation
- **Chat Sessions**: Database storage for model preferences
- **AI Providers**: Actual model execution via existing providers
- **Chat API**: Streaming and response handling

### UI Patterns
- Follow existing shadcn component patterns
- Match ChatComposer styling and layout
- Use existing Badge and Select components
- Integrate with current Flux design system

### Database Schema
Chat sessions table already has:
- `model_provider` VARCHAR(255) NULLABLE
- `model_name` VARCHAR(255) NULLABLE

## Technology Stack

### Frontend
- **React + TypeScript**: Component development
- **shadcn/ui**: UI component library (Select, Badge, etc.)
- **React Query**: API state management and caching
- **Zustand**: App state management (useAppStore)

### Backend
- **Laravel 12**: API endpoints and controllers
- **ModelSelectionService**: Business logic for model selection
- **Chat Sessions**: Model preference persistence

## Dependencies

### Required Files to Create
- `resources/js/components/ChatToolbar.tsx`
- `resources/js/hooks/useModelSelection.ts`
- `app/Http/Controllers/ModelController.php`

### Files to Modify (from stash)
- `app/Http/Controllers/ChatApiController.php`
- `app/Http/Controllers/ChatSessionController.php`
- `resources/js/islands/chat/ChatComposer.tsx`
- `resources/js/islands/chat/ChatIsland.tsx`
- `resources/js/hooks/useChatSessions.ts`
- `routes/api.php`

## Risk Assessment

### Potential Conflicts
- **Recent Modular Widgets**: Changes to chat interface layout
- **CSS Changes**: Reverted markdown styling may conflict
- **API Routes**: New model endpoints may conflict with existing routes
- **Component Dependencies**: Missing component imports

### Mitigation Strategies
- Apply stash incrementally with conflict resolution
- Test each component integration separately
- Verify model availability before showing in dropdown
- Graceful fallback to default models when selection fails

## Success Criteria

### Functional Requirements
- [ ] User can select AI model from dropdown in chat composer
- [ ] Selected model persists for the session
- [ ] Chat messages use the selected model
- [ ] Model selection works with all configured providers
- [ ] UI provides clear feedback on current model

### Technical Requirements
- [ ] No regressions in existing chat functionality
- [ ] Proper error handling for unavailable models
- [ ] Efficient model list caching
- [ ] Clean integration with existing codebase patterns
- [ ] Responsive design works on mobile devices

### User Experience
- [ ] Intuitive model selection workflow
- [ ] Visual indication of selected model
- [ ] Fast model switching without page reload
- [ ] Clear model names and provider labels
- [ ] Graceful handling of model switching mid-conversation