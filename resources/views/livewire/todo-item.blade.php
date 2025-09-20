<div>

        <input
            type="checkbox"
            wire:click="toggle"
            @checked(($fragment->state['status'] ?? 'open') === 'complete')
            class="
                focus:ring-0
                focus:ring-offset-0
                rounded-sm
                h-4
                w-4
                border-gray-400
                bg-gray-700
                text-cyan-500
                focus:cyan-300
                cursor-pointer
                py-1" />
        <span class="text-xs mx-1 text-gray-700 transition-all duration-200 rounded-pixel"
        />
        <span class="flex-1 transition-all duration-300 ease-in-out {{ ($fragment->state['status'] ?? 'open') === 'complete' ? 'line-through text-text-muted' : 'text-text-primary' }}">
            {{ $fragment->message }}
        </span>
        @if (($fragment->state['status'] ?? 'open') === 'complete' && isset($fragment->state['completed_at']))
            <span class="text-xs ml-2 text-text-muted opacity-60 transition-opacity duration-300">
                {{ \Carbon\Carbon::parse($fragment->state['completed_at'])->diffForHumans() }}
            </span>
        @endif
</div>
