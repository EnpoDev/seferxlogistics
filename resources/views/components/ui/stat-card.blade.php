@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'blue',
    'trend' => null,
    'trendValue' => null,
])

@php
    // Renk stilleri
    $colors = [
        'blue' => [
            'bg' => 'bg-blue-500/10',
            'text' => 'text-blue-600 dark:text-blue-400',
            'icon' => 'text-blue-500',
        ],
        'green' => [
            'bg' => 'bg-green-500/10',
            'text' => 'text-green-600 dark:text-green-400',
            'icon' => 'text-green-500',
        ],
        'orange' => [
            'bg' => 'bg-orange-500/10',
            'text' => 'text-orange-600 dark:text-orange-400',
            'icon' => 'text-orange-500',
        ],
        'purple' => [
            'bg' => 'bg-purple-500/10',
            'text' => 'text-purple-600 dark:text-purple-400',
            'icon' => 'text-purple-500',
        ],
        'red' => [
            'bg' => 'bg-red-500/10',
            'text' => 'text-red-600 dark:text-red-400',
            'icon' => 'text-red-500',
        ],
        'gray' => [
            'bg' => 'bg-gray-500/10',
            'text' => 'text-gray-600 dark:text-gray-400',
            'icon' => 'text-gray-500',
        ],
    ];

    $colorSet = $colors[$color] ?? $colors['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-black dark:text-white">{{ $value }}</p>

            @if($subtitle)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">{{ $subtitle }}</p>
            @endif

            @if($trend && $trendValue)
                <div class="mt-2 flex items-center gap-1">
                    @if($trend === 'up')
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $trendValue }}</span>
                    @else
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $trendValue }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if($icon)
            <div class="w-12 h-12 rounded-lg {{ $colorSet['bg'] }} flex items-center justify-center">
                <x-ui.icon :name="$icon" class="w-6 h-6 {{ $colorSet['icon'] }}" />
            </div>
        @endif
    </div>

    @isset($footer)
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
            {{ $footer }}
        </div>
    @endisset
</div>
