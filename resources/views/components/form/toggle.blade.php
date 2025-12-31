@props([
    'name',
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
    'size' => 'md',
    'error' => null,
])

@php
    $isChecked = old($name, $checked);

    // Boyut stilleri - shadcn pattern
    $sizes = [
        'sm' => [
            'track' => 'h-4 w-8',
            'thumb' => 'h-3 w-3',
            'translate' => 'translate-x-4',
        ],
        'md' => [
            'track' => 'h-6 w-11',
            'thumb' => 'h-5 w-5',
            'translate' => 'translate-x-5',
        ],
        'lg' => [
            'track' => 'h-7 w-14',
            'thumb' => 'h-6 w-6',
            'translate' => 'translate-x-7',
        ],
    ];

    $sizeSet = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->only('class') }}>
    <label class="flex items-start gap-3 cursor-pointer {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}">
        <div class="relative flex items-center mt-0.5">
            <input
                type="checkbox"
                name="{{ $name }}"
                id="{{ $name }}"
                @checked($isChecked)
                @disabled($disabled)
                class="sr-only peer"
                {{ $attributes->except('class') }}
            >
            <div class="{{ $sizeSet['track'] }} shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 dark:bg-gray-700 transition-colors peer-checked:bg-gray-900 dark:peer-checked:bg-white peer-focus-visible:ring-2 peer-focus-visible:ring-gray-400 peer-focus-visible:ring-offset-2 peer-disabled:cursor-not-allowed peer-disabled:opacity-50"></div>
            <div class="pointer-events-none absolute left-0.5 top-0.5 {{ $sizeSet['thumb'] }} rounded-full bg-white dark:bg-gray-900 shadow-lg ring-0 transition-transform peer-checked:{{ $sizeSet['translate'] }}"></div>
        </div>

        <div class="flex-1">
            @if($label)
                <span class="text-sm font-medium leading-none text-gray-900 dark:text-white">{{ $label }}</span>
            @endif

            @if($description)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
            @endif
        </div>
    </label>

    @if($error)
        <p class="text-xs text-red-500 mt-1">{{ $error }}</p>
    @endif
</div>
