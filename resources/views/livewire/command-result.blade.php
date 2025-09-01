{{-- Command Result Display --}}
<div class="border-l-4 border-electric-blue/50 bg-surface/50 p-4 rounded-r-lg">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-electric-blue capitalize">
            {{ ucfirst(str_replace('_', ' ', $type ?? 'command')) }} Result
        </h3>
        <button
            wire:click="exitCommandMode()"
            class="text-xs text-text-muted hover:text-electric-blue transition-colors"
            title="Exit command mode"
        >
            ✕ Exit
        </button>
    </div>

    {{-- Message --}}
    @if ($message)
        <div class="text-sm text-text-muted mb-3">
            {{ $message }}
        </div>
    @endif

    {{-- Data Content --}}
    @if ($data && is_array($data))
        @if ($type === 'recall')
            {{-- Recall Results --}}
            @if (isset($data['fragments']) && count($data['fragments']) > 0)
                {{-- Live search for todo results --}}
                @if (isset($data['type']) && $data['type'] === 'todo')
                    <div class="mb-4">
                        <div class="relative">
                            <input
                                type="text"
                                placeholder="Search todos..."
                                class="w-full px-3 py-2 text-sm bg-surface/50 border border-electric-blue/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-electric-blue/50 focus:border-electric-blue text-text-primary placeholder:text-text-muted"
                                x-data="{ search: '' }"
                                x-model="search"
                                x-on:input.debounce.300ms="@this.call('filterTodos', search)"
                            />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-1">
                    @foreach ($data['fragments'] as $fragment)
                        @if (($fragment['type']['value'] ?? '') === 'todo')
                            {{-- Use Livewire TodoItem for todos --}}
                            @livewire('todo-item', ['fragment' => \App\Models\Fragment::find($fragment['id'])], key('todo-'.$fragment['id']))
                        @else
                            {{-- Regular fragment display --}}
                            <div class="bg-surface border border-electric-blue/20 rounded-lg p-3">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="text-sm font-medium text-electric-blue">
                                        {{ $fragment['type']['value'] ?? 'Fragment' }}
                                    </div>
                                    <div class="text-xs text-text-muted">
                                        {{ \Carbon\Carbon::parse($fragment['created_at'])->format('M j, g:i A') }}
                                    </div>
                                </div>
                                <div class="text-sm text-text-primary">
                                    <x-chat-markdown :fragment="null">
                                        {{ $fragment['message'] }}
                                    </x-chat-markdown>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-sm text-text-muted italic">
                    No fragments found for this query.
                </div>
            @endif

        @elseif ($type === 'help')
            {{-- Help Results --}}
            @if (isset($data['message']))
                <div class="prose prose-sm max-w-none text-text-primary">
                    <x-chat-markdown :fragment="null">
                        {{ $data['message'] }}
                    </x-chat-markdown>
                </div>
            @endif

        @elseif ($type === 'bookmark')
            {{-- Bookmark Results --}}
            @if (isset($data['action']) && $data['action'] === 'show' && isset($data['fragments']) && count($data['fragments']) > 0)
                <div class="space-y-3">
                    @foreach ($data['fragments'] as $fragment)
                        <x-search-result-card
                            :fragment="$fragment"
                            :show-timestamp="true"
                            :show-score="false"
                            :highlight="true"
                        />
                    @endforeach
                </div>
            @elseif (isset($data['message']))
                <div class="text-sm text-text-muted">
                    <x-chat-markdown :fragment="null">
                        {{ $data['message'] }}
                    </x-chat-markdown>
                </div>
            @endif

        @elseif (in_array($type, ['session', 'session-start', 'session-end']))
            {{-- Session Results --}}
            @if (isset($data['message']))
                <div class="prose prose-sm max-w-none text-text-primary">
                    <x-chat-markdown :fragment="null">
                        {{ $data['message'] }}
                    </x-chat-markdown>
                </div>
            @endif

            @if (isset($data['session']) && is_array($data['session']))
                <div class="mt-4 bg-surface border border-electric-blue/20 rounded-lg p-3">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-electric-blue font-medium">Session ID:</span>
                            <span class="text-text-muted">{{ $data['session']['session_key'] ?? 'Unknown' }}</span>
                        </div>
                        <div>
                            <span class="text-electric-blue font-medium">Type:</span>
                            <span class="text-text-muted">{{ $data['session']['type'] ?? 'note' }}</span>
                        </div>
                        @if (!empty($data['session']['tags']))
                            <div class="col-span-2">
                                <span class="text-electric-blue font-medium">Tags:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ((array)$data['session']['tags'] as $tag)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-electric-blue/20 text-electric-blue">
                                            #{{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if (!empty($data['session']['context']))
                            <div class="col-span-2">
                                <span class="text-electric-blue font-medium">Context:</span>
                                <span class="text-text-muted">{{ $data['session']['context'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        @elseif ($type === 'search')
            {{-- Search Results --}}
            @if (isset($data['message']))
                <div class="prose prose-sm max-w-none text-text-primary mb-3">
                    <x-chat-markdown :fragment="null">
                        {{ $data['message'] }}
                    </x-chat-markdown>
                </div>
            @endif

            @if (isset($data['fragments']) && count($data['fragments']) > 0)
                <div class="space-y-3">
                    @foreach ($data['fragments'] as $fragment)
                        <x-search-result-card
                            :fragment="$fragment"
                            :show-timestamp="true"
                            :show-score="true"
                            :highlight="true"
                        />
                    @endforeach
                </div>
            @endif

        @else
            {{-- Generic data display --}}
            <pre class="text-xs bg-gray-800 p-3 rounded overflow-x-auto">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
        @endif
    @endif
</div>
