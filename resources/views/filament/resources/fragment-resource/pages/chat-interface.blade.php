<x-filament-panels::page class="max-w-3xl mx-auto rounded border mt-10 bg-white">

    <div class="h-screen w-full bg-zinc-950 text-black flex flex-col pt-[10px]">

        @if ($currentSession)
            <div class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 p-2 rounded mb-4 flex items-center justify-between">
                <div>
                    <strong>Session:</strong> {{ $currentSession['identifier'] ?? 'Unnamed Session' }}
                    <span class="text-sm text-gray-500 ml-2">(vault: {{ $currentSession['vault'] }}, type: {{ $currentSession['type'] }})</span>
                </div>
                <button wire:click="showSession" class="text-sm underline hover:text-green-600">Details</button>
            </div>
        @endif
            <div
                id="drift-avatar"
                x-data="{ avatar: '/interface/avatars/default/default.png' }"
                x-init="$watch('avatar', value => {
    $el.querySelector('img').src = value;
  })"
                class="top-[calc(100%-150px)] right-6 z-50 float-right bg-black w-20 rounded-full shadow-lg p-1 border-4 border-blue-400 animate-pulse transition-all"
            >
                <img :src="avatar" alt="Drift Avatar" class="float-right bg-black  rounded-full w-15 h-15 object-cover transition duration-500">
            </div>
        {{-- Chat Output --}}
            <div id="chat-output" class="flex-1 overflow-y-auto">
            <div class="space-y-2 divide-y divide-zinc-800">
                {{-- Normal Chat --}}
                <div class="mt-6">
                    @foreach ($chatMessages as $entry)
                        <x-chat-markdown>
                            {{ $entry['message'] }}
                        </x-chat-markdown>
                    @endforeach

                    @if (!empty($recalledTodos))
                        {{-- Recalled Todos --}}
                        @php
                            $todoFragments = $this->getTodoFragments();
                        @endphp

                        <h2 class="text-lg font-semibold text-blue-400 mb-3 mt-4">📋 Todos ({{ $todoFragments->count() }})</h2>
                        <ul class="list-none space-y-2">
                            @foreach ($todoFragments as $fragment)
                                <li wire:key="todo-{{ $fragment->id }}">
                                    <livewire:todo-item :fragment="$fragment" :key="'todo-'.$fragment->id" />
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </div>



            </div>
        </div>


        {{-- Input Bar --}}
        <form id="chat-form" x-data wire:submit.prevent="handleInput" class="p-1">


        <textarea
            x-data
            wire:model.defer="input"
            wire:keydown.enter.prevent="
                $el.value = '';
                $nextTick(() => {
                    document.getElementById('chat-form')?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                });
            "
            class="
                w-full
                p-2
                rounded
                bg-zinc-800
                text-back border
                border-zinc-700
                resize-none
                "
            rows="3"
        >
        </textarea>

            @if (!empty($commandHistory))
                <div class="mt-4 bg-zinc-900 p-3 rounded shadow-inner">
                    <div class="flex flex-wrap gap-2">
                        @foreach (array_reverse(array_slice($commandHistory, -4)) as $cmd)
                            <button
                                wire:click="injectCommand('{{ addslashes($cmd) }}')"
                                class="text-xs bg-zinc-800 hover:bg-zinc-700 text-gray-300 rounded px-2 py-1"
                            >
                                {{ $cmd }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-4">
                <button
                    x-data
                    x-on:click="$dispatch('open-command-palette')"
                    class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
                >
                    ➕ Commands
                </button>
            </div>



        </form>


    </div>


    <div
        x-data="{ open: false }"
        x-on:open-command-palette.window="open = true"
        x-show="open"
        style="display: none;"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    >
        <div class="bg-white dark:bg-zinc-900 rounded-lg p-6 w-96 shadow-xl">
            <h3 class="text-lg font-semibold mb-4 text-center">⚡ Command Palette</h3>
            <div class="space-y-2">
                @foreach (\App\Services\CommandRegistry::all() as $cmd)
                    <button
                        wire:click="injectCommand('/{{ $cmd }} ')"
                        x-on:click="open = false"
                        class="w-full text-left bg-zinc-800 hover:bg-zinc-700 text-gray-200 rounded px-3 py-2 text-sm"
                    >
                        /{{ $cmd }}
                    </button>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <button
                    x-on:click="open = false"
                    class="text-xs text-gray-400 hover:text-gray-200"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

{{--    <!-- Add Pusher -->--}}
{{--    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>--}}

{{--    <!-- Add Laravel Echo -->--}}
{{--    <script src="https://cdn.jsdelivr.net/npm/laravel-echo/dist/echo.iife.js"></script>--}}

    <script>
        // console.log('Initializing Echo...');
        //
        // window.Pusher = Pusher;
        //
        // const isHttps = window.location.protocol === 'http:';
        //
        // window.Echo = new Echo({
        //     broadcaster: 'pusher',
        //     key: '1cdf7afdbf274ab9e94bf2cae69839fb',
        //     wsHost: 'seer.test',
        //     wsPort: 6001,
        //     wssPort: 6001,
        //     forceTLS: true,
        //     encrypted: true,
        //     disableStats: true,
        //     enabledTransports: ['ws'], // ONLY wss
        // });
        //
        // console.log('Subscribing to lens channel...');
        //
        // window.Echo.channel('lens.chat')
        //     .listen('.fragment-processed', (e) => {
        //         console.log('Fragment Processed Event!', e);
        //     })
        //     .listen('.drift-avatar-change', (event) => {
        //         console.log('DriftSync Avatar Update!', event);
        //         document.querySelector('#drift-avatar').__x.$data.avatar = event.avatarPath;
        //     });

    </script>

{{--    <script>--}}
{{--        window.addEventListener('drift-avatar-change', event => {--}}
{{--            document.querySelector('#drift-avatar').__x.$data.avatar = event.detail.avatar;--}}
{{--        });--}}
{{--    </script>--}}



</x-filament-panels::page>


