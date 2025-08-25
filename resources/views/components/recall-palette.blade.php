{{-- Recall Palette Component - Ctrl+K Search Interface --}}
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
                        @input="console.log('Input changed:', $event.target.value); $wire.set('recallQuery', $event.target.value); if ($event.target.value.length >= 2) $wire.performRecallSearch()"
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
                
                {{-- Debug Info --}}
                <div class="px-4 py-2 text-xs text-red-500" data-debug-info>
                    DEBUG: Results count: {{ count($recallResults ?? []) }} | Query: {{ $recallQuery ?? 'empty' }}
                </div>
                
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
                                            {{ $result['preview'] }}
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
                                            
                                            <div class="text-xs text-gray-400">
                                                {{ $result['created_at'] }}
                                            </div>
                                            
                                            @if(isset($result['search_score']) && $result['search_score'] > 0)
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

                {{-- Suggestions --}}
                @if(strlen($recallQuery ?? '') < 2 && count($recallSuggestions ?? []) > 0)
                    <div class="py-2 border-t border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            Quick Filters
                        </div>
                        @foreach($recallSuggestions as $suggestion)
                            <div 
                                class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                wire:click="applySuggestion({{ json_encode($suggestion) }})"
                            >
                                <div class="flex items-center space-x-3">
                                    <code class="text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">
                                        {{ $suggestion['text'] }}
                                    </code>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $suggestion['description'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Autocomplete --}}
                @if(count($recallAutocomplete ?? []) > 0)
                    <div class="py-2 border-t border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            Available Filters
                        </div>
                        <div class="px-4 py-2">
                            @php
                                $categories = collect($recallAutocomplete)->groupBy('category');
                            @endphp
                            
                            @foreach($categories as $category => $items)
                                <div class="mb-3">
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $category }}</div>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($items as $item)
                                            <button 
                                                class="text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 px-2 py-1 rounded text-gray-700 dark:text-gray-300"
                                                wire:click="applyAutocomplete({{ json_encode($item) }})"
                                            >
                                                {{ $item['display'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
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

<script>
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
            
            // Listen for results updated event
            this.$wire.on('recall-results-updated', (event) => {
                console.log('Recall results updated:', event);
                // Force update the debug info manually
                const debugElement = document.querySelector('[data-debug-info]');
                if (debugElement) {
                    debugElement.textContent = `DEBUG: Results count: ${event.count} | Query: ${event.query}`;
                }
                // Force Livewire to refresh
                this.$wire.$refresh();
            });
        }
    }));
});
</script>