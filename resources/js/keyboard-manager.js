/**
 * KeyboardManager - Centralized keyboard shortcut handling for the chat application
 * Provides a comprehensive, accessible keyboard shortcut system
 */
class KeyboardManager {
    constructor() {
        this.shortcuts = new Map();
        this.sequences = new Map(); // For multi-key sequences like "gg"
        this.sequenceBuffer = [];
        this.sequenceTimeout = null;
        this.isEnabled = true;
        this.helpOverlayVisible = false;

        this.init();
        this.registerDefaultShortcuts();
    }

    init() {
        // Global keydown listener with proper delegation
        document.addEventListener('keydown', this.handleKeyDown.bind(this), true);

        // Reset sequence buffer on window focus loss
        window.addEventListener('blur', () => this.clearSequenceBuffer());

        // Create help overlay container
        this.createHelpOverlay();

        console.log('KeyboardManager: Initialized');
    }

    handleKeyDown(event) {
        if (!this.isEnabled) return;

        // Don't interfere with text inputs unless it's a specific override
        if (this.isInTextInput(event.target) && !this.isOverrideKey(event)) {
            return;
        }

        // Handle single key shortcuts first
        const shortcutKey = this.getShortcutKey(event);

        // Check for single key shortcuts
        if (this.shortcuts.has(shortcutKey)) {
            const shortcut = this.shortcuts.get(shortcutKey);
            if (this.canExecute(shortcut, event)) {
                event.preventDefault();
                event.stopPropagation();
                this.executeShortcut(shortcut, event);
                return;
            }
        }

        // Handle sequence shortcuts (like "gg")
        if (!event.ctrlKey && !event.metaKey && !event.altKey && event.key.length === 1) {
            this.handleSequence(event.key.toLowerCase(), event);
        }
    }

    isInTextInput(element) {
        if (!element) return false;

        const tagName = element.tagName.toLowerCase();
        const inputTypes = ['input', 'textarea', 'select'];
        const editableElements = element.contentEditable === 'true';

        return inputTypes.includes(tagName) || editableElements;
    }

    isOverrideKey(event) {
        // Keys that should work even in text inputs
        const overrideShortcuts = [
            'ctrl+k', 'cmd+k',    // Recall palette
            'escape',              // Close modals
            'ctrl+/', 'cmd+/',     // Command palette
            'ctrl+shift+c', 'cmd+shift+c' // Clear session
        ];

        const shortcutKey = this.getShortcutKey(event);
        return overrideShortcuts.includes(shortcutKey);
    }

    getShortcutKey(event) {
        const parts = [];

        if (event.ctrlKey) parts.push('ctrl');
        if (event.metaKey) parts.push('cmd');
        if (event.altKey) parts.push('alt');
        if (event.shiftKey) parts.push('shift');

        // Normalize key names
        let key = event.key.toLowerCase();
        if (key === ' ') key = 'space';
        if (key === 'arrowup') key = 'up';
        if (key === 'arrowdown') key = 'down';
        if (key === 'arrowleft') key = 'left';
        if (key === 'arrowright') key = 'right';

        parts.push(key);

        return parts.join('+');
    }

    canExecute(shortcut, event) {
        // Check context restrictions
        if (shortcut.context) {
            return this.checkContext(shortcut.context);
        }

        return true;
    }

    checkContext(context) {
        switch (context) {
            case 'chat-interface':
                return document.querySelector('[x-data*="chatInterface"]') !== null;
            case 'modal-open':
                return document.querySelector('.fixed.inset-0:not(.hidden)') !== null;
            case 'no-modal':
                return document.querySelector('.fixed.inset-0:not(.hidden)') === null;
            default:
                return true;
        }
    }

    executeShortcut(shortcut, event) {
        try {
            console.log(`KeyboardManager: Executing shortcut "${shortcut.description}"`);

            // Announce to screen readers
            this.announceAction(shortcut.description);

            // Execute the action
            if (typeof shortcut.action === 'function') {
                shortcut.action(event);
            } else if (typeof shortcut.action === 'string') {
                this.executeStringAction(shortcut.action, event);
            }
        } catch (error) {
            console.error('KeyboardManager: Error executing shortcut:', error);
        }
    }

