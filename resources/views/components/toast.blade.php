@props(['variant' => 'info', 'title' => '', 'message' => '', 'actions' => [], 'toastId' => 'toast'])

<div
    x-data="{
        show: false,
        variant: '{{ $variant }}',
        title: '{{ $title }}',
        message: '{{ $message }}',
        actions: @js($actions),
        timeout: null,

        display(variant = 'info', title = '', message = '', actions = [], duration = 5) {
            this.variant = variant;
            this.title = title;
            this.message = message;
            this.actions = actions || [];
            this.show = true;

            if (this.timeout) {
                clearTimeout(this.timeout);
            }

            if (duration > 0) {
                this.timeout = setTimeout(() => {
                    this.hide();
                }, duration * 1000);
            }
        },

        hide() {
            this.show = false;
            if (this.timeout) {
                clearTimeout(this.timeout);
                this.timeout = null;
            }
        },

        getVariantClasses() {
            const variants = {
                'success': 'bg-emerald-900/90 border-emerald-500/50 text-emerald-100',
                'error': 'bg-rose-900/90 border-rose-500/50 text-rose-100',
                'warning': 'bg-amber-900/90 border-amber-500/50 text-amber-100',
                'info': 'bg-blue-900/90 border-blue-500/50 text-blue-100'
            };
            return variants[this.variant] || variants.info;
        },

        getIconClasses() {
            const icons = {
                'success': 'text-emerald-400',
                'error': 'text-rose-400',
                'warning': 'text-amber-400',
                'info': 'text-blue-400'
            };
            return icons[this.variant] || icons.info;
        },

        getIcon() {
            const icons = {
                'success': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'error': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'warning': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
                'info': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
            };
            return icons[this.variant] || icons.info;
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200 transform"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    x-cloak
    id="{{ $toastId }}"
    class="fixed bottom-4 right-4 z-50 max-w-sm w-full"
>
    <div
        :class="getVariantClasses()"
        class="rounded-pixel border-2 backdrop-filter backdrop-blur-sm shadow-lg p-4 relative overflow-hidden"
    >
        <!-- Glow effect -->
        <div class="absolute inset-0 opacity-20 blur-sm" :class="getVariantClasses()"></div>

        <div class="relative z-10 flex items-start space-x-3">
            <!-- Icon -->
            <div class="flex-shrink-0">
                <svg
                    :class="getIconClasses()"
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        :d="getIcon()"
                    ></path>
                </svg>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <p x-show="title" x-text="title" class="text-sm font-semibold"></p>
                <p x-text="message" class="text-sm" :class="title ? 'mt-1' : ''"></p>

                <!-- Actions -->
                <template x-if="actions && actions.length > 0">
                    <div class="mt-3 flex space-x-2">
                        <template x-for="(action, index) in actions" :key="index">
                            <button
                                @click="action.callback && action.callback(); hide();"
                                :class="action.primary ? 'bg-white/20 hover:bg-white/30' : 'hover:bg-white/10'"
                                class="text-xs px-3 py-1 rounded-pixel border border-current/30 transition-colors"
                                x-text="action.label"
                            ></button>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Close button -->
            <button
                @click="hide()"
                class="flex-shrink-0 rounded-pixel p-1 hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-white/20"
                aria-label="Close notification"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Keyboard support -->
        <div x-data @keydown.escape.window="hide()"></div>
    </div>
</div>