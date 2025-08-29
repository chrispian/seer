/**
 * AutocompleteEngine - Universal autocomplete for /commands, @mentions, and [[fragments]]
 */
class AutocompleteEngine {
    constructor(textarea, options = {}) {
        this.textarea = textarea;
        this.options = {
            debounceMs: 300,
            maxResults: 10,
            minSearchLength: 1,
            ...options
        };
        
        this.isActive = false;
        this.currentTrigger = null;
        this.triggerPos = -1;
        this.searchTerm = '';
        this.selectedIndex = 0;
        this.results = [];
        this.searchTimeout = null;
        
        this.triggers = {
            '/': { type: 'command', endpoint: '/api/autocomplete/commands' },
            '@': { type: 'contact', endpoint: '/api/autocomplete/contacts' },
            '[[': { type: 'fragment', endpoint: '/api/autocomplete/fragments' }
        };
        
        this.init();
    }
    
    init() {
        // Create dropdown element
        this.dropdown = this.createDropdown();
        document.body.appendChild(this.dropdown);
        
        // Bind events
        this.textarea.addEventListener('input', this.handleInput.bind(this));
        this.textarea.addEventListener('keydown', this.handleKeydown.bind(this));
        this.textarea.addEventListener('blur', this.handleBlur.bind(this));
        
        // Click outside to close
        document.addEventListener('click', this.handleDocumentClick.bind(this));
    }
    
