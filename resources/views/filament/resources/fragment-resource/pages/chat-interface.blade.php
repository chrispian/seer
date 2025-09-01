<!-- Livewire Component Root -->
<div>
    <!-- Main 4-Column Layout -->
    <div class="h-screen flex">

    <!-- Left Column 1: Ribbon -->
    <div class="w-16 bg-gray-900 border-r border-thin border-hot-pink/20 flex flex-col items-center py-4">
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
                    x-cloak
                    @click.outside="open = false"
                    class="absolute left-full ml-2 top-0 bg-gray-900 border border-hot-pink/20 rounded-pixel p-2 space-y-1 min-w-48 z-50 shadow-lg shadow-hot-pink/10"
                >
                    <button
                        wire:click="openVaultModal"
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-gray-300 hover:bg-hot-pink/20 hover:text-hot-pink rounded-pixel transition-colors flex items-center space-x-2"
                    >
                        <x-heroicon-o-archive-box class="w-4 h-4 text-hot-pink"/>
                        <span>New Vault</span>
                    </button>
                    <button
                        wire:click="openProjectModal"
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-gray-300 hover:bg-electric-blue/20 hover:text-electric-blue rounded-pixel transition-colors flex items-center space-x-2"
                    >
                        <x-heroicon-o-folder class="w-4 h-4 text-electric-blue"/>
                        <span>New Project</span>
                    </button>
                    <button
                        wire:click="startNewChat"
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-gray-300 hover:bg-neon-cyan/20 hover:text-neon-cyan rounded-pixel transition-colors flex items-center space-x-2"
                    >
                        <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 text-neon-cyan"/>
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
    <div class="w-72 bg-gray-900/95 border-r border-thin border-electric-blue/20 flex flex-col">
        <!-- Vault Selection -->
        <div class="p-4 border-b border-thin border-hot-pink/10">
            <div>
                <h3 class="text-xs font-medium text-hot-pink/80 mb-2">Vault</h3>
                <div class="flex space-x-1">
                    <select
                        wire:model.live="currentVaultId"
                        class="flex-1 bg-gray-800 text-sm text-hot-pink rounded-l-pixel p-2 border-thin border-hot-pink/20 focus:ring-1 focus:ring-hot-pink focus:border-hot-pink appearance-none cursor-pointer"
                        style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%23f97316%27%3e%3cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;"
                    >
                        @foreach ($vaults as $vault)
                            <option value="{{ $vault['id'] }}" {{ $currentVaultId == $vault['id'] ? 'selected' : '' }} class="bg-gray-800 text-gray-300">
                                {{ $vault['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <button
                        wire:click="openVaultModal"
                        class="px-3 bg-gray-800 border-thin border-hot-pink/20 rounded-r-pixel hover:bg-hot-pink/20 transition-colors text-hot-pink"
                        title="Create new vault"
                    >
                        <x-heroicon-o-plus class="w-4 h-4"/>
                    </button>
                </div>
            </div>
        </div>

        <!-- Project Selection -->
        <div class="p-4 border-b border-thin border-electric-blue/10">
            <div>
                <h3 class="text-xs font-medium text-electric-blue/80 mb-2">Project</h3>
                <div class="flex space-x-1">
                    <select
                        wire:model.live="currentProjectId"
                        class="flex-1 bg-gray-800 text-sm text-electric-blue rounded-l-pixel p-2 border-thin border-electric-blue/20 focus:ring-1 focus:ring-electric-blue focus:border-electric-blue appearance-none cursor-pointer"
                        style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%2306b6d4%27%3e%3cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;"
                    >
                        @foreach ($projects as $project)
                            <option value="{{ $project['id'] }}" {{ $currentProjectId == $project['id'] ? 'selected' : '' }} class="bg-gray-800 text-gray-300">
                                {{ $project['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <button
                        wire:click="openProjectModal"
                        class="px-3 bg-gray-800 border-thin border-electric-blue/20 rounded-r-pixel hover:bg-electric-blue/20 transition-colors text-electric-blue"
                        title="Create new project"
                    >
                        <x-heroicon-o-plus class="w-4 h-4"/>
                    </button>
                </div>
            </div>
        </div>

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
                    <h3 class="text-xs font-medium text-hot-pink/80">
                        <x-heroicon-o-paper-clip class="inline w-3 h-3 mr-1"/>
                        Pinned Chats
                    </h3>
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
                    class="space-y-1"
                    id="pinned-chats-container"
                >
                    @foreach ($pinnedChatSessions as $session)
                        <div
                            data-id="{{ $session['id'] }}"
                            wire:click="switchToChat({{ $session['id'] }})"
                            class="flex items-center p-2 rounded-pixel cursor-pointer transition-all sortable-item
                                {{ $session['id'] === $currentChatSessionId
                                    ? 'bg-hot-pink/20 border-l-2 border-hot-pink'
                                    : 'bg-gray-800 hover:bg-neon-cyan/10'
                                }}"
                        >
                            <div class="drag-handle cursor-move mr-2 text-gray-500 hover:text-neon-cyan transition-colors">
                                <x-heroicon-o-bars-2 class="w-3 h-3"/>
                            </div>
                            <div class="flex-1 min-w-0 mr-2">
                                <span class="text-sm {{ $session['id'] === $currentChatSessionId ? 'text-hot-pink' : 'text-gray-300' }} truncate block" title="{{ $session['channel_display'] ?? $session['title'] }}">
                                    {{ $session['channel_display'] ?? $session['title'] }}
                                </span>
                            </div>
                            <span class="px-1.5 py-0.5 text-xs rounded-full {{ $session['id'] === $currentChatSessionId ? 'bg-hot-pink/30 text-hot-pink' : 'bg-neon-cyan/20 text-neon-cyan' }} font-medium">
                                {{ $session['message_count'] }}
                            </span>
                            <button
                                wire:click.stop="togglePinChat({{ $session['id'] }})"
                                class="ml-2 p-0.5 rounded bg-gray-700/50 text-gray-500 hover:bg-hot-pink/20 hover:text-hot-pink hover:shadow-sm hover:shadow-hot-pink/20 transition-all"
                                title="Unpin chat"
                            >
                                <x-heroicon-o-x-mark class="w-2.5 h-2.5"/>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Recent Chats -->
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-medium text-electric-blue/80">
                    <x-heroicon-o-chat-bubble-left-right class="inline w-3 h-3 mr-1"/>
                    Recent Chats
                </h3>
            </div>

            <div
                x-data="{
                    sortable: null,
                    initSortable() {
                        const container = document.getElementById('recent-chats-container');
                        if (container) {
                            this.sortable = Sortable.create(container, {
                                handle: '.drag-handle',
                                animation: 150,
                                ghostClass: 'opacity-50',
                                disabled: true, // Disable sorting but keep structure
                                onEnd: (evt) => {
                                    // No-op since sorting is disabled
                                }
                            });
                        }
                    }
                }"
                x-init="initSortable()"
                class="space-y-1"
                id="recent-chats-container"
            >
                @if (!empty($recentChatSessions))
                    @foreach ($recentChatSessions as $session)
                        <div
                            wire:click="switchToChat({{ $session['id'] }})"
                            class="flex items-center p-2 rounded-pixel cursor-pointer transition-all
                                {{ $session['id'] === $currentChatSessionId
                                    ? 'bg-hot-pink/20 border-l-2 border-hot-pink'
                                    : 'bg-gray-800 hover:bg-electric-blue/10'
                                }}"
                        >
                            <div class="flex-1 min-w-0 mr-2">
                                <span class="text-sm {{ $session['id'] === $currentChatSessionId ? 'text-hot-pink' : 'text-gray-300' }} truncate block" title="{{ $session['channel_display'] ?? $session['title'] }}">
                                    {{ $session['channel_display'] ?? $session['title'] }}
                                </span>
                            </div>
                            <span class="px-1.5 py-0.5 text-xs rounded-full {{ $session['id'] === $currentChatSessionId ? 'bg-hot-pink/30 text-hot-pink' : 'bg-electric-blue/20 text-electric-blue' }} font-medium">
                                {{ $session['message_count'] }}
                            </span>
                            <div class="flex items-center space-x-1 ml-2">
                                <button
                                    wire:click.stop="togglePinChat({{ $session['id'] }})"
                                    class="p-0.5 rounded bg-gray-700/50 text-gray-500 hover:bg-electric-blue/20 hover:text-electric-blue hover:shadow-sm hover:shadow-electric-blue/20 transition-all"
                                    title="Pin chat"
                                >
                                    <x-heroicon-o-paper-clip class="w-2.5 h-2.5"/>
                                </button>
                                <button
                                    wire:click.stop="deleteChat({{ $session['id'] }})"
                                    class="p-0.5 rounded bg-gray-700/50 text-gray-500 hover:bg-hot-pink/20 hover:text-hot-pink hover:shadow-sm hover:shadow-hot-pink/20 transition-all"
                                    title="Delete chat"
                                >
                                    <x-heroicon-o-trash class="w-2.5 h-2.5"/>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-gray-500 text-xs py-4">
                        No recent chats
                    </div>
                @endif
            </div>
        </div>

        <!-- New Chat & Commands -->
        <div class="p-4 border-t border-thin border-electric-blue/20 space-y-2">
            <button
                wire:click="startNewChat"
                class="w-full bg-electric-blue/20 hover:bg-electric-blue/30 text-electric-blue py-2 px-4 rounded-pixel transition-colors text-sm font-medium border border-electric-blue/40"
            >
                <x-heroicon-o-plus class="inline w-4 h-4 mr-1"/>
                New Chat
            </button>
            <button
                x-data
                x-on:click="$dispatch('open-command-palette')"
                class="w-full bg-hot-pink text-white py-2 px-4 rounded-pixel hover:bg-hot-pink/90 transition-colors text-sm font-medium"
            >
                <x-heroicon-o-command-line class="inline w-4 h-4 mr-1"/>
                Commands
            </button>
        </div>
    </div>

    <!-- Middle Column: Chat Interface -->
    <div class="flex-1 flex flex-col bg-surface">
        <!-- Row 1: Header -->
        <div class="h-14 bg-gray-900/50 border-b border-thin border-hot-pink/20 flex items-center justify-between px-6 sticky top-0 z-10 backdrop-blur-sm">
            <!-- Left: Contact Card Style Layout -->
            <div class="flex items-center space-x-2">
                <div
                    id="drift-avatar"
                    x-data="{ avatar: '/interface/avatars/default/avatar-1.png' }"
                    x-init="$watch('avatar', value => {
        $el.querySelector('img').src = value;
      })"
                    class="w-12 h-12 bg-hot-pink/20 rounded-full shadow-lg border-2 border-electric-blue/30 transition-all"
                    style="box-shadow: 0 0 10px rgba(0, 212, 255, 0.4);"
                    x-cloak
                >
                    <img :src="avatar" alt="Holis Avatar" class="rounded-full w-full h-full object-cover">
                </div>
                <div class="flex items-center space-x-4">
                    <div>
                        <div class="flex items-center space-x-3">
                            <h2 class="text-base font-medium text-gray-300">Agent ID: </h2>
                            <span class="text-sm font-medium text-electric-blue/80">C1-13</span>

                            <span class="bg-electric-blue/20 text-electric-blue/80 text-xs px-2 py-0.5 rounded-full font-medium">v1.1.2</span>
                        </div>
                        <div class="text-xs font-medium text-gray-300">
                            <span>Role:</span>
                            <span class="text-hot-pink/80">Chat Assistant</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Search Input -->
            <div class="flex items-center space-x-4">
                <div class="relative" x-data="headerSearch()">
                    <div class="flex items-center">
                        <input
                            x-model="searchQuery"
                            x-on:focus="searchOpen = true; console.log('Header search: Input focused')"
                            x-on:blur="setTimeout(() => { if (!$refs.dropdown?.matches(':hover')) { searchOpen = false; console.log('Header search: Input blurred, closing dropdown'); } }, 200)"
                            x-on:input.debounce.300ms="handleSearch()"
                            placeholder="Search fragments..."
                            class="text-sm bg-gray-800 border border-gray-700 rounded-l-md px-3 py-2 text-text-primary placeholder-text-muted focus:outline-none focus:border-hot-pink w-64"
                        >
                        <div class="bg-hot-pink h-10 w-1 rounded-r-md flex items-center justify-center">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-900"/>
                        </div>
                    </div>

                    <!-- Search Dropdown -->
                    <div
                        x-ref="dropdown"
                        x-show="searchOpen && searchQuery.length > 0"
                        x-cloak
                        class="absolute top-full left-0 right-0 mt-1 bg-gray-800 border border-hot-pink/30 rounded-md shadow-lg z-50 max-h-80 overflow-y-auto"
                        style="pointer-events: auto;"
                        x-on:mouseenter="console.log('Header search: Mouse entered dropdown')"
                        x-on:mouseleave="console.log('Header search: Mouse left dropdown')"
                    >
                        <div x-show="$wire.recallLoading" class="p-3 text-xs text-text-muted text-center">
                            <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin inline mr-1"/>
                            Searching...
                        </div>

                        <div x-show="!$wire.recallLoading && $wire.recallResults.length === 0 && searchQuery.length >= 2" class="p-3 text-xs text-text-muted text-center">
                            No fragments found
                        </div>

                        <template x-for="(result, index) in $wire.recallResults" :key="result.id">
                            <div
                                class="p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-600 last:border-b-0 transition-colors"
                                x-on:mousedown.prevent.stop="selectResult(index)"
                                x-on:click.prevent.stop="selectResult(index)"
                            >
                                <div class="flex items-start space-x-2">
                                    <!-- Type Badge -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium flex-shrink-0 mt-0.5"
                                        :class="{
                                            'bg-green-900/20 text-green-400 border border-green-500/20': result.type === 'todo' || result.type === 'task',
                                            'bg-purple-900/20 text-purple-400 border border-purple-500/20': result.type === 'meeting',
                                            'bg-yellow-900/20 text-yellow-400 border border-yellow-500/20': result.type === 'idea' || result.type === 'insight',
                                            'bg-gray-700 text-gray-300 border border-gray-500/20': result.type === 'note',
                                            'bg-blue-900/20 text-blue-400 border border-blue-500/20': !['todo', 'task', 'meeting', 'idea', 'insight', 'note'].includes(result.type)
                                        }"
                                        x-text="result.type">
                                    </span>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-200 truncate" x-text="result.title"></div>
                                        <div class="text-xs text-gray-400 line-clamp-2 mt-1">
                                            <template x-if="result.snippet">
                                                <span x-html="result.snippet"></span>
                                            </template>
                                            <template x-if="!result.snippet">
                                                <span x-text="result.preview"></span>
                                            </template>
                                        </div>

                                        <!-- Tags, Scores and Date -->
                                        <div class="flex items-center space-x-2 mt-2">
                                            <template x-if="result.tags && result.tags.length > 0">
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="tag in result.tags.slice(0, 2)" :key="tag">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-700 text-gray-300" x-text="'#' + tag"></span>
                                                    </template>
                                                    <template x-if="result.tags.length > 2">
                                                        <span class="text-xs text-gray-400" x-text="'+' + (result.tags.length - 2)"></span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="result.vec_sim > 0">
                                                <span class="text-xs text-electric-blue/60" :title="'Vector: ' + result.vec_sim.toFixed(3) + ' | Text: ' + result.txt_rank.toFixed(3)">
                                                    AI Match
                                                </span>
                                            </template>
                                            <div class="text-xs text-gray-400" x-text="result.created_at"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Chat Content -->
        <div class="flex-1 p-6 overflow-y-auto space-y-4" id="chat-output">
            <!-- Chat Messages -->
            @foreach ($chatMessages as $entry)
                @if (($entry['type'] ?? '') === 'command_result')
                    <!-- Command Result Injection -->
                    @include('livewire.command-result', [
                        'type' => $entry['command_type'],
                        'data' => $entry['data'],
                        'message' => $entry['message']
                    ])
                @else
                    <x-search-result-card
                        :fragment="$entry"
                        :showTimestamp="true"
                        :showScore="false"
                        :showActions="true"
                    />
                @endif
            @endforeach

            <!-- Todos Section -->
            @if (!empty($recalledTodos))
                @php
                    $todoFragments = $this->getTodoFragments();
                @endphp

                <div class="pixel-card pixel-card-cyan p-4 glow-cyan">
                    <h2 class="text-lg font-semibold text-neon-cyan mb-3">
                        <x-heroicon-o-clipboard-document-list class="inline w-5 h-5 mr-1"/>
                        Todos ({{ $todoFragments->count() }})
                    </h2>
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

        <!-- Undo Toast Row (slides up from below) -->
        <x-undo-toast />

        <!-- Error Toast Row (slides up from below) -->
        <x-error-toast />

        <!-- Success Toast Row (slides up from below) -->
        <x-success-toast />

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
    <div class="w-80 bg-gray-900/95 border-l border-thin border-electric-blue/20 flex flex-col">
        <!-- Widgets Section -->
        <div class="flex-1 p-4 overflow-y-auto">

            <!-- System Widgets -->
            <div class="space-y-4 mb-6">
                <!-- Stats Widget -->
                <div class="bg-gray-800 rounded-pixel p-3 border border-hot-pink/20">
                    <h4 class="text-xs font-medium text-hot-pink mb-2">Today's Activity</h4>
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


                <!-- Recent Bookmarks Widget -->
                <div
                    class="bg-gray-800 rounded-pixel p-3 border border-neon-cyan/20"
                    x-data="bookmarkWidget()"
                    x-init="init(); loadRecentBookmarks()"
                    x-cloak
                >
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-medium text-neon-cyan">Recent Bookmarks</h4>
                        <button
                            x-show="!searchMode"
                            x-cloak
                            x-on:click="searchMode = true; $nextTick(() => $refs.searchInput.focus())"
                            class="text-xs text-text-muted hover:text-neon-cyan transition-colors"
                        >
                            <x-heroicon-o-magnifying-glass class="w-3 h-3"/>
                        </button>
                        <button
                            x-show="searchMode"
                            x-cloak
                            x-on:click="clearSearch()"
                            class="text-xs text-text-muted hover:text-neon-cyan transition-colors"
                        >
                            <x-heroicon-o-x-mark class="w-3 h-3"/>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div x-show="searchMode" x-cloak class="mb-3">
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
                                <x-heroicon-o-bookmark class="w-3 h-3 text-hot-pink flex-shrink-0"/>
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

                    // Listen for fragment deletion events
                    window.addEventListener('show-undo-toast', () => {
                        this.loadRecentBookmarks();
                    });

                    // Listen for fragment restoration events
                    Livewire.on('undo-fragment', () => {
                        setTimeout(() => this.loadRecentBookmarks(), 100);
                    });

                    // Listen for bookmark toggle events
                    window.addEventListener('bookmark-toggled', () => {
                        this.loadRecentBookmarks();
                    });

                    // Listen for fragment restoration events
                    window.addEventListener('fragment-restored', () => {
                        this.loadRecentBookmarks();
                    });
                },

                async loadRecentBookmarks() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/bookmarks/recent?limit=6');
                        if (response.ok) {
                            const data = await response.json();
                            this.bookmarks = data.bookmarks;
                        } else if (response.status !== 404) {
                            console.warn('Failed to load recent bookmarks:', response.status);
                        }
                    } catch (error) {
                        console.log('Failed to load recent bookmarks (this is normal for new systems):', error.message);
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
                        } else if (response.status !== 404) {
                            console.warn('Failed to search bookmarks:', response.status);
                        }
                    } catch (error) {
                        console.log('Failed to search bookmarks (this is normal for new systems):', error.message);
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

                    // Create modal directly using the same pattern as header search
                    try {
                        console.log('Bookmark widget: Opening fragment modal for ID:', bookmark.fragment_id);
                        
                        // Fetch fragment data
                        const response = await fetch(`/api/fragments/${bookmark.fragment_id}`);
                        if (!response.ok) {
                            throw new Error(`Failed to load fragment: HTTP ${response.status}`);
                        }
                        
                        const fragmentData = await response.json();
                        console.log('Bookmark widget: Fragment data loaded:', fragmentData);
                        
                        // Show the fragment using direct modal creation (same as header search)
                        this.showFragmentInModal(fragmentData);
                        
                    } catch (error) {
                        console.error('Bookmark widget: Failed to load fragment:', error);
                        alert('Failed to load bookmark: ' + error.message);
                    } finally {
                        // Reset the flag after a delay to prevent accidental rapid clicks
                        setTimeout(() => {
                            this.openingModal = false;
                        }, 300);
                    }
                },
                
                showFragmentInModal(fragmentData) {
                    // Create or get modal container
                    let modalContainer = document.getElementById('bookmark-result-modal');
                    if (!modalContainer) {
                        modalContainer = document.createElement('div');
                        modalContainer.id = 'bookmark-result-modal';
                        modalContainer.className = 'fixed inset-0 z-50 hidden';
                        document.body.appendChild(modalContainer);
                    }
                    
                    const fragmentType = fragmentData.type || 'unknown';
                    
                    modalContainer.innerHTML = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="document.getElementById('bookmark-result-modal').classList.add('hidden'); document.body.style.overflow = 'auto';"></div>
                        <div class="fixed inset-0 flex items-center justify-center p-4">
                            <div class="relative max-w-4xl max-h-[90vh] overflow-auto bg-surface-2 p-6 rounded-pixel border border-thin border-hot-pink/30" style="min-width: 750px;">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <h2 class="text-lg font-medium text-text-primary">Fragment #${fragmentData.id}</h2>
                                        <span class="text-xs bg-hot-pink/20 text-hot-pink px-2 py-1 rounded-pixel border border-hot-pink/40">${fragmentType.toUpperCase()}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="window.copyFragmentFromModal(event, '${this.escapeHtml(fragmentData.message)}')" class="p-1.5 bg-gray-700 hover:bg-neon-cyan/20 text-gray-400 hover:text-neon-cyan rounded border border-gray-600 hover:border-neon-cyan/40 hover:shadow-sm hover:shadow-neon-cyan/20 transition-all" title="Copy fragment">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                        <button onclick="document.getElementById('bookmark-result-modal').classList.add('hidden'); document.body.style.overflow = 'auto';" class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 text-gray-400 hover:text-hot-pink rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all" title="Close">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="bg-surface-elevated p-4 rounded-pixel">
                                        <div class="text-sm text-text-primary whitespace-pre-wrap">${this.escapeHtml(fragmentData.message)}</div>
                                    </div>
                                    
                                    <div class="flex justify-between text-xs text-text-muted">
                                        <span>Created: ${new Date(fragmentData.created_at).toLocaleDateString()}</span>
                                        <span>ID: ${fragmentData.id}</span>
                                    </div>
                                    
                                    ${fragmentData.tags && fragmentData.tags.length ? `
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="text-text-muted">Tags:</span>
                                            ${fragmentData.tags.map(tag => `
                                                <span class="bg-electric-blue/20 text-electric-blue px-2 py-0.5 rounded border border-electric-blue/40">${tag}</span>
                                            `).join('')}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Show the modal
                    modalContainer.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    
                    // Add ESC key handler
                    const escHandler = (e) => {
                        if (e.key === 'Escape') {
                            modalContainer.classList.add('hidden');
                            document.body.style.overflow = 'auto';
                            document.removeEventListener('keydown', escHandler);
                        }
                    };
                    document.addEventListener('keydown', escHandler);
                },
                
                escapeHtml(text) {
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return text.replace(/[&<>"']/g, m => map[m]);
                }
            };
        }

        function headerSearch() {
            return {
                searchOpen: false,
                searchQuery: '',
                openingModal: false,

                handleSearch() {
                    console.log('Header search: handleSearch called with query:', this.searchQuery);

                    if (this.searchQuery.length < 2) {
                        console.log('Header search: Query too short, clearing results');
                        this.$wire.set('recallResults', []);
                        return;
                    }

                    console.log('Header search: Setting recallQuery and calling performRecallSearch');
                    // Use existing recall search functionality
                    this.$wire.set('recallQuery', this.searchQuery);
                    this.$wire.call('performRecallSearch').then(() => {
                        console.log('Header search: performRecallSearch completed, results:', this.$wire.recallResults);
                    }).catch(error => {
                        console.error('Header search: performRecallSearch failed:', error);
                    });
                },

                async selectResult(index) {
                    console.log('Header search: selectResult called with index:', index);
                    console.log('Header search: recallResults:', this.$wire.recallResults);

                    const result = this.$wire.recallResults[index];
                    console.log('Header search: selected result:', result);

                    if (!result || !result.id) {
                        console.warn('Header search: No fragment ID for result:', result);
                        return;
                    }

                    // Prevent multiple simultaneous openings
                    if (this.openingModal) {
                        console.log('Header search: Modal already opening, ignoring click');
                        return;
                    }

                    this.openingModal = true;
                    this.searchOpen = false;
                    this.searchQuery = '';

                    // Simple direct approach - just open the modal with the fragment ID
                    try {
                        console.log('Header search: Opening fragment modal for ID:', result.id);
                        
                        // Create a simple modal directly without waiting for LinkHandler
                        const response = await fetch(`/api/fragments/${result.id}`);
                        if (!response.ok) {
                            throw new Error(`Failed to load fragment: HTTP ${response.status}`);
                        }
                        
                        const fragmentData = await response.json();
                        console.log('Header search: Fragment data loaded:', fragmentData);
                        
                        // Show the fragment using a simple modal
                        this.showFragmentInModal(fragmentData);
                        
                    } catch (error) {
                        console.error('Header search: Failed to load fragment:', error);
                        alert('Failed to load fragment: ' + error.message);
                    } finally {
                        // Reset the flag after a delay
                        setTimeout(() => {
                            this.openingModal = false;
                        }, 500);
                    }
                },
                
                showFragmentInModal(fragmentData) {
                    // Create or get modal container
                    let modalContainer = document.getElementById('search-result-modal');
                    if (!modalContainer) {
                        modalContainer = document.createElement('div');
                        modalContainer.id = 'search-result-modal';
                        modalContainer.className = 'fixed inset-0 z-50 hidden';
                        document.body.appendChild(modalContainer);
                    }
                    
                    const fragmentType = fragmentData.type || 'unknown';
                    
                    modalContainer.innerHTML = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="document.getElementById('search-result-modal').classList.add('hidden')"></div>
                        <div class="fixed inset-0 flex items-center justify-center p-4">
                            <div class="relative max-w-4xl max-h-[90vh] overflow-auto bg-surface-2 p-6 rounded-pixel border border-thin border-hot-pink/30" style="min-width: 750px;">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <h2 class="text-lg font-medium text-text-primary">Fragment #${fragmentData.id}</h2>
                                        <span class="text-xs bg-hot-pink/20 text-hot-pink px-2 py-1 rounded-pixel border border-hot-pink/40">${fragmentType.toUpperCase()}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="window.copyFragmentFromModal(event, '${this.escapeHtml(fragmentData.message)}')" class="p-1.5 bg-gray-700 hover:bg-neon-cyan/20 text-gray-400 hover:text-neon-cyan rounded border border-gray-600 hover:border-neon-cyan/40 hover:shadow-sm hover:shadow-neon-cyan/20 transition-all" title="Copy fragment">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                        <button onclick="document.getElementById('search-result-modal').classList.add('hidden'); document.body.style.overflow = 'auto';" class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 text-gray-400 hover:text-hot-pink rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all" title="Close">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="bg-surface-elevated p-4 rounded-pixel">
                                        <div class="text-sm text-text-primary whitespace-pre-wrap">${this.escapeHtml(fragmentData.message)}</div>
                                    </div>
                                    
                                    <div class="flex justify-between text-xs text-text-muted">
                                        <span>Created: ${new Date(fragmentData.created_at).toLocaleDateString()}</span>
                                        <span>ID: ${fragmentData.id}</span>
                                    </div>
                                    
                                    ${fragmentData.tags && fragmentData.tags.length ? `
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="text-text-muted">Tags:</span>
                                            ${fragmentData.tags.map(tag => `
                                                <span class="bg-electric-blue/20 text-electric-blue px-2 py-0.5 rounded border border-electric-blue/40">${tag}</span>
                                            `).join('')}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    modalContainer.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    
                    // Close modal on escape key
                    const escapeHandler = (e) => {
                        if (e.key === 'Escape') {
                            modalContainer.classList.add('hidden');
                            document.body.style.overflow = 'auto';
                            document.removeEventListener('keydown', escapeHandler);
                        }
                    };
                    document.addEventListener('keydown', escapeHandler);
                },
                
                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            };
        }
        
        // Global function for copying fragment content from modal
        window.copyFragmentFromModal = async function(event, text) {
            try {
                // Unescape HTML entities
                const textarea = document.createElement('textarea');
                textarea.innerHTML = text;
                const unescapedText = textarea.value;
                
                // Copy to clipboard
                await navigator.clipboard.writeText(unescapedText);
                
                // Visual feedback on the button
                const button = event.target.closest('button');
                if (button) {
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                    button.classList.add('text-green-400', 'border-green-400/40');
                    button.classList.remove('text-gray-400', 'border-gray-600');
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('text-green-400', 'border-green-400/40');
                        button.classList.add('text-gray-400', 'border-gray-600');
                    }, 2000);
                }
            } catch (error) {
                console.error('Failed to copy fragment:', error);
                
                // Error feedback
                const button = event.target.closest('button');
                if (button) {
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                    button.classList.add('text-red-400', 'border-red-400/40');
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('text-red-400', 'border-red-400/40');
                    }, 2000);
                }
            }
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
                            // Don't show autocomplete if in command mode
                            if (this.$wire.inCommandMode) {
                                return;
                            }
                            this.autocompleteActive = true;
                            originalShow();
                        };

                        this.autocompleteEngine.hide = () => {
                            this.autocompleteActive = false;
                            originalHide();
                        };

                        // Watch for command mode changes
                        this.$watch('$wire.inCommandMode', (isInCommandMode) => {
                            if (isInCommandMode && this.autocompleteActive) {
                                this.autocompleteEngine.hide();
                            }
                        });
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
        <div class="bg-gray-900 rounded-pixel p-6 w-96 shadow-xl border border-electric-blue/30">
            <h3 class="text-lg font-semibold mb-4 text-center text-electric-blue">
                <x-heroicon-o-command-line class="inline w-5 h-5 mr-1"/>
                Command Palette
            </h3>
            <div class="space-y-2">
                @foreach (\App\Services\CommandRegistry::all() as $cmd)
                    <button
                        wire:click="executeCommand('{{ $cmd }}')"
                        x-on:click="open = false; $nextTick(() => document.querySelector('textarea[x-ref=chatTextarea]')?.focus())"
                        class="w-full text-left bg-gray-800 hover:bg-hot-pink/20 text-gray-300 hover:text-hot-pink rounded-pixel px-3 py-2 text-sm border border-hot-pink/20 transition-colors"
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
        <div class="bg-gray-900 rounded-pixel p-6 w-96 shadow-xl border border-hot-pink/30">
            <h3 class="text-lg font-semibold mb-4 text-center text-hot-pink">
                <x-heroicon-o-archive-box class="inline w-5 h-5 mr-1"/>
                Create New Vault
            </h3>

            <form wire:submit.prevent="createNewVault" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Vault Name *</label>
                    <input
                        type="text"
                        wire:model="newVaultName"
                        class="w-full bg-gray-800 text-gray-200 rounded-pixel p-3 border border-hot-pink/20 focus:ring-1 focus:ring-hot-pink focus:border-hot-pink"
                        placeholder="Enter vault name..."
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Description</label>
                    <textarea
                        wire:model="newVaultDescription"
                        class="w-full bg-gray-800 text-gray-200 rounded-pixel p-3 border border-hot-pink/20 focus:ring-1 focus:ring-hot-pink focus:border-hot-pink resize-none"
                        rows="3"
                        placeholder="Brief description (optional)..."
                    ></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button
                        type="submit"
                        class="flex-1 bg-hot-pink text-white py-2 px-4 rounded-pixel hover:bg-hot-pink/90 transition-colors text-sm font-medium"
                    >
                        Create Vault
                    </button>
                    <button
                        type="button"
                        wire:click="closeVaultModal"
                        class="flex-1 bg-gray-800 text-gray-400 py-2 px-4 rounded-pixel hover:bg-gray-700 transition-colors text-sm font-medium border border-gray-600"
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
        <div class="bg-gray-900 rounded-pixel p-6 w-96 shadow-xl border border-electric-blue/30">
            <h3 class="text-lg font-semibold mb-4 text-center text-electric-blue">
                <x-heroicon-o-folder class="inline w-5 h-5 mr-1"/>
                Create New Project
            </h3>

            <form wire:submit.prevent="createNewProject" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Project Name *</label>
                    <input
                        type="text"
                        wire:model="newProjectName"
                        class="w-full bg-gray-800 text-gray-200 rounded-pixel p-3 border border-electric-blue/20 focus:ring-1 focus:ring-electric-blue focus:border-electric-blue"
                        placeholder="Enter project name..."
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-2">Description</label>
                    <textarea
                        wire:model="newProjectDescription"
                        class="w-full bg-gray-800 text-gray-200 rounded-pixel p-3 border border-electric-blue/20 focus:ring-1 focus:ring-electric-blue focus:border-electric-blue resize-none"
                        rows="3"
                        placeholder="Brief description (optional)..."
                    ></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button
                        type="submit"
                        class="flex-1 bg-electric-blue text-white py-2 px-4 rounded-pixel hover:bg-electric-blue/90 transition-colors text-sm font-medium"
                    >
                        Create Project
                    </button>
                    <button
                        type="button"
                        wire:click="closeProjectModal"
                        class="flex-1 bg-gray-800 text-gray-400 py-2 px-4 rounded-pixel hover:bg-gray-700 transition-colors text-sm font-medium border border-gray-600"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recall Palette (Ctrl+K Search) - Inline -->
    <div
        x-data="recallPalette"
        x-show="$wire.showRecallPalette"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape="$wire.closeRecallPalette()"
        @keydown.ctrl.k.prevent="$wire.closeRecallPalette()"
    >
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        {{-- Modal Container --}}
        <div class="flex min-h-full items-start justify-center p-4 text-center sm:p-0">
            <div class="relative mt-20 w-full max-w-2xl transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-2xl transition-all">

                {{-- Search Input --}}
                <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-4">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input
                            wire:model.live="recallQuery"
                            type="text"
                            class="w-full pl-10 pr-4 py-3 border-0 text-lg text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 bg-transparent focus:ring-0 focus:outline-none"
                            placeholder="Search fragments... (type:todo #urgent @john has:link)"
                            x-ref="searchInput"
                            x-init="$nextTick(() => $refs.searchInput.focus())"
                            @keydown.arrow-up.prevent="$wire.moveRecallSelection('up')"
                            @keydown.arrow-down.prevent="$wire.moveRecallSelection('down')"
                            @keydown.enter.prevent="if (!$wire.recallLoading) $wire.selectCurrentRecallResult()"
                            autocomplete="off"
                        />
                        <div wire:loading wire:target="performRecallSearch" class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Results Container --}}
                <div class="max-h-96 overflow-y-auto">

                    {{-- Search Results --}}
                    @if(count($recallResults ?? []) > 0)
                        <div class="py-2">
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Search Results ({{ count($recallResults ?? []) }})
                            </div>
                            @foreach($recallResults as $index => $result)
                                <div
                                    class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-l-4 {{ $selectedRecallIndex === $index ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'border-transparent' }}"
                                    wire:click="selectRecallResult({{ $index }})"
                                >
                                    <div class="flex items-start space-x-3">
                                        {{-- Fragment Type Badge --}}
                                        <div class="flex-shrink-0 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ match($result['type']) {
                                                    'todo', 'task' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                                    'meeting' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
                                                    'idea', 'insight' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                                    'note' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
                                                } }}">
                                                {{ $result['type'] }}
                                            </span>
                                        </div>

                                        {{-- Content --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                {{ $result['title'] }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mt-1">
                                                @if(!empty($result['snippet']))
                                                    {!! $result['snippet'] !!}
                                                @else
                                                    {{ $result['preview'] }}
                                                @endif
                                            </div>

                                            {{-- Tags and Metadata --}}
                                            <div class="flex items-center space-x-2 mt-2">
                                                @if(!empty($result['tags']))
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach(array_slice($result['tags'], 0, 3) as $tag)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                                #{{ $tag }}
                                                            </span>
                                                        @endforeach
                                                        @if(count($result['tags']) > 3)
                                                            <span class="text-xs text-gray-400">+{{ count($result['tags']) - 3 }}</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if(isset($result['vec_sim']) && $result['vec_sim'] > 0)
                                                    <span class="text-xs text-blue-500 dark:text-blue-400" title="Vector: {{ number_format($result['vec_sim'], 3) }} | Text: {{ number_format($result['txt_rank'] ?? 0, 3) }}">
                                                        AI Match
                                                    </span>
                                                @endif

                                                <div class="text-xs text-gray-400">
                                                    {{ $result['created_at'] }}
                                                </div>

                                                @if(isset($result['search_score']) && $result['search_score'] > 0 && !isset($result['vec_sim']))
                                                    <div class="text-xs text-gray-400">
                                                        Score: {{ number_format($result['search_score'], 1) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- No Results Message --}}
                    @if(strlen($recallQuery ?? '') >= 2 && count($recallResults ?? []) === 0 && !($recallLoading ?? false))
                        <div class="px-4 py-8 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0120 12c0-4.411-3.589-8-8-8s-8 3.589-8 8c0 2.76 1.401 5.193 3.536 6.708" />
                                </svg>
                                <div class="text-lg font-medium mb-2">No fragments found</div>
                                <div class="text-sm">Try different search terms or remove some filters</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer with Help Text --}}
                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-gray-50 dark:bg-gray-750">
                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex space-x-4">
                            <div><kbd class="px-1 py-0.5 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded">↑↓</kbd> Navigate</div>
                            <div><kbd class="px-1 py-0.5 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded">Enter</kbd> Select</div>
                            <div><kbd class="px-1 py-0.5 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded">Esc</kbd> Close</div>
                        </div>
                        @if(count($recallResults ?? []) > 0)
                            <div>{{ $selectedRecallIndex + 1 }} of {{ count($recallResults ?? []) }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Initialize Alpine.js components
        document.addEventListener('alpine:init', () => {
            Alpine.data('recallPalette', () => ({
                init() {
                    // Listen for Ctrl+K globally
                    document.addEventListener('keydown', (e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                            e.preventDefault();
                            this.$wire.openRecallPalette();
                        }
                    });
                }
            }));
        });

        // Listen for undo toast events
        document.addEventListener('livewire:init', function() {
            Livewire.on('show-undo-toast', function(event) {
                console.log('Received undo toast event:', event);
                console.log('All arguments:', Array.from(arguments));

                // In Livewire 3, named parameters are passed as properties of the event object
                let fragmentId = event.fragmentId || event.detail?.fragmentId;
                let message = event.message || event.detail?.message;
                let objectType = event.objectType || event.detail?.objectType || 'fragment';

                // Fallback: check if data is in arguments
                if (!fragmentId && arguments.length > 1) {
                    fragmentId = arguments[0]?.fragmentId || arguments[1];
                    message = arguments[0]?.message || arguments[2];
                    objectType = arguments[0]?.objectType || arguments[3] || 'fragment';
                }

                console.log('Extracted values:', { fragmentId, message, objectType });

                if (!fragmentId) {
                    console.error('No fragmentId found in event data');
                    return;
                }

                // Find the undo toast by ID and trigger display
                const toastElement = document.getElementById('undo-toast');
                console.log('Toast element found:', !!toastElement);

                if (toastElement && toastElement._x_dataStack && toastElement._x_dataStack[0]) {
                    console.log('Calling display with:', fragmentId, message, objectType);
                    toastElement._x_dataStack[0].display(fragmentId, message, objectType);
                } else {
                    console.error('Could not find undo toast Alpine component');
                }
            });

            // Also listen for custom browser events as fallback
            window.addEventListener('show-undo-toast', function(event) {
                console.log('Received custom undo toast event:', event.detail);

                const fragmentId = event.detail.fragmentId;
                const message = event.detail.message;
                const objectType = event.detail.objectType || 'fragment';

                console.log('Custom event values:', { fragmentId, message, objectType });

                if (fragmentId) {
                    const toastElement = document.getElementById('undo-toast');
                    if (toastElement && toastElement._x_dataStack && toastElement._x_dataStack[0]) {
                        console.log('Calling display via custom event with:', fragmentId, message, objectType);
                        toastElement._x_dataStack[0].display(fragmentId, message, objectType);
                    }
                }
            });

            // DISABLED: First listener commented out to avoid conflicts
            /*
            // Listen for success toast events (for /frag and /chaos commands)
            Livewire.on('show-success-toast', function(event) {
                console.log('FIRST success toast listener received:', event);

                let title = event.title || event.detail?.title || 'Success';
                let message = event.message || event.detail?.message;
                let fragmentType = event.fragmentType || event.detail?.fragmentType || 'fragment';
                let fragmentId = event.fragmentId || event.detail?.fragmentId || null;

                console.log('FIRST listener success toast values:', { title, message, fragmentType, fragmentId });

                if (message) {
                    const toastElement = document.getElementById('success-toast');
                    if (toastElement && toastElement._x_dataStack && toastElement._x_dataStack[0]) {
                        console.log('FIRST listener calling display with:', title, message, fragmentType, fragmentId);
                        toastElement._x_dataStack[0].display(title, message, fragmentType, fragmentId, 5);
                    } else {
                        console.log('FIRST listener: success-toast element not found or not ready');
                    }
                }
            });
            */

            // Listen for browser success toast events
            window.addEventListener('show-success-toast', function(event) {
                console.log('Received custom success toast event:', event.detail);

                const message = event.detail.message;
                const objectType = event.detail.objectType || 'fragment';

                if (message) {
                    const toastElement = document.getElementById('undo-toast');
                    if (toastElement && toastElement._x_dataStack && toastElement._x_dataStack[0]) {
                        console.log('Calling displaySuccess via custom event with:', message, objectType);
                        toastElement._x_dataStack[0].displaySuccess(message, objectType, 10);
                    }
                }
            });

            Livewire.on('show-error-toast', function(data) {
                // Use the unified undo-toast for error messages
                const toastElement = document.getElementById('undo-toast');
                if (toastElement && toastElement._x_dataStack && toastElement._x_dataStack[0]) {
                    toastElement._x_dataStack[0].displayError(data.message, 2);
                }
            });

            // Handle success toasts with custom titles (for todo creation, etc.)
            Livewire.on('show-success-toast', function(data) {
                console.log('SECOND success toast handler received:', data);
                
                // Livewire events come as arrays, get the first element
                const eventData = Array.isArray(data) ? data[0] : data;
                console.log('Processed event data:', eventData);
                console.log('Title check:', { title: eventData.title, isCustom: (eventData.title && eventData.title !== 'Success') });
                
                // If we have a custom title, use success-toast component
                if (eventData.title && eventData.title !== 'Success') {
                    console.log('Attempting to use success-toast for custom title:', eventData.title);
                    const successToastElement = document.getElementById('success-toast');
                    console.log('success-toast element found:', !!successToastElement);
                    
                    if (successToastElement && successToastElement._x_dataStack && successToastElement._x_dataStack[0]) {
                        console.log('SUCCESS: Using success-toast for custom title:', eventData.title);
                        successToastElement._x_dataStack[0].display(
                            eventData.title, 
                            eventData.message, 
                            eventData.fragmentType || 'fragment', 
                            eventData.fragmentId || null, 
                            3
                        );
                        return;
                    } else {
                        console.log('FAILED: success-toast element not ready, _x_dataStack:', successToastElement?._x_dataStack);
                    }
                }
                
                // Fallback to undo-toast for generic messages (like fragment restored)
                console.log('FALLBACK: Using undo-toast for message');
                const undoToastElement = document.getElementById('undo-toast');
                if (undoToastElement && undoToastElement._x_dataStack && undoToastElement._x_dataStack[0]) {
                    console.log('undo-toast element ready, displaying success');
                    undoToastElement._x_dataStack[0].displaySuccess(
                        eventData.message, 
                        eventData.fragmentType || 'fragment', 
                        2
                    );
                } else {
                    console.log('ERROR: undo-toast element not found or not ready');
                }
            });

        });

        // Bookmark and copy functionality (fallback if link-handler.js not loaded)
        if (!window.checkBookmarkStatus) {
            window.checkBookmarkStatus = async function(fragmentId, element) {
                console.log('checkBookmarkStatus called:', fragmentId, element);
                if (!fragmentId || fragmentId <= 0) return;
                try {
                    const response = await fetch(`/api/fragments/${fragmentId}/bookmark`);
                    if (!response.ok) return;
                    const data = await response.json();
                    console.log('Initial bookmark status data:', data);
                    
                    // Try different ways to access Alpine component
                    let alpineComponent;
                    if (window.Alpine && Alpine.$data) {
                        alpineComponent = Alpine.$data(element);
                    }
                    
                    console.log('Alpine component for status check:', alpineComponent);
                    
                    if (alpineComponent && typeof alpineComponent.bookmarked !== 'undefined') {
                        alpineComponent.bookmarked = data.is_bookmarked;
                        console.log('Set initial bookmarked to:', data.is_bookmarked);
                    } else {
                        console.warn('Could not access Alpine component data for bookmark status');
                        // Fallback: set data attribute for visual state
                        element.setAttribute('data-bookmarked', data.is_bookmarked);
                    }
                } catch (error) {
                    console.error('checkBookmarkStatus error:', error);
                    return;
                }
            };
        }

        if (!window.toggleBookmark) {
            window.toggleBookmark = async function(fragmentId, element) {
                console.log('toggleBookmark called:', fragmentId, element);
                try {
                    const response = await fetch(`/api/fragments/${fragmentId}/bookmark`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });
                    console.log('Bookmark toggle response status:', response.status);
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const data = await response.json();
                    console.log('Bookmark toggle data:', data);
                    
                    // Update Alpine component state
                    const alpineComponent = Alpine.$data(element);
                    console.log('Alpine component for toggle:', alpineComponent);
                    if (alpineComponent) {
                        alpineComponent.bookmarked = data.is_bookmarked;
                        console.log('Updated bookmarked state to:', data.is_bookmarked);
                    }

                    // Dispatch event to notify bookmark widget to refresh (CRITICAL for real-time updates)
                    window.dispatchEvent(new CustomEvent('bookmark-toggled', {
                        detail: { fragmentId, action: data.action, isBookmarked: data.is_bookmarked }
                    }));

                } catch (error) {
                    console.error('Failed to toggle bookmark:', error.message);
                }
            };
        }

        if (!window.copyChatMessage) {
            window.copyChatMessage = async function(button) {
                try {
                    let messageContainer = button.parentElement;
                    while (messageContainer && !messageContainer.classList.contains('relative')) {
                        messageContainer = messageContainer.parentElement;
                    }
                    if (!messageContainer) {
                        messageContainer = button.closest('div.flex-1, div[class*="bg-surface-card"]');
                    }
                    if (!messageContainer) throw new Error('Could not find message container');

                    const clonedContainer = messageContainer.cloneNode(true);
                    const copyButton = clonedContainer.querySelector('button');
                    if (copyButton) copyButton.remove();

                    let text = clonedContainer.textContent || clonedContainer.innerText;
                    text = text.replace(/📋\s*Copy/g, '').replace(/✅\s*Copied!/g, '').replace(/❌\s*Failed/g, '').trim();
                    
                    if (!text || text.length === 0) throw new Error('No text content found');

                    await navigator.clipboard.writeText(text);

                    const originalHtml = button.innerHTML;
                    button.innerHTML = '✅ Copied!';
                    button.classList.add('text-green-400', 'border-green-400/40');
                    button.classList.remove('text-neon-cyan', 'border-neon-cyan/40');

                    setTimeout(() => {
                        button.innerHTML = originalHtml;
                        button.classList.remove('text-green-400', 'border-green-400/40');
                        button.classList.add('text-neon-cyan', 'border-neon-cyan/40');
                    }, 2000);

                } catch (error) {
                    console.error('Failed to copy message:', error);
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '❌ Failed';
                    button.classList.add('text-red-400', 'border-red-400/40');
                    setTimeout(() => {
                        button.innerHTML = originalHtml;
                        button.classList.remove('text-red-400', 'border-red-400/40');
                    }, 2000);
                }
            };
        }
    </script>

</div>
