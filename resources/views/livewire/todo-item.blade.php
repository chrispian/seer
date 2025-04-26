<li class="flex items-center gap-2 py-0">
    <input
        type="checkbox"
        wire:click="toggle"
        @checked(($fragment->state['status'] ?? 'open') === 'complete')
        class="h-4 w-4 text-green-500 rounded border-gray-300 focus:ring-green-500"
    />
    <span class="{{ ($fragment->state['status'] ?? 'open') === 'complete' ? 'line-through text-gray-500' : '' }}">
        {{ $fragment->message }}
    </span>
</li>
