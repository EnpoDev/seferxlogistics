@props([
    'type' => 'default',
    'size' => 'md',
    'dot' => false,
    'pill' => true,
    'removable' => false,
])

@php
    // Tip stilleri - shadcn pattern
    $types = [
        'default' => 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        'primary' => 'bg-primary text-primary-foreground hover:bg-primary/80',
        'secondary' => 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        'destructive' => 'bg-destructive text-destructive-foreground hover:bg-destructive/80',
        'outline' => 'text-foreground border border-input bg-transparent',
        // Semantic colors (geriye uyumluluk)
        'success' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
        'warning' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
        'danger' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400',
        'info' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
        'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400',
        'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400',
    ];

    // Dot renkleri
    $dotColors = [
        'default' => 'bg-muted-foreground',
        'primary' => 'bg-primary-foreground',
        'secondary' => 'bg-secondary-foreground',
        'destructive' => 'bg-destructive-foreground',
        'outline' => 'bg-foreground',
        'success' => 'bg-green-500',
        'warning' => 'bg-yellow-500',
        'danger' => 'bg-red-500',
        'info' => 'bg-blue-500',
        'purple' => 'bg-purple-500',
        'orange' => 'bg-orange-500',
    ];

    // Boyut stilleri
    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[10px]',
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];

    // Temel class'lar - shadcn pattern
    $baseClasses = 'inline-flex items-center gap-1.5 font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';

    if ($pill) {
        $baseClasses .= ' rounded-full';
    } else {
        $baseClasses .= ' rounded-[--radius-sm]';
    }

    $classes = $baseClasses . ' ' . ($types[$type] ?? $types['default']) . ' ' . ($sizes[$size] ?? $sizes['md']);
    $dotColor = $dotColors[$type] ?? $dotColors['default'];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
    @endif

    {{ $slot }}

    @if($removable)
        <button type="button" class="ml-0.5 -mr-1 rounded-full opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" @click="$el.parentElement.remove()">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</span>
