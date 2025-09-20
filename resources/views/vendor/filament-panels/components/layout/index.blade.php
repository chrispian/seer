@php
    use Filament\Support\Enums\Width;

    $navigation = filament()->getNavigation();
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    {{-- The sidebar is after the page content in the markup to fix issues with page content overlapping dropdown content from the sidebar. --}}
    <div
        class="fi-layout flex min-h-screen w-full flex-row-reverse overflow-x-clip"
    >
        <div
            @if (filament()->isSidebarCollapsibleOnDesktop())
                x-data="{}"
                x-bind:class="{
                    'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                }"
                x-bind:style="'display: flex; opacity:1;'" {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @elseif (filament()->isSidebarFullyCollapsibleOnDesktop())
                x-data="{}"
                x-bind:class="{
                    'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                }"
                x-bind:style="'display: flex; opacity:1;'" {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @elseif (! (filament()->isSidebarCollapsibleOnDesktop() || filament()->isSidebarFullyCollapsibleOnDesktop() || filament()->hasTopNavigation() || (! filament()->hasNavigation())))
                x-data="{}"
                x-bind:style="'display: flex; opacity:1;'" {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @endif
            @class([
                'fi-main-ctn w-screen flex-1 flex-col',
                'h-full opacity-0 transition-all' => filament()->isSidebarCollapsibleOnDesktop() || filament()->isSidebarFullyCollapsibleOnDesktop(),
                'opacity-0' => ! (filament()->isSidebarCollapsibleOnDesktop() || filament()->isSidebarFullyCollapsibleOnDesktop() || filament()->hasTopNavigation() || (! filament()->hasNavigation())),
                'flex' => filament()->hasTopNavigation() || (! filament()->hasNavigation()),
            ])
        >
            @if (filament()->hasTopbar())
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

                <x-filament-panels::topbar :navigation="$navigation" />

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_AFTER, scopes: $livewire->getRenderHookScopes()) }}
            @endif

            <main
                @class([
                    'fi-main mx-auto h-full w-full px-4 md:px-6 lg:px-8',
                    match ($maxContentWidth ??= (filament()->getMaxContentWidth() ?? Width::SevenExtraLarge)) {
                        Width::ExtraSmall, 'xs' => 'max-w-xs',
                        Width::Small, 'sm' => 'max-w-sm',
                        Width::Medium, 'md' => 'max-w-md',
                        Width::Large, 'lg' => 'max-w-lg',
                        Width::ExtraLarge, 'xl' => 'max-w-xl',
                        Width::TwoExtraLarge, '2xl' => 'max-w-2xl',
                        Width::ThreeExtraLarge, '3xl' => 'max-w-3xl',
                        Width::FourExtraLarge, '4xl' => 'max-w-4xl',
                        Width::FiveExtraLarge, '5xl' => 'max-w-5xl',
                        Width::SixExtraLarge, '6xl' => 'max-w-6xl',
                        Width::SevenExtraLarge, '7xl' => 'max-w-7xl',
                        Width::Full, 'full' => 'max-w-full',
                        Width::MinContent, 'min' => 'max-w-min',
                        Width::MaxContent, 'max' => 'max-w-max',
                        Width::FitContent, 'fit' => 'max-w-fit',
                        Width::Prose, 'prose' => 'max-w-prose',
                        Width::ScreenSmall, 'screen-sm' => 'max-w-screen-sm',
                        Width::ScreenMedium, 'screen-md' => 'max-w-screen-md',
                        Width::ScreenLarge, 'screen-lg' => 'max-w-screen-lg',
                        Width::ScreenExtraLarge, 'screen-xl' => 'max-w-screen-xl',
                        Width::ScreenTwoExtraLarge, 'screen-2xl' => 'max-w-screen-2xl',
                        default => $maxContentWidth,
                    },
                ])
            >
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, scopes: $livewire->getRenderHookScopes()) }}

                {{ $slot }}

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_END, scopes: $livewire->getRenderHookScopes()) }}
            </main>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire->getRenderHookScopes()) }}
        </div>

        @if (filament()->hasNavigation())
            <div
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                x-transition.opacity.300ms
                class="fi-sidebar-close-overlay fixed inset-0 z-30 bg-gray-950/50 transition duration-500 dark:bg-gray-950/75 lg:hidden"
            ></div>

            <x-filament-panels::sidebar
                :navigation="$navigation"
                class="fi-main-sidebar"
            />

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => {
                        let activeSidebarItem = document.querySelector(
                            '.fi-main-sidebar .fi-sidebar-item.fi-active',
                        )

                        if (
                            !activeSidebarItem ||
                            activeSidebarItem.offsetParent === null
                        ) {
                            activeSidebarItem = document.querySelector(
                                '.fi-main-sidebar .fi-sidebar-group.fi-active',
                            )
                        }

                        if (
                            !activeSidebarItem ||
                            activeSidebarItem.offsetParent === null
                        ) {
                            return
                        }

                        const sidebarWrapper = document.querySelector(
                            '.fi-main-sidebar .fi-sidebar-nav',
                        )

                        if (!sidebarWrapper) {
                            return
                        }

                        sidebarWrapper.scrollTo(
                            0,
                            activeSidebarItem.offsetTop -
                                window.innerHeight / 2,
                        )
                    }, 10)
                })
            </script>
        @endif
    </div>
</x-filament-panels::layout.base>
