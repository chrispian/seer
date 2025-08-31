{{-- Success toast for fragment/chaos aside notifications --}}
<div 
    id="success-toast"
    x-data="{
        show: false,
        timeLeft: 5,
        title: '',
        message: '',
        fragmentType: 'fragment',
        fragmentId: null,
        countdownInterval: null,
        
        display(title, message, fragmentType = 'fragment', fragmentId = null, duration = 5) {
            console.log('Success toast display called with:', { title, message, fragmentType, fragmentId, duration });
            this.title = title;
            this.message = message;
            this.fragmentType = fragmentType;
            this.fragmentId = fragmentId;
            this.timeLeft = duration;
            this.show = true;
            
            // Start countdown
            this.countdownInterval = setInterval(() => {
                this.timeLeft--;
                
                if (this.timeLeft <= 0) {
                    this.hide();
                }
            }, 1000);
        },
        
        getIcon() {
            if (this.fragmentType === 'chaos') {
                return '🌪️';
            }
            return '📝';
        },
        
        getIconBg() {
            if (this.fragmentType === 'chaos') {
                return 'bg-electric-blue';
            }
            return 'bg-neon-cyan';
        },
        
        getProgressBg() {
            if (this.fragmentType === 'chaos') {
                return 'bg-electric-blue';
            }
            return 'bg-neon-cyan';
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
    class="bg-surface-2 border-t border-thin border-neon-cyan/30 p-3 z-50 w-[90%] mx-auto"
    x-cloak>
    
    <div class="flex items-center justify-center">
        <div class="flex items-center justify-between w-full max-w-2xl">
            <div class="flex items-center space-x-3">
                <div class="w-6 h-6 rounded-pixel flex items-center justify-center" :class="getIconBg()">
                    <span class="text-white text-sm" x-text="getIcon()"></span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-text-primary" x-text="title"></p>
                    <p class="text-xs text-text-muted truncate" x-text="message" style="max-width: 400px;"></p>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="text-xs text-text-muted">
                    <span x-text="timeLeft"></span>s
                </div>
                
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
            <div class="h-full transition-all duration-1000 ease-linear rounded-pixel" 
                 :class="getProgressBg()"
                 :style="{ width: (timeLeft / 5 * 100) + '%' }"></div>
        </div>
    </div>
</div>