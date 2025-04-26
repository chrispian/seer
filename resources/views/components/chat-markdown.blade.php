@props(['fragment' => null])
@php
    $html = Str::of($slot)
        ->markdown()
        ->replaceMatches('/\[( |x)\] (.+)/i', function ($match) use ($fragment) {
            $checked = trim($match[1]) === 'x';
            $label = trim($match[2]);

            return view('components.chat-todo', [
                'checked' => $checked,
                'label' => $label,
                'fragmentId' => $fragment?->id ?? 0,
            ])->render();
        });
@endphp


<div class="prose dark:prose-invert max-w-none">
    {!! $html !!}
</div>