    createDropdown() {
        const dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            display: none;
            z-index: 1000;
            max-height: 240px;
            overflow-y: auto;
            transition: all 0.2s ease-out;
            transform-origin: bottom center;
        `;
        return dropdown;
    }
    
    handleInput(e) {
        const cursorPos = this.textarea.selectionStart;
        const text = this.textarea.value;
        
        // Check for trigger patterns
        const trigger = this.detectTrigger(text, cursorPos);
        
        if (trigger) {
            this.currentTrigger = trigger;
            this.triggerPos = trigger.startPos;
            this.searchTerm = trigger.searchTerm;
            this.performSearch();
        } else {
            this.hide();
        }
    }
    
    detectTrigger(text, cursorPos) {
        // Check for [[ fragment trigger
        const beforeCursor = text.substring(0, cursorPos);
        const fragmentMatch = beforeCursor.match(/\[\[([^\]]*?)$/);
        if (fragmentMatch) {
            return {
                type: 'fragment',
                startPos: fragmentMatch.index,
                searchTerm: fragmentMatch[1],
                prefix: '[['
            };
        }
        
        // Check for @ and / triggers
        const words = beforeCursor.split(/\s/);
        const lastWord = words[words.length - 1];
        
        for (const [triggerChar, config] of Object.entries(this.triggers)) {
            if (triggerChar === '[[') continue; // Already handled above
            
            if (lastWord.startsWith(triggerChar)) {
                const searchTerm = lastWord.substring(triggerChar.length);
                const startPos = beforeCursor.lastIndexOf(lastWord);
                
                return {
                    type: config.type,
                    startPos: startPos,
                    searchTerm: searchTerm,
                    prefix: triggerChar
                };
            }
        }
        
        return null;
    }
    
    async performSearch() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        this.searchTimeout = setTimeout(async () => {
            if (!this.currentTrigger || this.searchTerm.length < this.options.minSearchLength) {
                this.hide();
                return;
            }
            
            try {
                const endpoint = this.triggers[this.currentTrigger.prefix]?.endpoint || 
                                this.triggers['[['].endpoint; // Fallback for fragment
                
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('q', this.searchTerm);
                url.searchParams.set('limit', this.options.maxResults);
                
                const response = await fetch(url.toString());
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                
                const data = await response.json();
                this.results = data.results || [];
                this.selectedIndex = 0;
                this.renderDropdown();
                this.show();
            } catch (error) {
                console.error('Autocomplete search failed:', error);
                this.hide();
            }
        }, this.options.debounceMs);
    }
    
    renderDropdown() {
        if (this.results.length === 0) {
            this.dropdown.innerHTML = '<div class="autocomplete-item autocomplete-empty">No results found</div>';
            return;
        }
        
        this.dropdown.innerHTML = this.results.map((result, index) => {
            const isSelected = index === this.selectedIndex;
            
            // For fragments, remove [[]] from display
            let displayText = result.display;
            if (result.type === 'fragment' && displayText.startsWith('[[') && displayText.endsWith(']]')) {
                displayText = displayText.slice(2, -2);
            }
            
            return `
                <div class="autocomplete-item ${isSelected ? 'selected' : ''}" data-index="${index}">
                    <div class="autocomplete-main">
                        <span class="autocomplete-display">${this.escapeHtml(displayText)}</span>
                        ${result.organization ? `<span class="autocomplete-org">${this.escapeHtml(result.organization)}</span>` : ''}
                        ${result.fragment_type ? `<span class="autocomplete-type">${result.fragment_type}</span>` : ''}
                    </div>
                    ${result.description && result.type !== 'fragment' ? `<div class="autocomplete-description">${this.escapeHtml(result.description)}</div>` : ''}
                </div>
            `;
        }).join('');
        
        // Add click handlers
        this.dropdown.querySelectorAll('.autocomplete-item').forEach((item, index) => {
            if (!item.classList.contains('autocomplete-empty')) {
                item.addEventListener('click', () => this.selectItem(index));
            }
        });
    }
    
    handleKeydown(e) {
        if (!this.isActive) return;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
                this.renderDropdown();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                this.renderDropdown();
                break;
                
            case 'Enter':
            case 'Tab':
                if (this.results.length > 0) {
                    e.preventDefault();
                    this.selectItem(this.selectedIndex);
                }
                break;
                
            case 'Escape':
                e.preventDefault();
                this.hide();
                break;
        }
    }
    
    selectItem(index) {
        if (index < 0 || index >= this.results.length) return;
        
        const selectedResult = this.results[index];
        
        // Handle different selection types
        if (selectedResult.type === 'command') {
            // For commands, execute immediately
            this.executeCommand(selectedResult.value);
        } else {
            // For contacts and fragments, insert link with ID
            this.insertLink(selectedResult);
        }
        
        this.hide();
        this.textarea.focus();
    }
    
    executeCommand(commandName) {
        // Clear the textarea and set the command
        this.textarea.value = `/${commandName}`;
        
        // Trigger Livewire input event to sync the value
        if (this.textarea.hasAttribute('wire:model.defer')) {
            this.textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        // Use a small delay to ensure Livewire has processed the input
        setTimeout(() => {
            const form = this.textarea.closest('form');
            if (form) {
                // Trigger form submission
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        }, 10);
    }
    
    insertLink(selectedResult) {
        // Calculate insertion positions
        const beforeText = this.textarea.value.substring(0, this.triggerPos);
        const afterText = this.textarea.value.substring(this.textarea.selectionStart);
        
        let finalText;
        
        if (selectedResult.type === 'contact') {
            // Insert contact link with ID: @[John Doe](contact:123)
            finalText = `@[${selectedResult.value}](contact:${selectedResult.fragment_id})`;
        } else if (selectedResult.type === 'fragment') {
            // Insert fragment link with ID: [[Fragment Title]](fragment:456)
            const title = selectedResult.value;
            finalText = `[[${title}]](fragment:${selectedResult.fragment_id})`;
        } else {
            // Fallback to display text
            finalText = selectedResult.display;
        }
        
        // Insert the text
        const newValue = beforeText + finalText + afterText;
        this.textarea.value = newValue;
        
        // Position cursor after insertion
        const newCursorPos = beforeText.length + finalText.length;
        this.textarea.setSelectionRange(newCursorPos, newCursorPos);
        
        // Trigger Livewire update
        if (this.textarea.hasAttribute('wire:model.defer')) {
            this.textarea.dispatchEvent(new Event('input'));
        }
    }
    
    show() {
        if (this.results.length === 0) return;
        
        // Position dropdown directly attached to textarea
        const rect = this.textarea.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        // Match the width of the textarea exactly
        this.dropdown.style.width = rect.width + 'px';
        this.dropdown.style.position = 'fixed';
        this.dropdown.style.bottom = (window.innerHeight - rect.top) + 'px'; // Directly attached, no gap
        this.dropdown.style.left = rect.left + 'px';
        
        // Add animation classes
        this.dropdown.style.display = 'block';
        this.dropdown.style.opacity = '0';
        this.dropdown.style.transform = 'translateY(10px)';
        
        // Trigger animation
        setTimeout(() => {
            this.dropdown.style.opacity = '1';
            this.dropdown.style.transform = 'translateY(0)';
        }, 10);
        
        this.isActive = true;
    }
    
    hide() {
        // Animate out
        this.dropdown.style.opacity = '0';
        this.dropdown.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            this.dropdown.style.display = 'none';
        }, 200);
        
        this.isActive = false;
        this.currentTrigger = null;
        this.results = [];
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = null;
        }
    }
    
    handleBlur(e) {
        // Small delay to allow for click events on dropdown
        setTimeout(() => {
            if (!this.dropdown.contains(document.activeElement)) {
                this.hide();
            }
        }, 150);
    }
    
    handleDocumentClick(e) {
        if (!this.textarea.contains(e.target) && !this.dropdown.contains(e.target)) {
            this.hide();
        }
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    destroy() {
        this.hide();
        this.dropdown.remove();
        this.textarea.removeEventListener('input', this.handleInput);
        this.textarea.removeEventListener('keydown', this.handleKeydown);
        this.textarea.removeEventListener('blur', this.handleBlur);
        document.removeEventListener('click', this.handleDocumentClick);
    }
}

// Make available globally
window.AutocompleteEngine = AutocompleteEngine;