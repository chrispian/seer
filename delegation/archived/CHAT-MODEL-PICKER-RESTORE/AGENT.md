# Chat Model Picker Restoration Agent Profile

## Mission
Restore the lost user model selection feature that allows users to select AI models (OpenAI, Anthropic, Ollama) for each chat session and persist the selection.

## Workflow
- Use unified diffs for edits where possible
- Ask for direction if not sure about implementation details  
- Do not commit until user approves that the feature is working
- Use sub-agents for complex component development
- Start by applying stashed changes, then create missing components

## Quality Standards
- Model selection persists for the session duration
- Dropdown shows available models from ModelSelectionService
- Selected model is used for chat API calls
- UI integrates cleanly with existing ChatComposer
- Backward compatibility with sessions without model selection
- Clear visual indication of selected model

## Deliverables
- Apply stashed changes from feature/chat-model-picker branch
- Create ChatToolbar component with model dropdown
- Create useModelSelection hook for state management
- Create ModelController for available models API
- Integration testing with existing chat functionality
- UI polish and user experience optimization

## Technical Focus
- Leverage existing ModelSelectionService from ENG-03
- Use existing UI patterns (shadcn dropdowns, badges)
- Integrate with current ChatComposer and ChatIsland
- Persist model selection in chat_sessions table
- Handle model availability and fallbacks gracefully

## Communication
- Report progress on stash application and component creation
- Show screenshots of model selection in action
- Document any conflicts with recent modular widget changes
- Highlight performance impact and user experience improvements

## Safety Notes
- Preserve existing chat functionality during restoration
- Test with all configured AI providers (OpenAI, Anthropic, Ollama)
- Ensure graceful degradation when models are unavailable
- Validate API changes don't break existing chat sessions
- Test model selection persistence across browser sessions

## Integration Points
- Existing ModelSelectionService (app/Services/AI/ModelSelectionService.php)
- Current ChatComposer and ChatIsland components
- Chat session API endpoints and database schema
- AI provider configuration in config/fragments.php
- Existing chat message streaming functionality

## Sub-Agent Specializations
- **Stash Recovery Agent**: Apply stashed changes and resolve conflicts
- **UI Component Agent**: Create ChatToolbar and model selection dropdown
- **API Integration Agent**: Create ModelController and endpoints
- **State Management Agent**: Implement useModelSelection hook