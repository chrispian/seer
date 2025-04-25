<x-filament-panels::page class="max-w-3xl mx-auto rounded border mt-10 bg-white">
    <div class="h-screen w-full bg-zinc-950 text-black flex flex-col pt-[10px]">

        {{-- Chat Output --}}
        <div id="chat-output"
             class="flex-1 overflow-y-auto">
            <div class="space-y-2 divide-y divide-zinc-800">
                @foreach ($chatHistory as $message)
                    <div class="pt-2">
                        <div class="rounded px-3 py-2 bg-zinc-800 text-black">
                            <strong class="text-black">{{ ucfirst($message['type']) }}:</strong>
                            {{ $message['message'] }}
                        </div>
                    </div>
                @endforeach
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
