@props([
    'fragment',
    'showActions' => true,
    'showTimestamp' => true,
    'showScore' => false,
    'compact' => false,
    'highlight' => false
])

@php
    // Handle both fragment objects and arrays
    $fragmentId = is_array($fragment) ? ($fragment['id'] ?? null) : ($fragment->id ?? null);
    $message = is_array($fragment) ? ($fragment['message'] ?? '') : ($fragment->message ?? '');
    $createdAt = is_array($fragment) ? ($fragment['created_at'] ?? null) : ($fragment->created_at ?? null);
    $score = is_array($fragment) ? ($fragment['score'] ?? 0) : 0;

    // Handle type display - use 'label' for human-readable names
    if (is_array($fragment)) {
        // Type could be an array with label/value, or just a string
        if (is_array($fragment['type'] ?? null)) {
            $typeName = $fragment['type']['label'] ?? ucfirst($fragment['type']['value'] ?? 'fragment');
            $typeValue = $fragment['type']['value'] ?? 'fragment';
        } else {
            // Type is just a string value
            $typeValue = $fragment['type'] ?? 'fragment';
            $typeName = ucfirst($typeValue);
        }
    } else {
        $typeName = $fragment->type?->label ?? ucfirst($fragment->type?->value ?? 'fragment');
        $typeValue = $fragment->type?->value ?? 'fragment';
    }
@endphp

<div
    class="group transition-colors duration-200 {{ $highlight ? 'bg-electric-blue/10 border border-electric-blue/30 rounded-lg p-3' : 'bg-gray-800 border border-electric-blue/20 rounded-lg p-3' }} relative"
    @if($fragmentId) data-fragment-id="{{ $fragmentId }}" @endif
>
    <div class="flex items-start justify-between">
        <div class="flex-1 {{ $showActions ? 'mr-4' : '' }} pr-20">
            {{-- Type Display --}}
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-medium">
                    <span class="text-white">Type:</span>
                    <span class="text-electric-blue">{{ $typeName }}</span>
                </div>
            </div>

            {{-- Message Content --}}
            <div class="prose prose-sm dark:prose-invert max-w-none text-text-primary">
                <x-chat-markdown :fragment="null">
                    {{ $message }}
                </x-chat-markdown>
            </div>
        </div>

        {{-- Right Side Actions and Info --}}
        <div class="absolute top-3 right-3 flex flex-col items-end space-y-2">
            {{-- Date (moved above icons as requested) --}}
            @if($showTimestamp && $createdAt)
                <div class="text-xs text-text-muted">
                    {{ \Carbon\Carbon::parse($createdAt)->format('M j, g:i A') }}
                </div>
            @endif

            {{-- Action Buttons (20% smaller icons) --}}
            @if($showActions && $fragmentId && is_numeric($fragmentId) && $fragmentId > 0)
                <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <!-- Copy Button -->
                    <button
                        onclick="copyChatMessage(this)"
                        class="p-1.5 bg-gray-700 hover:bg-neon-cyan/20 text-gray-400 hover:text-neon-cyan rounded border border-gray-600 hover:border-neon-cyan/40 hover:shadow-sm hover:shadow-neon-cyan/20 transition-all"
                        title="Copy message"
                    >
                        <x-heroicon-o-document-duplicate class="w-3.5 h-3.5"/>
                    </button>

                    <!-- Delete Button -->
                    <button
                        wire:click="deleteFragment({{ $fragmentId }})"
                        class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 text-gray-400 hover:text-hot-pink rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all"
                        title="Delete message"
                        onclick="event.stopPropagation();"
                    >
                        <x-heroicon-o-trash class="w-3.5 h-3.5"/>
                    </button>

                    <!-- Bookmark Button -->
                    <button
                        onclick="event.stopPropagation(); window.toggleBookmark && window.toggleBookmark({{ $fragmentId }}, this)"
                        class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all text-gray-400 hover:text-hot-pink"
                        title="Toggle bookmark"
                        data-fragment-id="{{ $fragmentId }}"
                        x-data="{ bookmarked: false }"
                        x-init="window.checkBookmarkStatus && window.checkBookmarkStatus({{ $fragmentId }}, $el)"
                        x-cloak
                    >
                        <x-heroicon-o-bookmark class="w-3.5 h-3.5 pointer-events-none"/>
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Score Display (moved to bottom right as requested) --}}
    @if($showScore && $score > 0)
        <div class="absolute bottom-2 right-3 text-xs text-electric-blue/70 bg-electric-blue/10 px-2 py-0.5 rounded">
            Score: {{ number_format($score, 3) }}
        </div>
    @endif
</div>
