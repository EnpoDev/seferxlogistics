@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'loading' => false,
    'disabled' => false,
    'fullWidth' => false,
])

@php
    // Variant stilleri - shadcn pattern
    $variants = [
        'primary' => 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-sm',
        'secondary' => 'bg-secondary text-secondary-foreground hover:bg-secondary/80 border border-input shadow-sm',
        'destructive' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90 shadow-sm',
        'outline' => 'border border-input bg-background hover:bg-accent hover:text-accent-foreground shadow-sm',
        'ghost' => 'hover:bg-accent hover:text-accent-foreground',
        'link' => 'text-primary underline-offset-4 hover:underline',
        // Geriye uyumluluk icin eski isimler
        'danger' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90 shadow-sm',
        'success' => 'bg-success text-success-foreground hover:bg-success/90 shadow-sm',
        'warning' => 'bg-warning text-warning-foreground hover:bg-warning/90 shadow-sm',
    ];

    // Boyut stilleri
    $sizes = [
        'xs' => 'h-7 px-2 text-xs rounded-[--radius-sm]',
        'sm' => 'h-8 px-3 text-xs rounded-[--radius-sm]',
        'md' => 'h-9 px-4 text-sm rounded-[--radius-md]',
        'lg' => 'h-10 px-5 text-sm rounded-[--radius-md]',
        'xl' => 'h-11 px-6 text-base rounded-[--radius-lg]',
        'icon' => 'h-9 w-9 rounded-[--radius-md]',
    ];

    // Icon boyutlari
    $iconSizes = [
        'xs' => 'w-3 h-3',
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-4 h-4',
        'xl' => 'w-5 h-5',
        'icon' => 'w-4 h-4',
    ];

    // Temel class'lar - shadcn pattern
    $baseClasses = 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0';

    // Tum class'lari birlestir
    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);

    if ($fullWidth) {
        $classes .= ' w-full';
    }

    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
@endphp

@if($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && !$loading)
            <x-ui.icon :name="$icon" :class="$iconSize" />
        @endif

        @if($loading)
            <svg class="animate-spin {{ $iconSize }}" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif

        {{ $slot }}

        @if($iconRight && !$loading)
            <x-ui.icon :name="$iconRight" :class="$iconSize" />
        @endif
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @disabled($disabled || $loading)
    >
        @if($icon && !$loading)
            <x-ui.icon :name="$icon" :class="$iconSize" />
        @endif

        @if($loading)
            <svg class="animate-spin {{ $iconSize }}" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif

        {{ $slot }}

        @if($iconRight && !$loading)
            <x-ui.icon :name="$iconRight" :class="$iconSize" />
        @endif
    </button>
@endif
