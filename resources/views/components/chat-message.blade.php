@props(['message', 'type' => 'user', 'timestamp' => null])

<div class="flex items-start space-x-3">
    <div class="w-8 h-8 {{ $type === 'user' ? 'bg-hot-pink' : ($type === 'system' ? 'bg-neon-cyan' : 'bg-electric-blue') }} rounded-full flex items-center justify-center text-white text-sm font-medium pixel-card {{ $type === 'user' ? 'glow-pink' : ($type === 'system' ? 'glow-cyan' : 'glow-blue') }}">
        @if($type === 'user')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        @elseif($type === 'system')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        @endif
    </div>
    <div class="flex-1">
        <div class="bg-surface-card rounded-pixel p-4 pixel-card border-thin 
            {{ $type === 'user' ? 'border-hot-pink/30 pixel-card-pink glow-pink' : 
               ($type === 'system' ? 'border-neon-cyan/30 pixel-card-cyan glow-cyan' : 
                'border-electric-blue/30 pixel-card-blue glow-blue') }}">
            <div class="prose prose-sm dark:prose-invert max-w-none text-text-primary">
                {{ $slot }}
            </div>
        </div>
        <div class="text-xs text-text-muted mt-1">
            {{ $timestamp ?? 'Just now' }}
        </div>
    </div>
</div>