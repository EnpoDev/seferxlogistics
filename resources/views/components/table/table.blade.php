@props([
    'hoverable' => true,
    'striped' => false,
    'bordered' => false,
    'compact' => false,
])

@php
    $classes = 'w-full caption-bottom text-sm';

    if ($compact) {
        $classes .= ' text-xs';
    }
@endphp

<div {{ $attributes->merge(['class' => 'relative w-full overflow-auto bg-card rounded-[--radius-lg] border border-border']) }}>
    <table class="{{ $classes }}" data-hoverable="{{ $hoverable ? 'true' : 'false' }}" data-striped="{{ $striped ? 'true' : 'false' }}" data-bordered="{{ $bordered ? 'true' : 'false' }}">
        {{ $slot }}
    </table>
</div>
