<!-- Clear Command Panel -->
<div class="space-y-4">
    <div class="pixel-card pixel-card-hot p-4 glow-pink">
        <h3 class="text-lg font-semibold text-hot-pink mb-3">
            <x-heroicon-o-trash class="inline w-5 h-5 mr-1"/>
            Clear Chat Confirmation
        </h3>
        
        <div class="space-y-4">
            <div class="bg-surface-elevated rounded-pixel p-4 pixel-card border-thin border-hot-pink/30">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-400"/>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-gray-200 mb-2">Are you sure you want to clear the chat?</div>
                        <div class="text-xs text-gray-400">
                            This action will remove all messages from the current chat session. This cannot be undone.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-3">
                <button
                    x-on:click="$dispatch('clear-chat-confirmed')"
                    class="flex-1 bg-hot-pink hover:bg-hot-pink/90 text-white py-2 px-4 rounded-pixel transition-colors text-sm font-medium"
                >
                    <x-heroicon-o-trash class="inline w-4 h-4 mr-1"/>
                    Yes, Clear Chat
                </button>
                <button
                    wire:click="closeCommandPanel"
                    class="flex-1 bg-gray-700 hover:bg-gray-600 text-gray-300 py-2 px-4 rounded-pixel transition-colors text-sm font-medium"
                >
                    <x-heroicon-o-x-mark class="inline w-4 h-4 mr-1"/>
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>