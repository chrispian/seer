@props([
    'fragment',
    'showActions' => true,
    'showTimestamp' => true,
    'compact' => false,
    'highlight' => false
])

@php
    // Handle both fragment objects and arrays
    $fragmentId = is_array($fragment) ? ($fragment['id'] ?? null) : ($fragment->id ?? null);
    $message = is_array($fragment) ? ($fragment['message'] ?? '') : ($fragment->message ?? '');
    $createdAt = is_array($fragment) ? ($fragment['created_at'] ?? null) : ($fragment->created_at ?? null);

    // Handle type display - prefer 'name' over 'value' for better UX
    if (is_array($fragment)) {
        $typeName = $fragment['type']['name'] ?? ucfirst($fragment['type']['value'] ?? 'fragment');
        $typeValue = $fragment['type']['value'] ?? 'fragment';
    } else {
        $typeName = $fragment->type?->label ?? ucfirst($fragment->type?->value ?? 'fragment');
        $typeValue = $fragment->type?->value ?? 'fragment';
    }
@endphp

<div
    class="group transition-colors duration-200 rounded-md {{ $compact ? 'py-2 px-3' : 'py-3 px-4' }} {{ $highlight ? 'bg-electric-blue/10 border border-electric-blue/30' : 'bg-gray-900/60 hover:bg-gray-900/40' }}"
    @if($fragmentId) data-fragment-id="{{ $fragmentId }}" @endif
>
    <div class="flex items-start justify-between">
        <div class="flex-1 {{ $showActions ? 'mr-4' : '' }}">
            @if($showTimestamp && $createdAt)
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-medium">
                        <span class="text-white">Type:</span>
                        <span class="text-electric-blue">{{ $typeName }}</span>
                    </div>
                    <div class="text-xs text-text-muted">
                        {{ \Carbon\Carbon::parse($createdAt)->format('M j, g:i A') }}
                    </div>
                </div>
            @endif

            <div class="prose prose-sm dark:prose-invert max-w-none text-text-primary">
                <x-chat-markdown :fragment="null">
                    {{ $message }}
                </x-chat-markdown>
            </div>
        </div>

        @if($showActions && $fragmentId && is_numeric($fragmentId) && $fragmentId > 0)
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

                    <!-- Delete Button -->
                    <button
                        wire:click="deleteFragment({{ $fragmentId }})"
                        class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 text-gray-400 hover:text-hot-pink rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all"
                        title="Delete message"
                        onclick="event.stopPropagation();"
                    >
                        <x-heroicon-o-trash class="w-4 h-4"/>
                    </button>
                </div>

                <!-- Bookmark Button - Independent visibility -->
                <button
                    @click="window.toggleBookmark && window.toggleBookmark({{ $fragmentId }}, $el)"
                    class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all opacity-0 group-hover:opacity-100"
                    :class="bookmarked ? 'text-hot-pink border-hot-pink/40 !opacity-100' : 'text-gray-400 hover:text-hot-pink'"
                    title="Toggle bookmark"
                    data-fragment-id="{{ $fragmentId }}"
                    x-data="{ bookmarked: false }"
                    x-init="window.checkBookmarkStatus && window.checkBookmarkStatus({{ $fragmentId }}, $el)"
                    x-cloak
                >
                    <x-heroicon-o-bookmark class="w-4 h-4"/>
                </button>
            </div>
        @endif
    </div>
</div>
