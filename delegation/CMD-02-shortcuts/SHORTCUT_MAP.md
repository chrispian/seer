# Keyboard Shortcut Map - CMD-02

## Current Shortcuts (Existing)
- `Ctrl/Cmd + K` - Open recall palette
- `Escape` - Close modals/overlays
- `Enter` - Submit chat input / select autocomplete item
- `Tab` - Select autocomplete item
- `Arrow Up/Down` - Navigate recall palette and autocomplete
- `Ctrl/Cmd + Enter` - Force submit (existing handler)

## New Shortcuts (To Implement)

### Global Shortcuts (Available anywhere)
- `?` - Show keyboard shortcut help overlay
- `Ctrl/Cmd + /` - Toggle command palette
- `Ctrl/Cmd + Shift + C` - Clear current session
- `Ctrl/Cmd + Shift + N` - New chat session
- `Ctrl/Cmd + J` - Jump to input focus
- `Ctrl/Cmd + U` - Toggle sidebar visibility
- `Ctrl/Cmd + ,` - Open settings

### Context-Specific Shortcuts

#### Chat Interface
- `Ctrl/Cmd + L` - Clear input field
- `Ctrl/Cmd + Shift + L` - Clear entire chat
- `Ctrl/Cmd + R` - Regenerate last response
- `Ctrl/Cmd + S` - Save current conversation
- `Ctrl/Cmd + D` - Duplicate current input
- `Ctrl/Cmd + Shift + V` - Paste as plain text

#### Navigation
- `Ctrl/Cmd + 1-9` - Switch between chat sessions (if multiple)
- `Ctrl/Cmd + Tab` - Next session
- `Ctrl/Cmd + Shift + Tab` - Previous session
- `G G` - Go to top of chat
- `G Shift + G` - Go to bottom of chat

#### Command Palette Extensions
- `Ctrl/Cmd + P` - Quick file/fragment search
- `Ctrl/Cmd + Shift + P` - Command palette
- `Ctrl/Cmd + B` - Bookmarks palette

## Implementation Strategy

### 1. Global Key Handler
Create a centralized keyboard manager that:
- Respects input focus states (don't trigger when typing)
- Provides proper event delegation
- Handles key combination conflicts
- Supports customizable bindings

### 2. Context Awareness
- Check if user is in input field before triggering global shortcuts
- Respect modal/overlay states
- Handle Livewire component contexts

### 3. Accessibility Features
- Screen reader announcements for shortcut actions
- Visual feedback for shortcut activation
- Focus management for keyboard navigation
- Skip to main content shortcuts

### 4. Discoverability
- `?` key shows comprehensive help overlay
- Tooltips showing shortcuts on hover
- Contextual hints in UI
- Progressive disclosure (basic → advanced)

## Conflict Resolution

### Avoided Conflicts
- Not using common browser shortcuts (Ctrl+T, Ctrl+W, etc.)
- Respecting text input contexts
- Using Shift modifier to distinguish similar actions
- Leveraging lesser-used key combinations

### Browser Compatibility
- Prefer Ctrl on Windows/Linux, Cmd on Mac
- Fallback support for older browsers
- Test with screen readers and assistive tech

## Technical Implementation Notes

### Event Handling Architecture
1. **Global listener** on document with proper delegation
2. **Context checks** before executing actions
3. **Livewire integration** for component-specific actions
4. **Alpine.js compatibility** with existing components

### Storage and Customization
- Store custom keybindings in localStorage
- Provide reset to defaults option
- Export/import shortcut configurations
- Future: user preference sync

### Performance Considerations
- Debounced key sequences (like GG)
- Efficient event delegation
- Minimal DOM queries
- Lazy loading of shortcut help content