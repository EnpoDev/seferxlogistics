@props([
    'align' => 'left',
    'sortable' => false,
    'sortKey' => null,
    'sortDirection' => null,
    'width' => null,
])

@php
    $alignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];

    // shadcn pattern
    $classes = 'h-12 px-4 align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0 ' . ($alignClasses[$align] ?? 'text-left');

    if ($sortable) {
        $classes .= ' cursor-pointer hover:text-foreground transition-colors select-none';
    }

    $style = $width ? "width: {$width};" : '';
@endphp

<th {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
    @if($sortable)
        <div class="flex items-center gap-1 {{ $align === 'right' ? 'justify-end' : ($align === 'center' ? 'justify-center' : '') }}">
            <span>{{ $slot }}</span>
            <div class="flex flex-col">
                <svg class="w-3 h-3 {{ $sortDirection === 'asc' && $sortKey ? 'text-foreground' : 'text-muted-foreground/40' }}" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 14l5-5 5 5H7z"/>
                </svg>
                <svg class="w-3 h-3 -mt-1 {{ $sortDirection === 'desc' && $sortKey ? 'text-foreground' : 'text-muted-foreground/40' }}" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 10l5 5 5-5H7z"/>
                </svg>
            </div>
        </div>
    @else
        {{ $slot }}
    @endif
</th>
