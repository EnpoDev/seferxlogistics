@props([
    'padding' => 'md',
    'shadow' => 'sm',
    'hover' => false,
    'border' => true,
])

@php
    // Padding stilleri
    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    // Shadow stilleri
    $shadows = [
        'none' => '',
        'sm' => 'shadow-sm',
        'md' => 'shadow-md',
        'lg' => 'shadow-lg',
    ];

    // Temel class'lar - shadcn pattern
    $baseClasses = 'bg-card text-card-foreground rounded-[--radius-lg] transition-all duration-200';

    if ($border) {
        $baseClasses .= ' border border-border';
    }

    if ($hover) {
        $baseClasses .= ' hover:shadow-md hover:border-border/80';
    }

    $classes = $baseClasses . ' ' . ($shadows[$shadow] ?? $shadows['sm']);
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @isset($header)
        <div class="px-6 py-4 border-b border-border">
            {{ $header }}
        </div>
    @endisset

    <div class="{{ $paddings[$padding] ?? $paddings['md'] }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-6 py-4 border-t border-border bg-muted/50 rounded-b-[--radius-lg]">
            {{ $footer }}
        </div>
    @endisset
</div>
