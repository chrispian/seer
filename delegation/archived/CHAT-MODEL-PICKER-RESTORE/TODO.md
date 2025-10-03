# Chat Model Picker Restoration TODO

## Phase 1: Stash Recovery and Backend Validation

### Git Operations
- [ ] Switch to feature/chat-model-picker branch
- [ ] Check current branch state and conflicts
- [ ] Apply stash@{0} incrementally to avoid conflicts
- [ ] Resolve any merge conflicts with recent modular widget changes
- [ ] Test git state after applying changes

### Backend API Changes
- [ ] Apply ChatApiController changes from stash
  - [ ] Session-specific model handling in send() method
  - [ ] Model parameter validation and fallbacks
  - [ ] Integration with existing streaming functionality
- [ ] Apply ChatSessionController changes from stash
  - [ ] Add updateModel() method for model updates
  - [ ] Update validation rules to include model fields
  - [ ] Test model persistence in database
- [ ] Apply API route changes from stash
  - [ ] Add model update endpoint
  - [ ] Verify no conflicts with existing routes

### Database Validation
- [ ] Confirm chat_sessions table has model_provider and model_name columns
- [ ] Test model data persistence through API
- [ ] Verify existing sessions work without model data
- [ ] Test model updates don't break existing functionality

## Phase 2: Missing Component Development

### ModelController Creation
- [ ] Create `app/Http/Controllers/ModelController.php`
- [ ] Implement available() method
  - [ ] Use ModelSelectionService to get available models
  - [ ] Format response with provider grouping
  - [ ] Include model display names and capabilities
  - [ ] Add proper error handling for unconfigured providers
- [ ] Add route to `routes/api.php`
- [ ] Test endpoint returns proper JSON structure
- [ ] Validate response format matches frontend expectations

### ChatToolbar Component Development
- [ ] Create `resources/js/components/ChatToolbar.tsx`
- [ ] Implement component structure
  - [ ] Import required shadcn components (Select, Badge, etc.)
  - [ ] Define TypeScript interfaces for props
  - [ ] Create model selection dropdown
  - [ ] Add current model badge display
  - [ ] Include loading and error states
- [ ] Style component to match existing design
  - [ ] Follow Flux design system patterns
  - [ ] Ensure responsive behavior
  - [ ] Test dark/light mode compatibility
- [ ] Add accessibility features
  - [ ] Proper ARIA labels and roles
  - [ ] Keyboard navigation support
  - [ ] Screen reader compatibility

### useModelSelection Hook Development
- [ ] Create `resources/js/hooks/useModelSelection.ts`
- [ ] Implement hook functionality
  - [ ] Fetch available models from API
  - [ ] Manage selected model state
  - [ ] Handle model updates with optimistic updates
  - [ ] Cache model list with React Query
  - [ ] Include proper error handling
- [ ] Define TypeScript interfaces
  - [ ] Model data structure
  - [ ] Hook return type
  - [ ] Error handling types
- [ ] Add proper React Query integration
  - [ ] Cache model list effectively
  - [ ] Handle loading and error states
  - [ ] Implement optimistic updates for better UX

## Phase 3: Frontend Integration

### ChatComposer Integration
- [ ] Apply ChatComposer changes from stash
- [ ] Integrate ChatToolbar component
  - [ ] Import ChatToolbar component
  - [ ] Add toolbar to component structure
  - [ ] Pass selectedModel and onModelChange props
  - [ ] Handle toolbar positioning and layout
- [ ] Test component rendering
  - [ ] Verify no layout breaks
  - [ ] Test responsive behavior
  - [ ] Check integration with TipTap editor

### ChatIsland State Management
- [ ] Apply ChatIsland changes from stash
- [ ] Integrate useModelSelection hook
  - [ ] Import and initialize hook
  - [ ] Connect session model data
  - [ ] Handle model updates and persistence
  - [ ] Pass model data to ChatComposer
- [ ] Update chat API integration
  - [ ] Include selected model in API calls
  - [ ] Handle model parameter formatting
  - [ ] Test with different model providers

### useChatSessions Hook Updates
- [ ] Apply useChatSessions changes from stash
- [ ] Optimize cache invalidation for model updates
- [ ] Test session data updates work correctly
- [ ] Verify no performance regressions

## Phase 4: API Integration and Testing

### Model API Testing
- [ ] Test `/api/models/available` endpoint
  - [ ] Verify response format is correct
  - [ ] Test with different provider configurations
  - [ ] Handle missing or misconfigured providers
  - [ ] Test error responses are properly formatted
- [ ] Test model update API
  - [ ] Verify model persistence in database
  - [ ] Test validation and error handling
  - [ ] Check optimistic updates work correctly

### Chat API Integration
- [ ] Test selected model is used in chat API calls
- [ ] Verify model parameters are passed correctly
- [ ] Test with each configured provider (OpenAI, Anthropic, Ollama)
- [ ] Validate fallback behavior when model unavailable
- [ ] Test error handling for invalid model selections

