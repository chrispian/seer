<div class="flex items-center gap-3 py-1 transition-all duration-300 ease-in-out hover:bg-gray-800 hover:bg-opacity-30 rounded px-2 -mx-2">
    <input
        type="checkbox"
        wire:click="toggle"
        @checked(($fragment->state['status'] ?? 'open') === 'complete')
        class="h-4 w-4 text-green-500 rounded border-gray-300 focus:ring-green-500 transition-all duration-200"
    />
    <span class="transition-all duration-300 ease-in-out {{ ($fragment->state['status'] ?? 'open') === 'complete' ? 'line-through text-gray-400' : 'text-gray-900' }}">
        {{ $fragment->message }}
    </span>
    @if (($fragment->state['status'] ?? 'open') === 'complete' && isset($fragment->state['completed_at']))
        <span class="text-xs text-gray-600 ml-auto opacity-60 transition-opacity duration-300">
            {{ \Carbon\Carbon::parse($fragment->state['completed_at'])->diffForHumans() }}
        </span>
    @endif
</div>
