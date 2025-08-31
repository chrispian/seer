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
                <div class="space-y-3">
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

        @else
            {{-- Generic data display --}}
            <pre class="text-xs bg-gray-800 p-3 rounded overflow-x-auto">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
        @endif
    @endif
</div>