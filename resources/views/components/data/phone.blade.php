@props([
    'number',
    'clickable' => true,
])

@php
    $formatted = \App\Helpers\PhoneFormatter::format($number);
    $cleanNumber = preg_replace('/[^0-9+]/', '', $number ?? '');
@endphp

@if($clickable && $cleanNumber)
    <a
        href="tel:{{ $cleanNumber }}"
        {{ $attributes->merge(['class' => 'text-black dark:text-white hover:underline']) }}
    >
        {{ $formatted }}
    </a>
@else
    <span {{ $attributes->merge(['class' => 'text-black dark:text-white']) }}>
        {{ $formatted }}
    </span>
@endif
