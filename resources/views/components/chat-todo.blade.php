@props(['checked' => false, 'label', 'fragmentId'])

<label
    class="flex items-center space-x-2 cursor-pointer group hover:bg-zinc-100 dark:hover:bg-zinc-800 p-2 rounded-md transition"
>
    <input
        type="checkbox"
        x-model="checked"
        wire:click="toggleTodoCompletion({{ $fragmentId }})"
        class="accent-green-600 rounded focus:ring focus:ring-green-500"
    />
    <span :class="checked ? 'line-through opacity-60' : ''">
        aaaa {{ $label }}
    </span>
</label>
