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


        {{-- Chat Output --}}
        <div id="chat-output" class="flex-1 overflow-y-auto">
            <div class="space-y-2 divide-y divide-zinc-800">

                {{-- Recalled Todos --}}
                <h1 class="text-red-500 text-sm">Recalled Todos Count: {{ count($recalledTodos) }}</h1>

                {{-- Normal Chat --}}
                <div class="mt-6">
                    @if (!empty($chatHistory))
                        @foreach ($chatHistory as $entry)
                            <x-chat-markdown>
                                {{ $entry['message'] }}
                            </x-chat-markdown>
                        @endforeach
                    @endif

                        @if (!empty($recalledTodos))
                            <h2 class="text-lg font-semibold text-gray-300 mb-2">Todos:</h2>
                            <ul class="list-none space-y-1">
                                @foreach ($recalledTodos as $entry)
                                    <livewire:todo-item :fragment="\App\Models\Fragment::find($entry['id'])" :key="$entry['id']" />
                                @endforeach
                            </ul>
                        @endif


                </div>



            </div>
        </div>


        {{-- Input Bar --}}
        <form class="p-1">
        <textarea
            wire:model="input"
            wire:keydown.enter.prevent="handleInput"
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
            placeholder="Type your fragment and press Enter..."
            autofocus
        ></textarea>
        </form>


    </div>
</x-filament-panels::page>