    executeStringAction(action, event) {
        switch (action) {
            case 'focus-input':
                this.focusChatInput();
                break;
            case 'show-help':
                this.toggleHelpOverlay();
                break;
            case 'toggle-command-palette':
                this.toggleCommandPalette();
                break;
            case 'clear-input':
                this.clearChatInput();
                break;
            case 'scroll-to-top':
                this.scrollChatToTop();
                break;
            case 'scroll-to-bottom':
                this.scrollChatToBottom();
                break;
            default:
                console.warn('KeyboardManager: Unknown string action:', action);
        }
    }

    handleSequence(key, event) {
        this.sequenceBuffer.push(key);

        // Clear sequence buffer after timeout
        if (this.sequenceTimeout) {
            clearTimeout(this.sequenceTimeout);
        }

        this.sequenceTimeout = setTimeout(() => {
            this.clearSequenceBuffer();
        }, 1000);

        // Check for sequence matches
        const sequence = this.sequenceBuffer.join('');

        if (this.sequences.has(sequence)) {
            const shortcut = this.sequences.get(sequence);
            if (this.canExecute(shortcut, event)) {
                event.preventDefault();
                this.executeShortcut(shortcut, event);
                this.clearSequenceBuffer();
            }
        }

        // Limit buffer size
        if (this.sequenceBuffer.length > 3) {
            this.sequenceBuffer.shift();
        }
    }

    clearSequenceBuffer() {
        this.sequenceBuffer = [];
        if (this.sequenceTimeout) {
            clearTimeout(this.sequenceTimeout);
            this.sequenceTimeout = null;
        }
    }

    registerShortcut(keys, action, description, options = {}) {
        const shortcut = {
            keys,
            action,
            description,
            context: options.context,
            category: options.category || 'general'
        };

        if (Array.isArray(keys)) {
            // Multi-key sequence
            keys.forEach(key => this.sequences.set(key, shortcut));
        } else {
            // Single shortcut
            this.shortcuts.set(keys, shortcut);
        }

        return this;
    }

    registerDefaultShortcuts() {
        // Help and discovery
        this.registerShortcut('?', 'show-help', 'Show keyboard shortcuts help', {
            category: 'help'
        });

        // Navigation and focus
        this.registerShortcut('ctrl+j', 'focus-input', 'Focus chat input', {
            category: 'navigation'
        });

        this.registerShortcut('cmd+j', 'focus-input', 'Focus chat input', {
            category: 'navigation'
        });

        // Command palette
        this.registerShortcut('ctrl+/', 'toggle-command-palette', 'Toggle command palette', {
            category: 'commands'
        });

        this.registerShortcut('cmd+/', 'toggle-command-palette', 'Toggle command palette', {
            category: 'commands'
        });

        // Chat controls
        this.registerShortcut('ctrl+l', 'clear-input', 'Clear input field', {
            category: 'chat',
            context: 'chat-interface'
        });

        this.registerShortcut('cmd+l', 'clear-input', 'Clear input field', {
            category: 'chat',
            context: 'chat-interface'
        });

        this.registerShortcut('ctrl+shift+l', () => this.clearEntireChat(), 'Clear entire chat', {
            category: 'chat',
            context: 'chat-interface'
        });

        this.registerShortcut('cmd+shift+l', () => this.clearEntireChat(), 'Clear entire chat', {
            category: 'chat',
            context: 'chat-interface'
        });

        // Sidebar toggle
        this.registerShortcut('ctrl+u', () => this.toggleSidebar(), 'Toggle sidebar', {
            category: 'interface'
        });

        this.registerShortcut('cmd+u', () => this.toggleSidebar(), 'Toggle sidebar', {
            category: 'interface'
        });

        // Navigation sequences
        this.registerShortcut(['gg'], 'scroll-to-top', 'Go to top of chat', {
            category: 'navigation'
        });

        this.registerShortcut(['G'], 'scroll-to-bottom', 'Go to bottom of chat', {
            category: 'navigation'
        });

        // Session management
        this.registerShortcut('ctrl+shift+c', () => this.clearSession(), 'Clear current session', {
            category: 'session'
        });

        this.registerShortcut('cmd+shift+c', () => this.clearSession(), 'Clear current session', {
            category: 'session'
        });

        this.registerShortcut('ctrl+shift+n', () => this.newSession(), 'New chat session', {
            category: 'session'
        });

        this.registerShortcut('cmd+shift+n', () => this.newSession(), 'New chat session', {
            category: 'session'
        });
    }

