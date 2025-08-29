{{-- Tab-style undo toast that slides up from below chat window --}}
<div 
    id="undo-toast"
    x-data="{
        show: false,
        timeLeft: 60,
        message: '',
        fragmentId: null,
        objectType: 'fragment',
        countdownInterval: null,
        
        display(fragmentId, message, objectType = 'fragment') {
            console.log('Undo toast display called with:', { fragmentId, message, objectType });
            this.fragmentId = fragmentId;
            this.message = message;
            this.objectType = objectType;
            this.timeLeft = 60;
            this.show = true;
            
            // Start countdown
            this.countdownInterval = setInterval(() => {
                this.timeLeft--;
                
                if (this.timeLeft <= 0) {
                    this.hide();
                }
            }, 1000);
        },
        
        getTitle() {
            return this.objectType === 'chat' ? 'Chat deleted' : 'Fragment deleted';
        },
        
        getIcon() {
            return this.objectType === 'chat' ? '💬' : '🗑️';
        },
        
        hide() {
            this.show = false;
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
                this.countdownInterval = null;
            }
        },
        
    }" 
    x-show="show" 
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200 transform"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="bg-surface-2 border-t border-thin border-hot-pink/30 p-3 z-50 w-[90%] mx-auto"
    x-cloak>
    
    <div class="flex items-center justify-center">
        <div class="flex items-center justify-between w-full max-w-2xl">
            <div class="flex items-center space-x-3">
                <div class="w-6 h-6 bg-hot-pink rounded-pixel flex items-center justify-center">
                    <span class="text-white text-sm" x-text="getIcon()"></span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-text-primary" x-text="getTitle()"></p>
                    <p class="text-xs text-text-muted truncate" x-text="message" style="max-width: 300px;"></p>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="text-xs text-text-muted">
                    <span x-text="timeLeft"></span>s left
                </div>
                
                <button 
                        @click="
                            console.log('Undo clicked for fragment:', fragmentId);
                            if (fragmentId) {
                                Livewire.dispatch('undo-fragment', { fragmentId: fragmentId });
                                hide();
                            } else {
                                console.error('No fragmentId available for undo');
                            }
                        "
                        class="text-sm bg-hot-pink/20 hover:bg-hot-pink/30 text-hot-pink px-3 py-1 rounded-pixel border border-hot-pink/40 transition-colors glow-pink font-medium">
                    Undo
                </button>
                
                <button @click="hide()" 
                        class="text-text-muted hover:text-text-primary transition-colors text-lg leading-none">
                    ×
                </button>
            </div>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="mt-2">
        <div class="h-1 bg-surface rounded-pixel overflow-hidden">
            <div class="h-full bg-hot-pink transition-all duration-1000 ease-linear rounded-pixel" 
                 :style="{ width: (timeLeft / 60 * 100) + '%' }"></div>
        </div>
    </div>
</div>