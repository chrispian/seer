# Chat Model Picker Restoration Implementation Plan

## Phase 1: Stash Recovery and Conflict Resolution
**Duration**: 1-2 hours
**Status**: `todo`

### Apply Stashed Changes
- [ ] Switch to feature/chat-model-picker branch
- [ ] Apply stash@{0} changes carefully
- [ ] Resolve any merge conflicts with recent changes
- [ ] Test backend API endpoints work correctly

### Validate Backend Integration
- [ ] Verify ChatApiController session model handling
- [ ] Test ChatSessionController updateModel endpoint
- [ ] Confirm model persistence in database
- [ ] Validate API routes are registered correctly

## Phase 2: Missing Component Creation
**Duration**: 2-3 hours
**Status**: `backlog`

### ModelController API
- [ ] Create `app/Http/Controllers/ModelController.php`
- [ ] Implement `available()` method using ModelSelectionService
- [ ] Return formatted model list with provider grouping
- [ ] Add proper error handling and validation
- [ ] Register route in `routes/api.php`

### ChatToolbar Component
- [ ] Create `resources/js/components/ChatToolbar.tsx`
- [ ] Implement model selection dropdown using shadcn Select
- [ ] Add model badge display for current selection
- [ ] Include proper TypeScript interfaces
- [ ] Follow existing component patterns and styling

### useModelSelection Hook
- [ ] Create `resources/js/hooks/useModelSelection.ts`
- [ ] Implement model state management with React Query
- [ ] Handle session model updates via API
- [ ] Add optimistic updates for better UX
- [ ] Include error handling and fallbacks

## Phase 3: Component Integration
**Duration**: 2-3 hours
**Status**: `backlog`

### ChatComposer Integration
- [ ] Update ChatComposer to include ChatToolbar
- [ ] Pass model selection props correctly
- [ ] Ensure proper event handling
- [ ] Test toolbar positioning and styling
- [ ] Verify responsive behavior

### ChatIsland State Management
- [ ] Integrate useModelSelection hook
- [ ] Connect session model data from React Query
- [ ] Handle model updates and persistence
- [ ] Add model selection to chat API calls
- [ ] Test state synchronization

### API Integration Testing
- [ ] Test model list endpoint functionality
- [ ] Verify model selection persistence
- [ ] Validate chat API uses selected model
- [ ] Test error scenarios and fallbacks

## Phase 4: UI Polish and User Experience
**Duration**: 1-2 hours
**Status**: `backlog`

### Visual Design
- [ ] Ensure ChatToolbar matches existing design system
- [ ] Polish dropdown styling and animations
- [ ] Add proper icons and visual hierarchy
- [ ] Test dark/light mode compatibility
- [ ] Optimize for mobile responsiveness

### User Experience Enhancements
- [ ] Add loading states for model selection
- [ ] Include helpful tooltips and descriptions
- [ ] Show model capabilities (if available)
- [ ] Add keyboard navigation support
- [ ] Implement smooth transitions

### Performance Optimization
- [ ] Cache model list data effectively
- [ ] Optimize re-renders during model selection
- [ ] Minimize API calls for model updates
- [ ] Test performance with large model lists

## Phase 5: Testing and Validation
**Duration**: 1-2 hours
**Status**: `backlog`

### Functional Testing
- [ ] Test model selection with each provider (OpenAI, Anthropic, Ollama)
- [ ] Verify session persistence across browser refreshes
- [ ] Test model switching mid-conversation
- [ ] Validate error handling for unavailable models
- [ ] Test with missing or misconfigured providers

### Integration Testing
- [ ] Ensure no regressions in existing chat functionality
- [ ] Test with different session states and scenarios
- [ ] Verify compatibility with recent modular widget changes
- [ ] Test responsive behavior across devices
- [ ] Validate accessibility compliance

### User Acceptance Testing
- [ ] Test complete user workflow end-to-end
- [ ] Verify intuitive user experience
- [ ] Test performance under realistic usage
- [ ] Gather feedback on UI/UX design
- [ ] Document any discovered limitations

## Acceptance Criteria

### Core Functionality
- [ ] Users can select AI models from a dropdown in chat composer
- [ ] Selected model persists for the duration of the chat session
- [ ] Chat messages are processed using the selected model
- [ ] Model selection works with all configured AI providers
- [ ] System gracefully handles unavailable or misconfigured models

### User Interface
- [ ] Model selection dropdown is easily accessible and intuitive
- [ ] Current model is clearly indicated in the UI
- [ ] Component styling matches existing design system
- [ ] Responsive design works properly on mobile devices
- [ ] Loading and error states are handled gracefully

### Technical Implementation
- [ ] No performance regressions in chat functionality
- [ ] Proper integration with existing ModelSelectionService
- [ ] Clean separation of concerns between components
- [ ] Comprehensive error handling and fallbacks
- [ ] Code follows existing patterns and conventions

## Risk Mitigation

### Merge Conflicts
- Apply stash changes incrementally and test each component
- Resolve conflicts carefully to preserve both old and new functionality
- Use git merge tools for complex conflict resolution
- Test thoroughly after each conflict resolution

### Component Dependencies
- Verify all imported components and hooks are available
- Create missing dependencies before integrating them
- Test component imports and exports carefully
- Use proper TypeScript interfaces to catch issues early

### API Integration
- Test backend endpoints independently before frontend integration
- Validate data formats match frontend expectations
- Include proper error handling for API failures
- Test with various provider configurations

### User Experience
- Test model selection workflow with real users
- Ensure clear visual feedback for all interactions
- Handle edge cases like slow network or provider errors
- Maintain existing chat functionality expectations

## Success Metrics

### User Experience
- Model selection is discoverable and easy to use
- Users can successfully switch models without confusion
- Clear indication of which model is currently selected
- No disruption to existing chat workflow

### Technical Performance
- Model selection adds <100ms overhead to chat operations
- Model list loads quickly and is properly cached
- No memory leaks or performance degradation
- Graceful degradation when providers are unavailable

### Code Quality
- Components follow existing patterns and conventions
- Proper TypeScript typing throughout
- Clean separation of concerns
- Comprehensive error handling
- Good test coverage for new functionality