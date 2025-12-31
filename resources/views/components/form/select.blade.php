@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Seciniz',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
    'multiple' => false,
    'size' => 'md',
])

@php
    // Boyut stilleri - shadcn pattern
    $sizes = [
        'sm' => 'h-8 px-3 text-xs',
        'md' => 'h-10 px-3 text-sm',
        'lg' => 'h-11 px-4 text-base',
    ];

    $selectSize = $sizes[$size] ?? $sizes['md'];

    // Select class'lari
    $selectClasses = 'flex w-full items-center justify-between rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/20 focus:border-gray-400 dark:focus:border-gray-500 transition-colors pr-10';

    if ($error) {
        $selectClasses .= ' border-red-300 dark:border-red-700 focus:ring-red-500/20';
    } else {
        $selectClasses .= ' border-gray-200 dark:border-gray-700';
    }

    if ($disabled) {
        $selectClasses .= ' cursor-not-allowed opacity-50';
    }

    $selectClasses .= ' ' . $selectSize;

    // Selected value
    $selectedValue = old($name, $selected);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-2']) }}>
    @if($label)
        <label for="{{ $name }}" class="text-sm font-medium leading-none text-foreground peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {{ $label }}
            @if($required)
                <span class="text-destructive">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        @disabled($disabled)
        @required($required)
        @if($multiple) multiple @endif
        {{ $attributes->except('class')->merge(['class' => $selectClasses]) }}
    >
        @if($placeholder && !$multiple)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $key => $option)
            @if(is_array($option) && isset($option['group']))
                <optgroup label="{{ $option['group'] }}">
                    @foreach($option['options'] as $optKey => $optLabel)
                        <option
                            value="{{ $optKey }}"
                            @selected($multiple ? in_array($optKey, (array)$selectedValue) : $selectedValue == $optKey)
                        >
                            {{ $optLabel }}
                        </option>
                    @endforeach
                </optgroup>
            @else
                <option
                    value="{{ $key }}"
                    @selected($multiple ? in_array($key, (array)$selectedValue) : $selectedValue == $key)
                >
                    {{ is_array($option) ? ($option['label'] ?? $option) : $option }}
                </option>
            @endif
        @endforeach
    </select>

    @if($error)
        <p class="text-xs text-destructive">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
</div>
