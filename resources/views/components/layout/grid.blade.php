@props([
    'cols' => 1,
    'mdCols' => null,
    'lgCols' => null,
    'gap' => 6,
])

@php
    $colsMap = [
        1 => 'grid-cols-1',
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        4 => 'grid-cols-4',
        5 => 'grid-cols-5',
        6 => 'grid-cols-6',
    ];

    $mdColsMap = [
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        5 => 'md:grid-cols-5',
        6 => 'md:grid-cols-6',
    ];

    $lgColsMap = [
        1 => 'lg:grid-cols-1',
        2 => 'lg:grid-cols-2',
        3 => 'lg:grid-cols-3',
        4 => 'lg:grid-cols-4',
        5 => 'lg:grid-cols-5',
        6 => 'lg:grid-cols-6',
    ];

    $gapMap = [
        0 => 'gap-0',
        1 => 'gap-1',
        2 => 'gap-2',
        3 => 'gap-3',
        4 => 'gap-4',
        5 => 'gap-5',
        6 => 'gap-6',
        8 => 'gap-8',
    ];

    $classes = 'grid ' . ($colsMap[$cols] ?? 'grid-cols-1');

    if ($mdCols) {
        $classes .= ' ' . ($mdColsMap[$mdCols] ?? '');
    }

    if ($lgCols) {
        $classes .= ' ' . ($lgColsMap[$lgCols] ?? '');
    }

    $classes .= ' ' . ($gapMap[$gap] ?? 'gap-6');
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
