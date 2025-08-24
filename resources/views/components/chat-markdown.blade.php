@props(['fragment' => null])
@php
    $html = Str::of($slot)
        // First, protect our custom link patterns from markdown processing
        ->replaceMatches('/@\[([^\]]+)\]\(contact:(\d+)\)/', '{{CONTACT_LINK:$1:$2}}')
        ->replaceMatches('/\[\[([^\]]+)\]\]\(fragment:(\d+)\)/', '{{FRAGMENT_LINK:$1:$2}}')
        // Process markdown
        ->markdown()
        // Restore our custom links with proper HTML
        ->replaceMatches('/{{CONTACT_LINK:([^:]+):(\d+)}}/', '<a href="#" class="contact-link" data-contact-id="$2" data-contact-name="$1">@$1</a>')
        ->replaceMatches('/{{FRAGMENT_LINK:([^:]+):(\d+)}}/', '<a href="#" class="fragment-link" data-fragment-id="$2" data-fragment-title="$1">[[$1]]</a>')
        // Handle todo checkboxes
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