    // Action implementations
    focusChatInput() {
        const input = document.querySelector('textarea[wire\\:model\\.defer="input"]');
        if (input) {
            input.focus();
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    clearChatInput() {
        const input = document.querySelector('textarea[wire\\:model\\.defer="input"]');
        if (input) {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        }
    }

    clearEntireChat() {
        // Trigger Livewire method if available
        if (window.Livewire) {
            const component = window.Livewire.find(this.findLivewireComponentId());
            if (component && component.call) {
                component.call('clearMessages');
            }
        }
    }

    clearSession() {
        if (confirm('Clear the current session? This cannot be undone.')) {
            this.clearEntireChat();
        }
    }

    newSession() {
        // This would depend on your routing structure
        if (confirm('Start a new session? Current conversation will be saved.')) {
            window.location.href = '/chat/new';
        }
    }

    toggleSidebar() {
        // Look for sidebar toggle button and click it
        const toggleButton = document.querySelector('[data-sidebar-toggle]') ||
                            document.querySelector('.sidebar-toggle') ||
                            document.querySelector('[x-on\\:click*="sidebar"]');

        if (toggleButton) {
            toggleButton.click();
        }
    }

    toggleCommandPalette() {
        // Check if command panel component exists
        const commandPanel = document.querySelector('[x-data*="commandPanel"]');
        if (commandPanel && window.Livewire) {
            const component = window.Livewire.find(this.findLivewireComponentId(commandPanel));
            if (component) {
                component.call('toggle');
            }
        }
    }

    scrollChatToTop() {
        const chatContainer = document.querySelector('.chat-output') ||
                             document.querySelector('#chat-output') ||
                             document.querySelector('[class*="chat"]');

        if (chatContainer) {
            chatContainer.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    scrollChatToBottom() {
        const chatContainer = document.querySelector('.chat-output') ||
                             document.querySelector('#chat-output') ||
                             document.querySelector('[class*="chat"]');

        if (chatContainer) {
            chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });
        }
    }

    findLivewireComponentId(element = document) {
        const livewireEl = element.querySelector('[wire\\:id]');
        return livewireEl ? livewireEl.getAttribute('wire:id') : null;
    }

    announceAction(description) {
        // Create temporary element for screen reader announcement
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = `Shortcut activated: ${description}`;

        document.body.appendChild(announcement);

        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    createHelpOverlay() {
        this.helpOverlay = document.createElement('div');
        this.helpOverlay.id = 'keyboard-shortcuts-help';
        this.helpOverlay.className = 'fixed inset-0 z-50 hidden';
        this.helpOverlay.setAttribute('role', 'dialog');
        this.helpOverlay.setAttribute('aria-labelledby', 'shortcuts-title');
        this.helpOverlay.setAttribute('aria-modal', 'true');

        this.helpOverlay.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="keyboardManager.hideHelpOverlay()"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-surface-card rounded-pixel border border-hot-pink/30 max-w-4xl max-h-[80vh] overflow-y-auto p-6 relative">
                    <div class="flex items-center justify-between mb-6">
                        <h2 id="shortcuts-title" class="text-2xl font-bold text-text-primary">Keyboard Shortcuts</h2>
                        <button onclick="keyboardManager.hideHelpOverlay()" class="p-2 rounded-pixel bg-gray-700 hover:bg-gray-600 text-gray-300" aria-label="Close shortcuts help">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div id="shortcuts-content" class="space-y-6">
                        <!-- Content will be populated by showHelpOverlay() -->
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(this.helpOverlay);
    }

    toggleHelpOverlay() {
        if (this.helpOverlayVisible) {
            this.hideHelpOverlay();
        } else {
            this.showHelpOverlay();
        }
    }

    showHelpOverlay() {
        this.populateHelpContent();
        this.helpOverlay.classList.remove('hidden');
        this.helpOverlayVisible = true;

        // Focus management
        const closeButton = this.helpOverlay.querySelector('button');
        if (closeButton) {
            closeButton.focus();
        }

        // Trap focus within modal
        this.trapFocus(this.helpOverlay);

        // Escape key handler
        this.helpEscHandler = (e) => {
            if (e.key === 'Escape') {
                this.hideHelpOverlay();
            }
        };
        document.addEventListener('keydown', this.helpEscHandler);

        this.announceAction('Keyboard shortcuts help opened');
    }

    hideHelpOverlay() {
        this.helpOverlay.classList.add('hidden');
        this.helpOverlayVisible = false;

        if (this.helpEscHandler) {
            document.removeEventListener('keydown', this.helpEscHandler);
            this.helpEscHandler = null;
        }

        this.announceAction('Keyboard shortcuts help closed');
    }

    populateHelpContent() {
        const content = this.helpOverlay.querySelector('#shortcuts-content');
        const categories = this.organizeShortcutsByCategory();

        content.innerHTML = Object.entries(categories).map(([category, shortcuts]) => `
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-hot-pink capitalize">${category}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    ${shortcuts.map(shortcut => `
                        <div class="flex justify-between items-center p-2 bg-surface-elevated rounded-pixel">
                            <span class="text-text-secondary">${shortcut.description}</span>
                            <kbd class="px-2 py-1 bg-gray-700 text-gray-300 rounded text-sm font-mono">
                                ${this.formatShortcutDisplay(shortcut.keys)}
                            </kbd>
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');
    }

    organizeShortcutsByCategory() {
        const categories = {};

        // Process single shortcuts
        for (const [keys, shortcut] of this.shortcuts) {
            const category = shortcut.category || 'general';
            if (!categories[category]) categories[category] = [];
            categories[category].push({ ...shortcut, keys });
        }

        // Process sequence shortcuts
        for (const [keys, shortcut] of this.sequences) {
            const category = shortcut.category || 'general';
            if (!categories[category]) categories[category] = [];
            // Only add if not already present (avoid duplicates from registerShortcut arrays)
            if (!categories[category].find(s => s.description === shortcut.description)) {
                categories[category].push({ ...shortcut, keys });
            }
        }

        return categories;
    }

    formatShortcutDisplay(keys) {
        if (Array.isArray(keys)) {
            return keys.join(', ');
        }

        return keys
            .replace(/ctrl/g, '⌃')
            .replace(/cmd/g, '⌘')
            .replace(/shift/g, '⇧')
            .replace(/alt/g, '⌥')
            .replace(/\+/g, ' + ')
            .toUpperCase();
    }

    trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        element.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });
    }

    enable() {
        this.isEnabled = true;
    }

    disable() {
        this.isEnabled = false;
    }

    destroy() {
        document.removeEventListener('keydown', this.handleKeyDown);
        window.removeEventListener('blur', this.clearSequenceBuffer);

        if (this.helpOverlay) {
            this.helpOverlay.remove();
        }

        this.shortcuts.clear();
        this.sequences.clear();
    }
}

// Initialize globally
window.keyboardManager = new KeyboardManager();

// Make available for other scripts
window.KeyboardManager = KeyboardManager;

console.log('KeyboardManager: Loaded and initialized');