/**
 * LinkHandler - Handles clicks on @mentions and [[fragment]] links in chat
 */
class LinkHandler {
    constructor() {
        this.modalContainer = null;
        this.init();
    }
    
    init() {
        // Create modal container
        this.createModalContainer();
        
        // Listen for clicks on the chat output area
        this.attachLinkListeners();
    }
    
    createModalContainer() {
        this.modalContainer = document.createElement('div');
        this.modalContainer.id = 'link-modal-container';
        this.modalContainer.className = 'fixed inset-0 z-50 hidden';
        this.modalContainer.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="event.stopPropagation(); linkHandler.closeModal()"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div id="link-modal-content" class="relative max-w-6xl max-h-[90vh] overflow-auto">
                    <!-- Modal content will be inserted here -->
                </div>
            </div>
        `;
        document.body.appendChild(this.modalContainer);
    }
    
    attachLinkListeners() {
        console.log('LinkHandler: Attaching event listeners...');
        
        // Single event listener that handles both detection and prevention
        document.addEventListener('click', (e) => {
            const target = e.target;
            
            // Skip if click is within bookmark widget
            if (target.closest('[x-data*="bookmarkWidget"]')) {
                return;
            }
            
            // Debug logging for any click
            console.log('LinkHandler: Click detected on', target.tagName, target.className, target.textContent);
            
            // Check if clicked element or its parent contains a link pattern
            const linkElement = this.findLinkElement(target);
            if (linkElement) {
                console.log('LinkHandler: Found link element', linkElement);
                e.preventDefault();
                e.stopPropagation();
                this.handleLinkClick(linkElement);
                return false;
            }
            
            // Also prevent default for our custom link classes (fallback)
            if (target.classList && (target.classList.contains('contact-link') || target.classList.contains('fragment-link'))) {
                console.log('LinkHandler: Fallback prevention for custom link class', target.className);
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        console.log('LinkHandler: Event listeners attached successfully');
    }
    
    findLinkElement(element) {
        // Check if we clicked on a link with our custom classes and data attributes
        let current = element;
        for (let i = 0; i < 3; i++) { // Check element and up to 2 levels up
            if (!current) break;
            
            // Check for contact links with data attributes
            if (current.classList && current.classList.contains('contact-link')) {
                return {
                    type: 'contact',
                    name: current.dataset.contactName || current.textContent.replace(/[@]/g, ''),
                    id: current.dataset.contactId,
                    element: current
                };
            }
            
            // Check for fragment links with data attributes  
            if (current.classList && current.classList.contains('fragment-link')) {
                return {
                    type: 'fragment',
                    title: current.dataset.fragmentTitle || current.textContent.replace(/\[\[|\]\]/g, ''),
                    id: current.dataset.fragmentId,
                    element: current
                };
            }
            
            current = current.parentElement;
        }
        
        // Fallback: Look for link patterns in the element text and its parents
        current = element;
        for (let i = 0; i < 5; i++) { // Check up to 5 levels up
            if (!current) break;
            
            const text = current.textContent || '';
            
            // Check for contact links: @[Name](contact:123)
            const contactMatch = text.match(/@\[([^\]]+)\]\(contact:(\d+)\)/);
            if (contactMatch) {
                return {
                    type: 'contact',
                    name: contactMatch[1],
                    id: contactMatch[2],
                    element: current
                };
            }
            
            // Check for fragment links: [[Title]](fragment:456)
            const fragmentMatch = text.match(/\[\[([^\]]+)\]\]\(fragment:(\d+)\)/);
            if (fragmentMatch) {
                return {
                    type: 'fragment',
                    title: fragmentMatch[1],
                    id: fragmentMatch[2],
                    element: current
                };
            }
            
            current = current.parentElement;
        }
        
        return null;
    }
    
    async handleLinkClick(linkData) {
        try {
            if (linkData.type === 'contact') {
                await this.showContactModal(linkData.id, linkData.name);
            } else if (linkData.type === 'fragment') {
                await this.showFragmentModal(linkData.id, linkData.title);
            }
        } catch (error) {
            console.error('Error handling link click:', error);
            this.showErrorModal('Failed to load content');
        }
    }
    
    async showContactModal(contactId, name) {
        this.showLoadingModal();
        
        try {
            const response = await fetch(`/api/contacts/${contactId}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const contactData = await response.json();
            this.renderContactModal(contactData);
        } catch (error) {
            this.showErrorModal(`Failed to load contact: ${name}`);
        }
    }
    
    async showFragmentModal(fragmentId, title) {
        this.showLoadingModal();
        
        try {
            const response = await fetch(`/api/fragments/${fragmentId}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const fragmentData = await response.json();
            this.renderFragmentModal(fragmentData);
        } catch (error) {
            this.showErrorModal(`Failed to load fragment: ${title}`);
        }
    }
    
    renderContactModal(contactData) {
        const modalContent = document.getElementById('link-modal-content');
        modalContent.innerHTML = `
            <div class="pixel-card pixel-card-blue glow-blue bg-surface-card p-6 rounded-pixel border-2 border-electric-blue/40">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-electric-blue">Contact Details</h2>
                    <div class="flex items-center space-x-2">
                        <button onclick="linkHandler.copyCard(this)" class="text-xs bg-neon-cyan/20 hover:bg-neon-cyan/30 text-neon-cyan px-2 py-1 rounded-pixel border border-neon-cyan/40 transition-colors">
                            📋 Copy
                        </button>
                        <button onclick="event.stopPropagation(); linkHandler.closeModal()" class="p-1 rounded bg-gray-900 border border-gray-700 text-gray-400 hover:bg-hot-pink/20 hover:text-hot-pink hover:border-hot-pink/40 transition-all">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div x-data="contactCard(${JSON.stringify(contactData).replace(/"/g, '&quot;')})">
                    <div class="contact-card-content">
                        <!-- Condensed view -->
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-hot-pink/20 rounded-full flex items-center justify-center">
                                    <span class="text-lg font-bold text-hot-pink">\${getInitials()}</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-text-primary" x-text="contact.full_name || 'Unnamed Contact'"></h3>
                                    <p class="text-sm text-text-muted" x-text="contact.organization || 'No organization'"></p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-2">
                                <div x-show="contact.emails && contact.emails.length" class="flex items-center space-x-2">
                                    <span class="text-xs text-electric-blue font-medium">EMAIL:</span>
                                    <span class="text-sm text-text-secondary" x-text="contact.emails ? contact.emails[0] : 'None'"></span>
                                </div>
                                <div x-show="contact.phones && contact.phones.length" class="flex items-center space-x-2">
                                    <span class="text-xs text-hot-pink font-medium">PHONE:</span>
                                    <span class="text-sm text-text-secondary" x-text="contact.phones ? contact.phones[0] : 'None'"></span>
                                </div>
                            </div>
                            
                            <button x-show="!expanded" @click="expanded = !expanded" class="text-xs text-neon-cyan hover:text-bright-pink transition-colors">
                                ▼ Show More Details
                            </button>
                        </div>
                        
                        <!-- Expanded view -->
                        <div x-show="expanded" x-transition class="mt-4 pt-4 border-t border-text-muted/20">
                            <div class="space-y-4">
                                <!-- All emails -->
                                <div x-show="contact.emails && contact.emails.length > 1">
                                    <h4 class="text-sm font-medium text-electric-blue mb-2">All Email Addresses:</h4>
                                    <template x-for="email in contact.emails">
                                        <div class="text-sm text-text-secondary mb-1" x-text="email"></div>
                                    </template>
                                </div>
                                
                                <!-- All phones -->
                                <div x-show="contact.phones && contact.phones.length > 1">
                                    <h4 class="text-sm font-medium text-hot-pink mb-2">All Phone Numbers:</h4>
                                    <template x-for="phone in contact.phones">
                                        <div class="text-sm text-text-secondary mb-1" x-text="phone"></div>
                                    </template>
                                </div>
                                
                                <!-- Fragment info -->
                                <div class="bg-surface-elevated p-3 rounded-pixel">
                                    <h4 class="text-sm font-medium text-deep-purple mb-2">Fragment Details:</h4>
                                    <p class="text-xs text-text-muted">ID: <span x-text="contact.fragment_id"></span></p>
                                    <p class="text-sm text-text-secondary mt-1" x-text="contact.fragment?.message || 'No message'"></p>
                                </div>
                            </div>
                            
                            <button @click="expanded = !expanded" class="text-xs text-neon-cyan hover:text-bright-pink transition-colors mt-4">
                                ▲ Show Less
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.showModal();
    }
    
    renderFragmentModal(fragmentData) {
        const modalContent = document.getElementById('link-modal-content');
        const fragmentType = fragmentData.type || 'unknown';
        const typeColors = {
            'log': 'hot-pink',
            'note': 'electric-blue', 
            'todo': 'neon-cyan',
            'contact': 'deep-purple',
            'unknown': 'text-muted'
        };
        const typeColor = typeColors[fragmentType] || 'text-muted';
        
        modalContent.innerHTML = `
            <div class="bg-surface-2 p-6 rounded-pixel border border-thin border-hot-pink/30" style="min-width: 750px;">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <h2 class="text-lg font-medium text-text-primary">Fragment</h2>
                        <span class="text-xs bg-${typeColor}/20 text-${typeColor} px-2 py-1 rounded-pixel border border-${typeColor}/40">${fragmentType.toUpperCase()}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="linkHandler.copyCard(this)" class="p-1.5 bg-gray-700 hover:bg-neon-cyan/20 text-gray-400 hover:text-neon-cyan rounded border border-gray-600 hover:border-neon-cyan/40 hover:shadow-sm hover:shadow-neon-cyan/20 transition-all" title="Copy fragment">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <button onclick="event.stopPropagation(); linkHandler.closeModal()" class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 text-gray-400 hover:text-hot-pink rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all" title="Close">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="fragment-card-content space-y-4">
                    <!-- Main message -->
                    <div class="bg-surface-elevated p-4 rounded-pixel">
                        <div class="text-sm font-medium text-text-primary">
                            ${this.formatFragmentMessage(fragmentData.message)}
                        </div>
                    </div>
                    
                    <!-- Metadata -->
                    <div class="flex justify-between text-xs text-text-muted">
                        <div class="flex items-center space-x-2">
                            <span>Created:</span>
                            <span>${new Date(fragmentData.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span>ID:</span>
                            <span class="font-mono">${fragmentData.id}</span>
                        </div>
                    </div>
                    
                    <!-- Tags if any -->
                    ${fragmentData.tags && fragmentData.tags.length ? `
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-text-muted">Tags:</span>
                            <div class="flex flex-wrap gap-1">
                                ${fragmentData.tags.map(tag => `
                                    <span class="bg-electric-blue/20 text-electric-blue px-2 py-0.5 rounded border border-electric-blue/40">${tag}</span>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        this.showModal();
    }
    
    formatFragmentMessage(message) {
        // Basic markdown-style formatting
        return message
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code class="bg-surface-elevated px-1 rounded">$1</code>')
            .replace(/\n/g, '<br>');
    }
    
    showLoadingModal() {
        const modalContent = document.getElementById('link-modal-content');
        modalContent.innerHTML = `
            <div class="pixel-card pixel-card-blue glow-blue bg-surface-card p-8 rounded-pixel border-2 border-electric-blue/40 text-center">
                <div class="text-electric-blue text-lg">⏳ Loading...</div>
            </div>
        `;
        this.showModal();
    }
    
    showErrorModal(message) {
        const modalContent = document.getElementById('link-modal-content');
        modalContent.innerHTML = `
            <div class="pixel-card bg-surface-card p-6 rounded-pixel border-2 border-red-500/40 text-center">
                <div class="text-red-400 text-lg mb-2">❌ Error</div>
                <div class="text-text-secondary">${message}</div>
                <button onclick="linkHandler.closeModal()" class="mt-4 px-4 py-2 bg-red-500/20 text-red-400 rounded-pixel hover:bg-red-500/30 transition-colors">
                    Close
                </button>
            </div>
        `;
        this.showModal();
    }
    
    showModal() {
        this.modalContainer.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    closeModal() {
        this.modalContainer.classList.add('hidden');
        document.body.style.overflow = 'auto';
        
        // Temporarily disable bookmark widget clicks to prevent interference
        const bookmarkWidget = document.querySelector('[x-data*="bookmarkWidget"]');
        if (bookmarkWidget) {
            bookmarkWidget.style.pointerEvents = 'none';
            setTimeout(() => {
                if (bookmarkWidget.style) {
                    bookmarkWidget.style.pointerEvents = 'auto';
                }
            }, 300);
        }
        
        // Dispatch a custom event to notify other components
        document.dispatchEvent(new CustomEvent('modalClosed'));
    }
    
    async copyCard(button) {
        try {
            // Get the modal content without buttons
            const cardContent = button.closest('.pixel-card').cloneNode(true);
            
            // Remove copy and close buttons
            const buttons = cardContent.querySelectorAll('button');
            buttons.forEach(btn => btn.remove());
            
            // Get clean text content
            const text = cardContent.textContent.trim().replace(/\s+/g, ' ');
            
            await navigator.clipboard.writeText(text);
            
            // Visual feedback
            const originalText = button.textContent;
            button.textContent = '✅ Copied!';
            button.classList.add('text-green-400');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('text-green-400');
            }, 2000);
        } catch (error) {
            console.error('Failed to copy:', error);
            button.textContent = '❌ Failed';
            setTimeout(() => {
                button.textContent = '📋 Copy';
            }, 2000);
        }
    }
}

// Alpine.js component for contact cards
function contactCard(contactData) {
    return {
        contact: contactData,
        expanded: false,
        
        getInitials() {
            const name = this.contact.full_name || 'Unknown';
            return name.split(' ')
                .map(word => word.charAt(0))
                .join('')
                .substring(0, 2)
                .toUpperCase();
        }
    };
}

// Make available globally
window.contactCard = contactCard;

// Bookmark functionality
window.checkBookmarkStatus = async function(fragmentId, element) {
    // Silently skip bookmark checks for invalid fragment IDs
    if (!fragmentId || fragmentId <= 0) {
        return;
    }
    
    try {
        const response = await fetch(`/api/fragments/${fragmentId}/bookmark`);
        if (!response.ok) {
            // Silently ignore 404s - fragment doesn't exist or is deleted
            return;
        }
        
        const data = await response.json();
        const alpineComponent = Alpine.$data(element);
        if (alpineComponent) {
            alpineComponent.bookmarked = data.is_bookmarked;
        }
    } catch (error) {
        // Silently ignore network errors
        return;
    }
};

window.toggleBookmark = async function(fragmentId, element) {
    try {
        const response = await fetch(`/api/fragments/${fragmentId}/bookmark`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update Alpine.js component state
        const alpineComponent = Alpine.$data(element);
        if (alpineComponent) {
            alpineComponent.bookmarked = data.is_bookmarked;
        }
        
        // Show feedback
        const action = data.action === 'added' ? 'Bookmarked!' : 'Removed bookmark';
        showBookmarkFeedback(element, action, data.action === 'added');
        
        // Dispatch event to notify bookmark widget to refresh
        window.dispatchEvent(new CustomEvent('bookmark-toggled', {
            detail: { fragmentId, action: data.action, isBookmarked: data.is_bookmarked }
        }));
        
    } catch (error) {
        console.log('Failed to toggle bookmark (this is normal if fragment was just deleted):', error.message);
        if (error.message.includes('404')) {
            showBookmarkFeedback(element, 'Fragment not found', false, true);
        } else {
            showBookmarkFeedback(element, 'Failed!', false, true);
        }
    }
};

function showBookmarkFeedback(element, message, isSuccess, isError = false) {
    // Create temporary feedback element
    const feedback = document.createElement('div');
    feedback.className = `absolute -top-8 -right-2 px-2 py-1 rounded-pixel text-xs font-medium pointer-events-none z-20 ${
        isError ? 'bg-red-500/20 text-red-400 border border-red-500/40' : 
        isSuccess ? 'bg-green-500/20 text-green-400 border border-green-500/40' : 
        'bg-blue-500/20 text-blue-400 border border-blue-500/40'
    }`;
    feedback.textContent = message;
    
    element.appendChild(feedback);
    
    setTimeout(() => {
        feedback.remove();
    }, 2000);
}

// Simple copy function that should always work
function copyTextSimple(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    textArea.setSelectionRange(0, 99999);
    
    const success = document.execCommand('copy');
    document.body.removeChild(textArea);
    
    if (!success) {
        throw new Error('Copy command failed');
    }
    
    return Promise.resolve();
}

// Clipboard fallback function
async function copyToClipboardFallback(text) {
    // Try modern clipboard API first
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch (err) {
            console.log('Clipboard API failed, trying fallback:', err);
        }
    }
    
    // Fallback method for older browsers or non-secure contexts
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const result = document.execCommand('copy');
        if (!result) {
            throw new Error('execCommand failed');
        }
    } finally {
        document.body.removeChild(textArea);
    }
}

// Global function for copying chat messages
window.copyChatMessage = async function(button) {
    try {
        console.log('Copy button clicked:', button);
        
        // Get the parent message container (skip the button itself)
        let messageContainer = button.parentElement;
        while (messageContainer && !messageContainer.classList.contains('relative')) {
            messageContainer = messageContainer.parentElement;
        }
        
        // If we found the relative container, it should have the message content
        if (!messageContainer) {
            // Fallback: look for any parent with a lot of content
            messageContainer = button.closest('div.flex-1, div[class*="bg-surface-card"]');
        }
        
        console.log('Message container found:', messageContainer);
        
        if (!messageContainer) {
            throw new Error('Could not find message container');
        }
        
        // Clone the container to manipulate it
        const clonedContainer = messageContainer.cloneNode(true);
        
        // Remove the copy button from the clone
        const copyButton = clonedContainer.querySelector('button');
        if (copyButton) {
            copyButton.remove();
        }
        
        // Get all text content
        let text = clonedContainer.textContent || clonedContainer.innerText;
        text = text.replace(/📋\s*Copy/g, '').replace(/✅\s*Copied!/g, '').replace(/❌\s*Failed/g, '').trim();
        
        console.log('Text to copy:', JSON.stringify(text));
        
        if (!text || text.length === 0) {
            throw new Error('No text content found to copy');
        }
        
        // Use the simplest copy method first
        await copyTextSimple(text);
        
        // Visual feedback
        const originalText = button.textContent;
        button.textContent = '✅ Copied!';
        button.classList.add('text-green-400', 'border-green-400/40');
        button.classList.remove('text-neon-cyan', 'border-neon-cyan/40');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('text-green-400', 'border-green-400/40');
            button.classList.add('text-neon-cyan', 'border-neon-cyan/40');
        }, 2000);
        
    } catch (error) {
        console.error('Failed to copy message:', error);
        
        // Error feedback
        const originalText = button.textContent;
        button.textContent = '❌ Failed';
        button.classList.add('text-red-400', 'border-red-400/40');
        button.classList.remove('text-neon-cyan', 'border-neon-cyan/40');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('text-red-400', 'border-red-400/40');
            button.classList.add('text-neon-cyan', 'border-neon-cyan/40');
        }, 2000);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('LinkHandler: Initializing...');
    window.linkHandler = new LinkHandler();
    console.log('LinkHandler: Initialized successfully');
    
    // Debug: Check for existing links
    setTimeout(() => {
        const contactLinks = document.querySelectorAll('.contact-link');
        const fragmentLinks = document.querySelectorAll('.fragment-link');
        console.log('LinkHandler: Found', contactLinks.length, 'contact links and', fragmentLinks.length, 'fragment links');
        
        if (contactLinks.length > 0) {
            console.log('LinkHandler: First contact link:', contactLinks[0]);
        }
        if (fragmentLinks.length > 0) {
            console.log('LinkHandler: First fragment link:', fragmentLinks[0]);
        }
    }, 1000);
});