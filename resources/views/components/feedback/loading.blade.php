@props([
    'size' => 'md',
    'text' => null,
    'overlay' => false,
])

@php
    $sizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
        'xl' => 'w-12 h-12',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if($overlay)
    <div {{ $attributes->merge(['class' => 'fixed inset-0 z-50 flex items-center justify-center']) }} style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="bg-white dark:bg-[#181818] rounded-xl p-6 flex flex-col items-center gap-3 shadow-xl">
            <svg class="{{ $sizeClass }} animate-spin text-black dark:text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            @if($text)
                <span class="text-sm font-medium text-black dark:text-white">{{ $text }}</span>
            @endif
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-2']) }}>
        <svg class="{{ $sizeClass }} animate-spin text-black dark:text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if($text)
            <span class="text-sm font-medium text-black dark:text-white">{{ $text }}</span>
        @endif
    </div>
@endif
