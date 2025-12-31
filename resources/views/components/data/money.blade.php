@props([
    'amount' => null,
    'currency' => 'TL',
    'showSign' => false,
    'color' => null,
    'decimals' => 2,
])

@php
    $amount = $amount ?? 0;
    $formattedAmount = number_format((float)$amount, $decimals, ',', '.');

    // Renk belirleme
    if ($color) {
        $colorClass = match($color) {
            'success', 'green' => 'text-green-600 dark:text-green-400',
            'danger', 'red' => 'text-red-600 dark:text-red-400',
            'warning', 'yellow' => 'text-yellow-600 dark:text-yellow-400',
            'info', 'blue' => 'text-blue-600 dark:text-blue-400',
            default => 'text-black dark:text-white',
        };
    } elseif ($showSign && $amount != 0) {
        $colorClass = $amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    } else {
        $colorClass = 'text-black dark:text-white';
    }

    $sign = '';
    if ($showSign && $amount > 0) {
        $sign = '+';
    }
@endphp

<span {{ $attributes->merge(['class' => $colorClass]) }}>
    {{ $sign }}{{ $formattedAmount }} {{ $currency }}
</span>
