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

    // Handle model info
    $modelProvider = is_array($fragment) ? ($fragment['model_provider'] ?? null) : ($fragment->model_provider ?? null);
    $modelName = is_array($fragment) ? ($fragment['model_name'] ?? null) : ($fragment->model_name ?? null);

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
    class="group transition-colors duration-200 {{ $highlight ? 'bg-electric-blue/10 border border-electric-blue/30 rounded-lg p-3' : 'border-b border-b-gray-800 border-dashed p-3' }} relative"
    @if($fragmentId) data-fragment-id="{{ $fragmentId }}" @endif
>
    <div class="flex items-start justify-between">
        <div class="flex-1 {{ $showActions ? 'mr-2' : '' }} ">
            {{-- Type Display --}}
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-medium">
                    <span class="text-white">Type:</span>
                    <span class="text-electric-blue">{{ $typeName }}</span>
                </div>
            </div>

            {{-- Model Info Display --}}
            @if (config('fragments.models.ui.show_in_fragments', true) && !empty($modelProvider))
                <div class="mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-electric-blue/10 text-electric-blue/80 border border-electric-blue/20">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                        </svg>
                        {{ ucfirst($modelProvider) }}{{ !empty($modelName) ? ' ' . $modelName : '' }}
                    </span>
                </div>
            @endif

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
                <div class="flex items-center">
                    <!-- Copy and Delete Buttons (hover only) -->
                    <div class="flex items-center space-x-2 mr-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
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
                    </div>

                    <!-- Bookmark Button (independent opacity control) -->
                    <div wire:ignore.self
                         x-data="{
                             bookmarked: false,
                             init() {
                                 console.log('Bookmark component init for fragment {{ $fragmentId }}');
                                 this.checkBookmarkStatus();
                             },
                             async checkBookmarkStatus() {
                                 try {
                                     const response = await fetch(`/api/fragments/{{ $fragmentId }}/bookmark`);
                                     if (response.ok) {
                                         const data = await response.json();
                                         console.log('Initial bookmark status for {{ $fragmentId }}:', data.is_bookmarked);
                                         this.bookmarked = data.is_bookmarked;
                                     }
                                 } catch (error) {
                                     console.log('Error checking bookmark status:', error);
                                 }
                             },
                             async toggleBookmark() {
                                 try {
                                     const response = await fetch(`/api/fragments/{{ $fragmentId }}/bookmark`, {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
                                         }
                                     });

                                     if (response.ok) {
                                         const data = await response.json();
                                         console.log('Toggle result for {{ $fragmentId }}:', data.is_bookmarked);
                                         this.bookmarked = data.is_bookmarked;

                                         // Dispatch event for bookmark widget
                                         window.dispatchEvent(new CustomEvent('bookmark-toggled', {
                                             detail: { fragmentId: {{ $fragmentId }}, action: data.action, isBookmarked: data.is_bookmarked }
                                         }));
                                     }
                                 } catch (error) {
                                     console.log('Error toggling bookmark:', error);
                                 }
                             }
                         }"
                         :class="bookmarked ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                         class="transition-opacity duration-200">
                        <button
                            @click="event.stopPropagation(); toggleBookmark(); console.log('Bookmark clicked, state:', bookmarked)"
                            class="p-1.5 bg-gray-700 hover:bg-hot-pink/20 rounded border border-gray-600 hover:border-hot-pink/40 hover:shadow-sm hover:shadow-hot-pink/20 transition-all"
                            :class="bookmarked ? 'text-hot-pink border-hot-pink/40' : 'text-gray-400 hover:text-hot-pink'"
                            title="Toggle bookmark"
                            data-fragment-id="{{ $fragmentId }}"
                            x-ref="button"
                        >
                            <x-heroicon-o-bookmark class="w-3.5 h-3.5 pointer-events-none"/>
                        </button>
                    </div>
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
