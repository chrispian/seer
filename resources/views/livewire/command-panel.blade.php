<!-- Command Panel Slide-Over -->
<div
    x-show="$wire.isVisible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-x-full"
    x-transition:enter-end="opacity-100 transform translate-x-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 transform translate-x-0"
    x-transition:leave-end="opacity-0 transform translate-x-full"
    class="fixed inset-y-0 right-0 z-60 w-full max-w-2xl bg-surface border-l border-hot-pink/20 shadow-2xl backdrop-blur-sm"
    style="width: calc(100% - 280px);" {{-- Accounts for sidebar width --}}
    x-cloak
    @keydown.escape="$wire.hide()"
    @click.outside="$wire.hide()"
>
    <!-- Panel Header -->
    <div class="h-14 bg-gray-900/50 border-b border-thin border-hot-pink/20 flex items-center justify-between px-6 sticky top-0 z-10 backdrop-blur-sm">
        <div class="flex items-center space-x-2">
            <x-heroicon-o-command-line class="w-5 h-5 text-hot-pink"/>
            <h2 class="text-lg font-semibold text-gray-200">
                {{ ucfirst($type) }} Command
            </h2>
        </div>
        <button
            wire:click="hide"
            class="w-8 h-8 bg-gray-700 hover:bg-gray-600 rounded-pixel flex items-center justify-center transition-colors"
        >
            <x-heroicon-o-x-mark class="w-4 h-4 text-gray-300"/>
        </button>
    </div>

    <!-- Panel Content -->
    <div class="flex-1 p-6 overflow-y-auto">
        @if($type === 'recall')
            @include('livewire.panels.recall-panel')
        @elseif($type === 'help')
            @include('livewire.panels.help-panel')
        @elseif($type === 'clear')
            @include('livewire.panels.clear-panel')
        @else
            @include('livewire.panels.generic-panel')
        @endif
    </div>
</div>
