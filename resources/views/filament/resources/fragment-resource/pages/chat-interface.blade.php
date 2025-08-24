<!-- Main 4-Column Layout -->
<div class="h-screen flex">
    
    <!-- Left Column 1: Ribbon -->
    <div class="w-16 bg-surface-2 border-r border-thin border-hot-pink/30 flex flex-col items-center py-4">
        <!-- Fe Periodic Element -->
        <div class="relative">
            <!-- Main hot pink square -->
            <div class="w-10 h-10 bg-hot-pink rounded-pixel flex items-center justify-center relative z-10 pixel-card glow-pink">
                <span class="text-white font-bold text-xl font-mono leading-none">Fe</span>
            </div>
            <!-- Offset outline -->
            <div class="absolute -top-0.5 -left-0.5 w-11 h-11 border-thin border-electric-blue rounded-pixel"></div>
        </div>
        
        <!-- Additional ribbon items -->
        <div class="mt-8 space-y-3">
            <button class="w-8 h-8 bg-surface-card rounded-pixel pixel-card border-thin border-hot-pink/30 flex items-center justify-center hover:bg-hot-pink/10 transition-colors glow-pink">
                <svg class="w-4 h-4 text-hot-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </button>
            <button class="w-8 h-8 bg-surface-card rounded-pixel pixel-card border-thin border-electric-blue/30 flex items-center justify-center hover:bg-electric-blue/10 transition-colors glow-blue">
                <svg class="w-4 h-4 text-electric-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Left Column 2: Navigation -->
    <div class="w-72 bg-surface-2 border-r border-thin border-electric-blue/30 flex flex-col">
        <!-- Projects/Context Sessions -->
        <div class="p-4 border-b border-thin border-hot-pink/20">
            <div class="pixel-card pixel-card-pink p-3 glow-pink">
                <h3 class="text-sm font-medium text-text-secondary mb-2">Active Project</h3>
                <div class="bg-surface-elevated rounded-pixel p-2 pixel-card border-thin border-hot-pink/30">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-hot-pink">Seer Interface</span>
                        <span class="text-xs text-electric-blue">v2.0</span>
                    </div>
                    <div class="text-xs text-text-muted mt-1">Chat Interface Redesign</div>
                </div>
            </div>
        </div>

        <!-- Session Indicator -->
        @if ($currentSession)
        <div class="p-4 border-b border-thin border-neon-cyan/20">
            <div class="pixel-card pixel-card-cyan p-3 glow-cyan">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-neon-cyan">Active Session</h3>
                    <button wire:click="showSession" class="text-xs text-text-muted hover:text-neon-cyan">Details</button>
                </div>
                <div class="text-sm font-medium text-text-primary">{{ $currentSession['identifier'] ?? 'Unnamed Session' }}</div>
                <div class="text-xs text-text-muted mt-1">{{ $currentSession['vault'] }} • {{ $currentSession['type'] }}</div>
            </div>
        </div>
        @endif

        <!-- Chat History -->
        <div class="flex-1 p-4 overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-text-secondary">Recent Chats</h3>
                <button wire:click="startNewChat" class="text-xs bg-electric-blue/20 hover:bg-electric-blue/30 text-electric-blue px-2 py-1 rounded-pixel border border-electric-blue/40 transition-colors pixel-card glow-blue">
                    ✨ New Chat
                </button>
            </div>
            
            <div class="space-y-2">
                @if (!empty($recentChatSessions))
                    @foreach ($recentChatSessions as $session)
                        <div 
                            wire:click="switchToChat({{ $session['id'] }})"
                            class="pixel-card p-3 cursor-pointer transition-all
                                {{ $session['id'] === $currentChatSessionId 
                                    ? 'pixel-card-pink bg-hot-pink/20 border-hot-pink/60 glow-pink' 
                                    : 'pixel-card-blue bg-electric-blue/10 border-electric-blue/30 hover:bg-electric-blue/20 hover:border-electric-blue/50' 
                                }}"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium {{ $session['id'] === $currentChatSessionId ? 'text-text-primary' : 'text-text-secondary' }} truncate">
                                        {{ $session['title'] }}
                                    </div>
                                    <div class="text-xs text-text-muted mt-1">
                                        {{ $session['message_count'] }} messages
                                    </div>
                                    @if (!empty($session['preview']))
                                        <div class="text-xs text-text-muted mt-1 truncate">
                                            {{ $session['preview'] }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-xs {{ $session['id'] === $currentChatSessionId ? 'text-hot-pink' : 'text-electric-blue' }} ml-2 font-medium flex-shrink-0">
                                    {{ $session['id'] === $currentChatSessionId ? 'Active' : $session['last_activity'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-text-muted text-xs py-4">
                        No recent chats
                    </div>
                @endif
            </div>
        </div>

        <!-- New Chat & Commands -->
        <div class="p-4 border-t border-thin border-electric-blue/20 space-y-3">
            <button 
                x-data
                x-on:click="$dispatch('open-command-palette')"
                class="w-full bg-hot-pink text-white py-2 px-4 rounded-pixel hover:bg-hot-pink/90 transition-colors text-sm font-medium pixel-card glow-pink"
            >
                ⚡ Commands
            </button>
        </div>
    </div>

    <!-- Middle Column: Chat Interface -->
    <div class="flex-1 flex flex-col bg-surface">
        <!-- Row 1: Header -->
        <div class="h-16 bg-surface-2 border-b border-thin border-hot-pink/30 flex items-center justify-between px-6 sticky top-0 z-10">
            <div class="flex items-center space-x-3">
                <h2 class="text-lg font-medium text-text-primary">Chat Interface</h2>
                <span class="bg-neon-cyan/20 text-bright-pink text-xs px-2 py-1 rounded-pixel border-thin border-neon-cyan/40 font-medium">Active</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <div
                    id="drift-avatar"
                    x-data="{ avatar: '/interface/avatars/default/default.png' }"
                    x-init="$watch('avatar', value => {
        $el.querySelector('img').src = value;
      })"
                    class="w-8 h-8 bg-surface-card rounded-full shadow-lg border-2 border-electric-blue/30 transition-all"
                >
                    <img :src="avatar" alt="Drift Avatar" class="rounded-full w-full h-full object-cover">
                </div>
            </div>
        </div>

        <!-- Row 2: Chat Content -->
        <div class="flex-1 p-6 overflow-y-auto space-y-4" id="chat-output">
            <!-- Chat Messages -->
            @foreach ($chatMessages as $entry)
                <x-chat-message 
                    :type="$entry['type'] ?? 'user'"
                    :fragmentId="$entry['id'] ?? null"
                    :timestamp="$this->formatTimestamp($entry['created_at'] ?? null)"
                >
                    <x-chat-markdown :fragment="null">
                        {{ $entry['message'] }}
                    </x-chat-markdown>
                </x-chat-message>
            @endforeach

            <!-- Todos Section -->
            @if (!empty($recalledTodos))
                @php
                    $todoFragments = $this->getTodoFragments();
                @endphp
                
                <div class="pixel-card pixel-card-cyan p-4 glow-cyan">
                    <h2 class="text-lg font-semibold text-neon-cyan mb-3">📋 Todos ({{ $todoFragments->count() }})</h2>
                    <div class="space-y-2">
                        @foreach ($todoFragments as $fragment)
                            <div wire:key="todo-{{ $fragment->id }}" class="bg-surface-elevated rounded-pixel p-2 pixel-card border-thin border-neon-cyan/30">
                                <livewire:todo-item :fragment="$fragment" :key="'todo-'.$fragment->id" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Row 3: Input Area -->
        <div class="bg-surface-2 border-t border-thin border-hot-pink/30">                
            <!-- Chat Input -->
            <form id="chat-form" x-data wire:submit.prevent="handleInput" class="p-4">
                <div class="flex space-x-3">
                    <div class="flex-1">
                        <textarea 
                            x-data="chatTextarea()"
                            x-ref="chatTextarea"
                            x-init="initAutocomplete()"
                            wire:model.defer="input"
                            x-on:keydown.enter.prevent="handleEnterKey($event)"
                            class="w-full p-3 border-thin border-hot-pink/30 rounded-pixel resize-none focus:ring-2 focus:ring-hot-pink focus:border-hot-pink pixel-card bg-surface-card text-text-primary" 
                            rows="2" 
                            placeholder="Type your message... (try /, @, or [[)"
                        ></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-hot-pink text-white rounded-pixel hover:bg-hot-pink/90 transition-colors self-center pixel-card glow-pink">
                        Send
                    </button>
                </div>
                
                <!-- Command History -->
                @if (!empty($commandHistory))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach (array_reverse(array_slice($commandHistory, -4)) as $cmd)
                            <button
                                type="button"
                                wire:click="injectCommand('{{ addslashes($cmd) }}')"
                                class="text-xs bg-surface-card hover:bg-surface-elevated text-text-secondary rounded-pixel px-2 py-1 border-thin border-electric-blue/30 pixel-card glow-blue"
                            >
                                {{ $cmd }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Right Column: Widgets & Search -->
    <div class="w-80 bg-surface-2 border-l border-thin border-electric-blue/30 flex flex-col">
        <!-- Widgets Section -->
        <div class="flex-1 p-4 overflow-y-auto">
            <h3 class="text-sm font-medium text-text-secondary mb-4">Widgets</h3>
            
            <!-- System Widgets -->
            <div class="space-y-4 mb-6">
                <!-- Stats Widget -->
                <div class="pixel-card pixel-card-pink p-4 glow-pink">
                    <h4 class="text-sm font-medium text-hot-pink mb-3">Today's Activity</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-text-muted">Messages</span>
                            <span class="text-sm font-medium text-hot-pink">{{ count($chatMessages) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-text-muted">Commands</span>
                            <span class="text-sm font-medium text-electric-blue">{{ count($commandHistory) }}</span>
                        </div>
                        @if (!empty($recalledTodos))
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-text-muted">Todos</span>
                                <span class="text-sm font-medium text-neon-cyan">{{ $this->getTodoFragments()->count() }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions Widget -->
                <div class="pixel-card pixel-card-blue p-4 glow-blue">
                    <h4 class="text-sm font-medium text-electric-blue mb-3">Quick Actions</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-hot-pink/20 border-thin border-hot-pink/40 pixel-card glow-pink">Export</button>
                        <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-electric-blue/20 border-thin border-electric-blue/40 pixel-card glow-blue">Search</button>
                        <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-neon-cyan/20 border-thin border-neon-cyan/40 pixel-card glow-cyan">Filter</button>
                        <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-deep-purple/20 border-thin border-deep-purple/40 pixel-card">Archive</button>
                    </div>
                </div>

                <!-- Recent Bookmarks Widget -->
                <div 
                    class="pixel-card pixel-card-cyan p-4 glow-cyan"
                    x-data="bookmarkWidget()"
                    x-init="init(); loadRecentBookmarks()"
                >
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-neon-cyan">Recent Bookmarks</h4>
                        <button 
                            x-show="!searchMode"
                            x-on:click="searchMode = true; $nextTick(() => $refs.searchInput.focus())"
                            class="text-xs text-text-muted hover:text-neon-cyan transition-colors"
                        >
                            🔍
                        </button>
                        <button 
                            x-show="searchMode"
                            x-on:click="clearSearch()"
                            class="text-xs text-text-muted hover:text-neon-cyan transition-colors"
                        >
                            ✕
                        </button>
                    </div>
                    
                    <!-- Search Input -->
                    <div x-show="searchMode" class="mb-3">
                        <input 
                            x-ref="searchInput"
                            x-model="searchQuery"
                            x-on:input.debounce.300ms="handleSearch()"
                            placeholder="Search bookmarks..."
                            class="w-full bg-surface-card text-text-secondary text-xs p-2 rounded-pixel border-thin border-neon-cyan/40 focus:border-neon-cyan focus:outline-none"
                        />
                    </div>
                    
                    <!-- Bookmarks List -->
                    <div class="space-y-2 max-h-48 overflow-y-auto" x-show="!loading" :class="{ 'pointer-events-none': openingModal }">
                        <template x-for="bookmark in bookmarks" :key="bookmark.id">
                            <div 
                                x-on:click.stop="openBookmark(bookmark)"
                                class="flex items-center space-x-2 text-xs cursor-pointer hover:bg-neon-cyan/10 p-1 rounded-pixel transition-colors"
                            >
                                <div class="w-2 h-2 bg-neon-cyan rounded-full flex-shrink-0"></div>
                                <span 
                                    class="text-text-secondary flex-1 truncate"
                                    :title="bookmark.fragment_title"
                                    x-text="bookmark.name"
                                ></span>
                                <span class="text-text-muted text-xs flex-shrink-0" x-text="bookmark.updated_at"></span>
                            </div>
                        </template>
                        
                        <!-- Empty State -->
                        <div x-show="bookmarks.length === 0 && !loading" class="text-center text-text-muted text-xs py-4">
                            <span x-show="!searchMode">No bookmarks yet</span>
                            <span x-show="searchMode">No results found</span>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div x-show="loading" class="text-center text-neon-cyan text-xs py-4">
                        ⏳ Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Autocomplete Scripts -->
    @vite(['resources/js/app.js'])
    <script>
        function bookmarkWidget() {
            return {
                bookmarks: [],
                loading: false,
                searchMode: false,
                searchQuery: '',
                openingModal: false,
                
                init() {
                    // Listen for modal close events to reset our state
                    document.addEventListener('modalClosed', () => {
                        this.openingModal = false;
                    });
                },
                
                async loadRecentBookmarks() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/bookmarks/recent?limit=8');
                        if (response.ok) {
                            const data = await response.json();
                            this.bookmarks = data.bookmarks;
                        }
                    } catch (error) {
                        console.error('Failed to load recent bookmarks:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                
                async handleSearch() {
                    if (this.searchQuery.length < 2) {
                        await this.loadRecentBookmarks();
                        return;
                    }
                    
                    this.loading = true;
                    try {
                        const response = await fetch(`/api/bookmarks/search?q=${encodeURIComponent(this.searchQuery)}&limit=8`);
                        if (response.ok) {
                            const data = await response.json();
                            this.bookmarks = data.bookmarks;
                        }
                    } catch (error) {
                        console.error('Failed to search bookmarks:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                
                clearSearch() {
                    this.searchMode = false;
                    this.searchQuery = '';
                    this.loadRecentBookmarks();
                },
                
                async openBookmark(bookmark) {
                    console.log('Opening bookmark:', bookmark);
                    
                    // Prevent multiple simultaneous openings
                    if (this.openingModal) {
                        console.log('Modal already opening, ignoring click');
                        return;
                    }
                    
                    if (!bookmark || !bookmark.fragment_id) {
                        console.warn('No fragment ID for bookmark:', bookmark);
                        // Show user feedback for invalid bookmark
                        alert('This bookmark references a fragment that no longer exists.');
                        return;
                    }
                    
                    this.openingModal = true;
                    
                    // Mark bookmark as viewed
                    try {
                        const response = await fetch(`/api/bookmarks/${bookmark.id}/mark-viewed`, { 
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            }
                        });
                        if (!response.ok) {
                            console.warn('Failed to mark bookmark as viewed:', response.status);
                        }
                    } catch (error) {
                        console.error('Failed to mark bookmark as viewed:', error);
                    }
                    
                    // Open fragment modal using existing LinkHandler
                    try {
                        if (window.linkHandler && window.linkHandler.showFragmentModal) {
                            // Add a small delay to prevent event conflicts
                            await new Promise(resolve => setTimeout(resolve, 50));
                            await window.linkHandler.showFragmentModal(bookmark.fragment_id, bookmark.name);
                        } else {
                            console.error('LinkHandler not available');
                            alert('Unable to open bookmark. Please try refreshing the page.');
                        }
                    } catch (error) {
                        console.error('Failed to open fragment modal:', error);
                        alert('Failed to load bookmark content.');
                    } finally {
                        // Reset the flag after a delay to prevent accidental rapid clicks
                        setTimeout(() => {
                            this.openingModal = false;
                        }, 500);
                    }
                }
            };
        }

        function chatTextarea() {
            return {
                autocompleteActive: false,
                autocompleteEngine: null,
                
                initAutocomplete() {
                    if (typeof AutocompleteEngine !== 'undefined') {
                        this.autocompleteEngine = new AutocompleteEngine(this.$refs.chatTextarea);
                        
                        // Monitor autocomplete state
                        const originalShow = this.autocompleteEngine.show.bind(this.autocompleteEngine);
                        const originalHide = this.autocompleteEngine.hide.bind(this.autocompleteEngine);
                        
                        this.autocompleteEngine.show = () => {
                            this.autocompleteActive = true;
                            originalShow();
                        };
                        
                        this.autocompleteEngine.hide = () => {
                            this.autocompleteActive = false;
                            originalHide();
                        };
                    }
                },
                
                handleEnterKey(event) {
                    // If autocomplete is active, don't submit
                    if (this.autocompleteActive) {
                        return;
                    }
                    
                    // Don't clear the textarea here - let Livewire handle it
                    // Just trigger the form submission
                    this.$nextTick(() => {
                        const form = document.getElementById('chat-form');
                        if (form) {
                            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                        }
                    });
                },
                
                destroy() {
                    if (this.autocompleteEngine) {
                        this.autocompleteEngine.destroy();
                    }
                }
            };
        }
    </script>

    <!-- Command Palette Modal -->
    <div
        x-data="{ open: false }"
        x-on:open-command-palette.window="open = true"
        x-show="open"
        style="display: none;"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    >
        <div class="bg-surface-card rounded-pixel p-6 w-96 shadow-xl pixel-card border-thin border-electric-blue/40 glow-blue">
            <h3 class="text-lg font-semibold mb-4 text-center text-electric-blue">⚡ Command Palette</h3>
            <div class="space-y-2">
                @foreach (\App\Services\CommandRegistry::all() as $cmd)
                    <button
                        wire:click="executeCommand('{{ $cmd }}')"
                        x-on:click="open = false; $nextTick(() => document.querySelector('textarea[x-ref=chatTextarea]')?.focus())"
                        class="w-full text-left bg-surface-elevated hover:bg-hot-pink/20 text-text-secondary hover:text-text-primary rounded-pixel px-3 py-2 text-sm pixel-card border-thin border-hot-pink/30 glow-pink"
                    >
                        /{{ $cmd }}
                    </button>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <button
                    x-on:click="open = false"
                    class="text-xs text-text-muted hover:text-text-secondary"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>