### Frontend-Backend Integration
- [ ] Test complete model selection workflow
- [ ] Verify state synchronization between components
- [ ] Test session persistence across browser refreshes
- [ ] Validate model switching works mid-conversation
- [ ] Test loading states and error handling

## Phase 5: UI Polish and User Experience

### Visual Design Polish
- [ ] Fine-tune ChatToolbar styling
  - [ ] Match existing component spacing and sizing
  - [ ] Ensure proper visual hierarchy
  - [ ] Test with different screen sizes
  - [ ] Verify icon and text alignment
- [ ] Optimize dropdown appearance
  - [ ] Style dropdown options properly
  - [ ] Add hover and focus states
  - [ ] Include proper visual separators
  - [ ] Test dropdown positioning and overflow

### Interaction Design
- [ ] Add smooth transitions and animations
- [ ] Implement proper loading states
  - [ ] Model list loading indicator
  - [ ] Model selection updating state
  - [ ] Graceful error state display
- [ ] Include helpful tooltips and descriptions
- [ ] Add keyboard shortcuts if appropriate
- [ ] Test touch interactions on mobile

### Performance Optimization
- [ ] Optimize component re-renders
- [ ] Cache model list data effectively
- [ ] Minimize API calls during model selection
- [ ] Test performance with large model lists
- [ ] Profile memory usage and optimize if needed

## Phase 6: Comprehensive Testing

### Unit Testing
- [ ] Test ChatToolbar component
  - [ ] Component rendering with different props
  - [ ] Model selection event handling
  - [ ] Loading and error state rendering
  - [ ] Accessibility features
- [ ] Test useModelSelection hook
  - [ ] Model list fetching and caching
  - [ ] Model selection state management
  - [ ] Error handling scenarios
  - [ ] Optimistic updates

### Integration Testing
- [ ] Test complete model selection workflow
  - [ ] User selects model from dropdown
  - [ ] Model persists in session
  - [ ] Chat messages use selected model
  - [ ] Model selection survives page refresh
- [ ] Test with different provider configurations
  - [ ] All providers configured correctly
  - [ ] Some providers missing configuration
  - [ ] All providers unavailable
  - [ ] Mixed availability scenarios

### User Experience Testing
- [ ] Test with realistic user scenarios
  - [ ] First-time user selects model
  - [ ] User switches models mid-conversation
  - [ ] User navigates between sessions with different models
  - [ ] User encounters error scenarios
- [ ] Test responsive behavior
  - [ ] Desktop browser testing
  - [ ] Mobile browser testing
  - [ ] Tablet browser testing
  - [ ] Different viewport sizes

### Regression Testing
- [ ] Verify existing chat functionality unchanged
  - [ ] Chat message sending and receiving
  - [ ] File attachments and uploads
  - [ ] Command execution functionality
  - [ ] Session management features
- [ ] Test compatibility with recent changes
  - [ ] Modular widget system integration
  - [ ] Sidebar and layout changes
  - [ ] Any recent API modifications

## Verification and Acceptance

### Functional Verification
- [ ] Model selection dropdown appears in chat composer
- [ ] All configured models appear in dropdown with proper names
- [ ] Selected model is clearly indicated in UI
- [ ] Model selection persists for session duration
- [ ] Chat API uses selected model for processing
- [ ] Error handling works for unavailable models

### Technical Verification
- [ ] No performance regressions in chat functionality
- [ ] Proper TypeScript typing throughout new components
- [ ] Clean integration with existing codebase patterns
- [ ] Comprehensive error handling and fallbacks
- [ ] Proper React Query cache management

### User Experience Verification
- [ ] Model selection workflow is intuitive and discoverable
- [ ] Clear visual feedback for all user interactions
- [ ] Responsive design works across all target devices
- [ ] Loading states provide appropriate feedback
- [ ] Error states are helpful and actionable

### Documentation and Handoff
- [ ] Document new components and their usage
- [ ] Update any relevant API documentation
- [ ] Create usage examples for model selection
- [ ] Document known limitations or considerations
- [ ] Prepare demo/screenshots for user review

## Post-Implementation Tasks

### Code Quality
- [ ] Run linting and formatting tools
- [ ] Optimize imports and remove unused code
- [ ] Add any missing TypeScript types
- [ ] Clean up console.log statements and debug code

### Performance Monitoring
- [ ] Monitor chat performance after deployment
- [ ] Track model selection usage patterns
- [ ] Watch for any error patterns in logs
- [ ] Gather user feedback on the feature

### Future Enhancements
- [ ] Consider adding model descriptions/capabilities
- [ ] Evaluate adding model performance indicators
- [ ] Plan for additional AI provider integrations
- [ ] Consider session-level model preferences