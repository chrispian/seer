@props(['message', 'type' => 'user', 'timestamp' => null, 'fragmentId' => null])

<div class="group py-3 px-4 transition-colors duration-200 rounded-pixel
    {{ $type === 'user' ? 'bg-hot-pink/5 border border-hot-pink/20 hover:bg-hot-pink/10' : 'hover:bg-gray-800/30' }}"
    @if($fragmentId) data-fragment-id="{{ $fragmentId }}" @endif>
    
    <div class="flex items-start justify-between">
        <div class="flex-1 mr-4">
            <div class="prose prose-sm dark:prose-invert max-w-none text-text-primary">
                {{ $slot }}
            </div>
            <div class="text-xs text-text-muted mt-1">
                {{ $timestamp ?? 'Just now' }}
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center space-x-2">
            <!-- Copy & Delete Buttons - Only show on hover -->
            <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <!-- Copy Button -->
                <button 
                    onclick="copyChatMessage(this)" 
                    class="p-1.5 bg-gray-700 hover:bg-neon-cyan/20 text-gray-400 hover:text-neon-cyan rounded border border-gray-600 hover:border-neon-cyan/40 hover:shadow-sm hover:shadow-neon-cyan/20 transition-all"
                    title="Copy message"
                >
                    <x-heroicon-o-document-duplicate class="w-4 h-4"/>
                </button>
                
                @if($fragmentId && is_numeric($fragmentId) && $fragmentId > 0)
                    <!-- Delete Button -->
                    <button 
                        wire:click="deleteFragment({{ $fragmentId }})" 
                        class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 text-gray-400 hover:text-hot-pink rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all"
                        title="Delete message"
                        onclick="event.stopPropagation();"
                    >
                        <x-heroicon-o-trash class="w-4 h-4"/>
                    </button>
                @endif
            </div>
            
            @if($fragmentId && is_numeric($fragmentId) && $fragmentId > 0)
                <!-- Bookmark Button - Independent visibility -->
                <button 
                    onclick="toggleBookmark({{ $fragmentId }}, this)"
                    class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all opacity-0 group-hover:opacity-100"
                    :class="bookmarked ? 'text-hot-pink border-hot-pink/40 !opacity-100' : 'text-gray-400 hover:text-hot-pink'"
                    title="Toggle bookmark"
                    data-fragment-id="{{ $fragmentId }}"
                    x-data="{ bookmarked: false }"
                    x-init="checkBookmarkStatus({{ $fragmentId }}, $el)"
                    x-cloak
                >
                    <x-heroicon-o-bookmark class="w-4 h-4"/>
                </button>
            @endif
        </div>
    </div>
</div>