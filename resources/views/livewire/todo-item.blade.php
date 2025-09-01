<div class="flex items-center gap-3 py-1 px-3 transition-all duration-300 ease-in-out hover:bg-neon-cyan/10 rounded-pixel">
    <input
        type="checkbox"
        wire:click="toggle"
        @checked(($fragment->state['status'] ?? 'open') === 'complete')
        class="h-4 w-4 text-neon-cyan accent-neon-cyan rounded border-neon-cyan/30 focus:ring-neon-cyan/50 transition-all duration-200"
    />
    <span class="flex-1 transition-all duration-300 ease-in-out {{ ($fragment->state['status'] ?? 'open') === 'complete' ? 'line-through text-text-muted' : 'text-text-primary' }}">
        {{ $fragment->message }}
    </span>
    @if (($fragment->state['status'] ?? 'open') === 'complete' && isset($fragment->state['completed_at']))
        <span class="text-xs text-text-muted ml-auto opacity-60 transition-opacity duration-300">
            {{ \Carbon\Carbon::parse($fragment->state['completed_at'])->diffForHumans() }}
        </span>
    @endif
</div>
