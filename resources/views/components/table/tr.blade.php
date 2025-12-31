@props([
    'hoverable' => true,
    'clickable' => false,
    'href' => null,
])

@php
    // shadcn pattern
    $classes = 'border-b border-border transition-colors data-[state=selected]:bg-muted';

    if ($hoverable) {
        $classes .= ' hover:bg-muted/50';
    }

    if ($clickable || $href) {
        $classes .= ' cursor-pointer';
    }
@endphp

@if($href)
    <tr
        {{ $attributes->merge(['class' => $classes]) }}
        onclick="window.location='{{ $href }}'"
    >
        {{ $slot }}
    </tr>
@else
    <tr {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </tr>
@endif
