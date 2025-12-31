@props([
    'tier',
])

@php
    $tiers = [
        'bronze' => [
            'label' => 'Bronz',
            'color' => '#CD7F32',
            'bg' => 'bg-orange-100 dark:bg-orange-900/30',
            'text' => 'text-orange-800 dark:text-orange-400',
        ],
        'silver' => [
            'label' => 'Gumus',
            'color' => '#C0C0C0',
            'bg' => 'bg-gray-200 dark:bg-gray-700',
            'text' => 'text-gray-700 dark:text-gray-300',
        ],
        'gold' => [
            'label' => 'Altin',
            'color' => '#FFD700',
            'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'text' => 'text-yellow-700 dark:text-yellow-400',
        ],
        'platinum' => [
            'label' => 'Platin',
            'color' => '#E5E4E2',
            'bg' => 'bg-slate-200 dark:bg-slate-700',
            'text' => 'text-slate-700 dark:text-slate-300',
        ],
    ];

    $tierInfo = $tiers[$tier] ?? $tiers['bronze'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {$tierInfo['bg']} {$tierInfo['text']}"]) }}>
    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="{{ $tierInfo['color'] }}">
        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
    </svg>
    {{ $tierInfo['label'] }}
</span>
