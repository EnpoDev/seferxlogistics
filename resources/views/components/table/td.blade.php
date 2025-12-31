@props([
    'align' => 'left',
    'nowrap' => false,
])

@php
    $alignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];

    // shadcn pattern
    $classes = 'p-4 align-middle text-foreground [&:has([role=checkbox])]:pr-0 ' . ($alignClasses[$align] ?? 'text-left');

    if ($nowrap) {
        $classes .= ' whitespace-nowrap';
    }
@endphp

<td {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</td>
