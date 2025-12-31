@props([
    'tabs' => [],
    'defaultTab' => null,
    'variant' => 'default',
    'fullWidth' => false,
])

@php
    $defaultTabKey = $defaultTab ?? (count($tabs) > 0 ? array_key_first($tabs) : null);

    // Variant stilleri
    $variants = [
        'default' => [
            'container' => 'border-b border-gray-200 dark:border-gray-800',
            'tab' => 'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
            'active' => 'border-black dark:border-white text-black dark:text-white',
            'inactive' => 'border-transparent text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-700',
        ],
        'pills' => [
            'container' => 'bg-gray-100 dark:bg-gray-900 rounded-lg p-1',
            'tab' => 'px-4 py-2 text-sm font-medium rounded-md transition-all',
            'active' => 'bg-white dark:bg-[#181818] text-black dark:text-white shadow-sm',
            'inactive' => 'text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white',
        ],
        'buttons' => [
            'container' => 'flex gap-2',
            'tab' => 'px-4 py-2 text-sm font-medium rounded-lg border transition-all',
            'active' => 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white',
            'inactive' => 'bg-white dark:bg-[#181818] text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-700 hover:border-black dark:hover:border-white hover:text-black dark:hover:text-white',
        ],
    ];

    $variantStyles = $variants[$variant] ?? $variants['default'];
@endphp

<div
    x-data="{ activeTab: '{{ $defaultTabKey }}' }"
    {{ $attributes }}
>
    <!-- Tab Navigation -->
    <div class="{{ $variantStyles['container'] }} {{ $fullWidth ? '' : 'inline-flex' }}">
        @foreach($tabs as $key => $tab)
            <button
                type="button"
                @click="activeTab = '{{ $key }}'"
                :class="activeTab === '{{ $key }}' ? '{{ $variantStyles['active'] }}' : '{{ $variantStyles['inactive'] }}'"
                class="{{ $variantStyles['tab'] }} {{ $fullWidth ? 'flex-1' : '' }} flex items-center justify-center gap-2"
            >
                @if(is_array($tab) && isset($tab['icon']))
                    <x-ui.icon :name="$tab['icon']" class="w-4 h-4" />
                @endif

                <span>{{ is_array($tab) ? $tab['label'] : $tab }}</span>

                @if(is_array($tab) && isset($tab['badge']))
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        {{ $tab['badge'] }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    <!-- Tab Panels -->
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
