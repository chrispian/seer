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
            <!-- Create Flyout Menu -->
            <div x-data="{ open: false }" class="relative">
                <button 
                    @click="open = !open"
                    class="w-8 h-8 bg-surface-card rounded-pixel pixel-card border-thin border-hot-pink/30 flex items-center justify-center hover:bg-hot-pink/10 transition-colors glow-pink"
                >
                    <svg class="w-4 h-4 text-hot-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </button>
                
                <!-- Flyout Menu -->
                <div 
                    x-show="open"
                    x-transition
                    @click.outside="open = false"
                    class="absolute left-full ml-2 top-0 bg-surface-2 border border-hot-pink/30 rounded-pixel pixel-card p-2 space-y-1 min-w-48 z-50 glow-pink"
                >
                    <button 
                        wire:click="openVaultModal"
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-text-primary hover:bg-hot-pink/20 rounded-pixel transition-colors flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4 text-hot-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span>New Vault</span>
                    </button>
                    <button 
                        wire:click="openProjectModal"
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-text-primary hover:bg-electric-blue/20 rounded-pixel transition-colors flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4 text-electric-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span>New Project</span>
                    </button>
                    <button 
                        wire:click="startNewChat"
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-text-primary hover:bg-neon-cyan/20 rounded-pixel transition-colors flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4 text-neon-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>New Chat</span>
                    </button>
                </div>
            </div>
            
            <button class="w-8 h-8 bg-surface-card rounded-pixel pixel-card border-thin border-electric-blue/30 flex items-center justify-center hover:bg-electric-blue/10 transition-colors glow-blue">
                <svg class="w-4 h-4 text-electric-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Left Column 2: Navigation -->
    <div class="w-72 bg-surface-2 border-r border-thin border-electric-blue/30 flex flex-col">
        <!-- Vault Selection -->
        <div class="p-4 border-b border-thin border-hot-pink/20">
            <div class="pixel-card pixel-card-pink p-3 glow-pink">
                <h3 class="text-sm font-medium text-text-secondary mb-2">Current Vault</h3>
                <select 
                    wire:model.live="currentVaultId" 
                    class="w-full bg-surface-elevated text-sm text-hot-pink rounded-pixel p-2 pixel-card border-thin border-hot-pink/30 focus:ring-2 focus:ring-hot-pink focus:border-hot-pink"
                >
                    @foreach ($vaults as $vault)
                        <option value="{{ $vault['id'] }}" {{ $currentVaultId == $vault['id'] ? 'selected' : '' }}>
                            {{ $vault['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Project Selection -->
        <div class="p-4 border-b border-thin border-electric-blue/20">
            <div class="pixel-card pixel-card-blue p-3 glow-blue">
                <h3 class="text-sm font-medium text-text-secondary mb-2">Current Project</h3>
                <select 
                    wire:model.live="currentProjectId"
                    class="w-full bg-surface-elevated text-sm text-electric-blue rounded-pixel p-2 pixel-card border-thin border-electric-blue/30 focus:ring-2 focus:ring-electric-blue focus:border-electric-blue"
                >
                    @foreach ($projects as $project)
                        <option value="{{ $project['id'] }}" {{ $currentProjectId == $project['id'] ? 'selected' : '' }}>
                            {{ $project['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Active Session (Current Chat) -->
        @if ($currentChatSessionId)
            @php
                $activeSession = collect($recentChatSessions)->firstWhere('id', $currentChatSessionId) 
                    ?? collect($pinnedChatSessions)->firstWhere('id', $currentChatSessionId);
            @endphp
            @if ($activeSession)
            <div class="p-4 border-b border-thin border-neon-cyan/20">
                <div class="pixel-card pixel-card-cyan p-3 glow-cyan">
                    <h3 class="text-sm font-medium text-text-secondary mb-2">Active Chat</h3>
                    <div 
                        wire:click="switchToChat({{ $activeSession['id'] }})"
                        class="bg-surface-elevated rounded-pixel p-2 pixel-card border-thin border-neon-cyan/30 cursor-pointer hover:bg-neon-cyan/10 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-neon-cyan truncate">{{ $activeSession['title'] }}</span>
                            <span class="text-xs text-bright-pink ml-2">Active</span>
                        </div>
                        <div class="text-xs text-text-muted mt-1">{{ $activeSession['message_count'] }} messages</div>
                    </div>
                </div>
            </div>
            @endif
        @endif

        <!-- Legacy Session Indicator -->
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
            <!-- Pinned Chats -->
            @if (!empty($pinnedChatSessions))
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-text-secondary">📌 Pinned Chats</h3>
                </div>
                
                <div 
                    x-data="{
                        sortable: null,
                        initSortable() {
                            const container = document.getElementById('pinned-chats-container');
                            if (container) {
                                this.sortable = Sortable.create(container, {
                                    handle: '.drag-handle',
                                    animation: 150,
                                    ghostClass: 'opacity-50',
                                    onEnd: (evt) => {
                                        const items = Array.from(container.children);
                                        const newOrder = items.map((item, index) => ({
                                            id: parseInt(item.dataset.id),
                                            sortOrder: index + 1
                                        }));
                                        
                                        // Call Livewire method to update sort order
                                        @this.call('updatePinnedChatOrder', newOrder);
                                    }
                                });
                            }
                        }
                    }"
                    x-init="initSortable()"
                    class="space-y-2"
                    id="pinned-chats-container"
                >
                    @foreach ($pinnedChatSessions as $session)
                        <div 
                            data-id="{{ $session['id'] }}"
                            wire:click="switchToChat({{ $session['id'] }})"
                            class="pixel-card p-3 cursor-pointer transition-all sortable-item
                                {{ $session['id'] === $currentChatSessionId 
                                    ? 'pixel-card-pink bg-hot-pink/20 border-hot-pink/60 glow-pink' 
                                    : 'pixel-card-cyan bg-neon-cyan/10 border-neon-cyan/30 hover:bg-neon-cyan/20 hover:border-neon-cyan/50' 
                                }}"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div class="drag-handle cursor-move mr-2 text-text-muted hover:text-neon-cyan transition-colors">
                                        ⋮⋮
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium {{ $session['id'] === $currentChatSessionId ? 'text-text-primary' : 'text-text-secondary' }} truncate break-words" title="{{ $session['title'] }}">
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
                                </div>
                                <div class="flex flex-col items-end space-y-1">
                                    <button 
                                        wire:click.stop="togglePinChat({{ $session['id'] }})"
                                        class="text-xs text-neon-cyan hover:text-bright-pink transition-colors"
                                        title="Unpin chat"
                                    >
                                        📌
                                    </button>
                                    <div class="text-xs {{ $session['id'] === $currentChatSessionId ? 'text-hot-pink' : 'text-neon-cyan' }} font-medium">
                                        {{ $session['id'] === $currentChatSessionId ? 'Active' : $session['last_activity'] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Recent Chats -->
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-text-secondary">Recent Chats</h3>
                <button wire:click="startNewChat" class="text-xs bg-electric-blue/20 hover:bg-electric-blue/30 text-electric-blue px-2 py-1 rounded-pixel border border-electric-blue/40 transition-colors pixel-card glow-blue">
                    ✨ New Chat
                </button>
            </div>
            
            <div class="space-y-1">
                @if (!empty($recentChatSessions))
                    @foreach ($recentChatSessions as $session)
                        <div 
                            wire:click="switchToChat({{ $session['id'] }})"
                            class="px-3 py-2 cursor-pointer transition-all rounded-pixel text-sm
                                {{ $session['id'] === $currentChatSessionId 
                                    ? 'bg-hot-pink/20 text-hot-pink border-l-2 border-hot-pink' 
                                    : 'hover:bg-electric-blue/10 text-text-secondary hover:text-electric-blue' 
                                }}"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="truncate font-medium">{{ $session['title'] }}</div>
                                    <div class="text-xs text-text-muted">{{ $session['message_count'] }} messages</div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button 
                                        wire:click.stop="togglePinChat({{ $session['id'] }})"
                                        class="text-xs text-text-muted hover:text-electric-blue transition-colors"
                                        title="Pin chat"
                                    >
                                        📌
                                    </button>
                                    <div class="text-xs {{ $session['id'] === $currentChatSessionId ? 'text-hot-pink' : 'text-text-muted' }}">
                                        {{ $session['id'] === $currentChatSessionId ? 'Active' : $session['last_activity'] }}
                                    </div>
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

    <!-- Create Vault Modal -->
    <div
        x-show="$wire.showVaultModal"
        x-transition
        style="display: none;"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    >
        <div class="bg-surface-2 rounded-pixel p-6 w-96 shadow-xl pixel-card border-thin border-hot-pink/40 glow-pink">
            <h3 class="text-lg font-semibold mb-4 text-center text-hot-pink">🗄️ Create New Vault</h3>
            
            <form wire:submit.prevent="createNewVault" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Vault Name *</label>
                    <input 
                        type="text" 
                        wire:model="newVaultName"
                        class="w-full bg-surface-elevated text-text-primary rounded-pixel p-3 pixel-card border-thin border-hot-pink/30 focus:ring-2 focus:ring-hot-pink focus:border-hot-pink"
                        placeholder="Enter vault name..."
                        required
                    />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Description</label>
                    <textarea 
                        wire:model="newVaultDescription"
                        class="w-full bg-surface-elevated text-text-primary rounded-pixel p-3 pixel-card border-thin border-hot-pink/30 focus:ring-2 focus:ring-hot-pink focus:border-hot-pink resize-none"
                        rows="3"
                        placeholder="Brief description (optional)..."
                    ></textarea>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button
                        type="submit"
                        class="flex-1 bg-hot-pink text-white py-2 px-4 rounded-pixel hover:bg-hot-pink/90 transition-colors text-sm font-medium pixel-card glow-pink"
                    >
                        Create Vault
                    </button>
                    <button
                        type="button"
                        wire:click="closeVaultModal"
                        class="flex-1 bg-surface-elevated text-text-secondary py-2 px-4 rounded-pixel hover:bg-surface-card transition-colors text-sm font-medium pixel-card border-thin border-text-muted/30"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Project Modal -->
    <div
        x-show="$wire.showProjectModal"
        x-transition
        style="display: none;"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    >
        <div class="bg-surface-2 rounded-pixel p-6 w-96 shadow-xl pixel-card border-thin border-electric-blue/40 glow-blue">
            <h3 class="text-lg font-semibold mb-4 text-center text-electric-blue">📁 Create New Project</h3>
            
            <form wire:submit.prevent="createNewProject" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Project Name *</label>
                    <input 
                        type="text" 
                        wire:model="newProjectName"
                        class="w-full bg-surface-elevated text-text-primary rounded-pixel p-3 pixel-card border-thin border-electric-blue/30 focus:ring-2 focus:ring-electric-blue focus:border-electric-blue"
                        placeholder="Enter project name..."
                        required
                    />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Description</label>
                    <textarea 
                        wire:model="newProjectDescription"
                        class="w-full bg-surface-elevated text-text-primary rounded-pixel p-3 pixel-card border-thin border-electric-blue/30 focus:ring-2 focus:ring-electric-blue focus:border-electric-blue resize-none"
                        rows="3"
                        placeholder="Brief description (optional)..."
                    ></textarea>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button
                        type="submit"
                        class="flex-1 bg-electric-blue text-white py-2 px-4 rounded-pixel hover:bg-electric-blue/90 transition-colors text-sm font-medium pixel-card glow-blue"
                    >
                        Create Project
                    </button>
                    <button
                        type="button"
                        wire:click="closeProjectModal"
                        class="flex-1 bg-surface-elevated text-text-secondary py-2 px-4 rounded-pixel hover:bg-surface-card transition-colors text-sm font-medium pixel-card border-thin border-text-muted/30"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>