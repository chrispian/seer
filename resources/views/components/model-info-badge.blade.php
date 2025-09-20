@props([
    'provider' => null,
    'model' => null,
    'size' => 'sm', // sm, xs
    'show' => true
])

@if($show && !empty($provider) && config('fragments.models.ui.show_model_info', true))
    @php
        $sizeClasses = [
            'xs' => 'text-xs px-1.5 py-0.5',
            'sm' => 'text-xs px-2 py-0.5',
        ];
        $iconSizes = [
            'xs' => 'w-2.5 h-2.5',
            'sm' => 'w-3 h-3',
        ];
    @endphp

    <span class="inline-flex items-center rounded {{ $sizeClasses[$size] ?? $sizeClasses['sm'] }} bg-electric-blue/10 text-electric-blue/80 border border-electric-blue/20">
        <svg class="{{ $iconSizes[$size] ?? $iconSizes['sm'] }} mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
        </svg>
        {{ ucfirst($provider) }}{{ !empty($model) ? ' ' . $model : '' }}
    </span>
@endif