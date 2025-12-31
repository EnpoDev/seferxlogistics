@props([
    'value' => 0,
    'max' => 100,
    'size' => 'md',
    'color' => 'primary',
    'showLabel' => false,
    'animated' => false,
])

@php
    $percentage = min(100, max(0, ($value / $max) * 100));

    // shadcn pattern
    $sizes = [
        'sm' => 'h-1',
        'md' => 'h-2',
        'lg' => 'h-3',
        'xl' => 'h-4',
    ];

    $colors = [
        'primary' => 'bg-primary',
        'secondary' => 'bg-secondary-foreground',
        'success' => 'bg-green-500',
        'warning' => 'bg-yellow-500',
        'danger' => 'bg-destructive',
        'info' => 'bg-blue-500',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $colorClass = $colors[$color] ?? $colors['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($showLabel)
        <div class="flex justify-between items-center mb-1">
            <span class="text-xs font-medium text-muted-foreground">{{ $slot }}</span>
            <span class="text-xs font-medium text-foreground">{{ number_format($percentage, 0) }}%</span>
        </div>
    @endif

    <div class="relative w-full overflow-hidden rounded-full bg-secondary {{ $sizeClass }}">
        <div
            class="{{ $colorClass }} {{ $sizeClass }} w-full flex-1 rounded-full transition-all duration-500 {{ $animated ? 'animate-pulse' : '' }}"
            style="width: {{ $percentage }}%"
        ></div>
    </div>
</div>
