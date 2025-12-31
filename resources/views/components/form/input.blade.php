@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'label' => null,
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
    'icon' => null,
    'prefix' => null,
    'suffix' => null,
    'size' => 'md',
])

@php
    // Boyut stilleri
    $heights = [
        'sm' => 'h-8 text-xs',
        'md' => 'h-10 text-sm',
        'lg' => 'h-11 text-base',
    ];

    $inputHeight = $heights[$size] ?? $heights['md'];
    $inputId = $id ?? $name;
    $inputValue = $name ? old($name, $value) : $value;

    // Padding hesapla
    $paddingLeft = ($icon || $prefix) ? 'pl-11' : 'pl-4';
    $paddingRight = $suffix ? 'pr-11' : 'pr-4';

    // Input class'lari
    $inputClasses = "flex w-full rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/20 focus:border-gray-400 dark:focus:border-gray-500 transition-colors {$paddingLeft} {$paddingRight} {$inputHeight}";

    if ($error) {
        $inputClasses .= ' border-red-300 dark:border-red-700 focus:ring-red-500/20';
    } else {
        $inputClasses .= ' border-gray-200 dark:border-gray-700';
    }

    if ($disabled || $readonly) {
        $inputClasses .= ' cursor-not-allowed opacity-50';
    }
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-2']) }}>
    @if($label)
        <label @if($inputId) for="{{ $inputId }}" @endif class="text-sm font-medium leading-none text-foreground peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {{ $label }}
            @if($required)
                <span class="text-destructive">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <x-ui.icon :name="$icon" class="w-5 h-5 text-gray-400" />
            </div>
        @elseif($prefix)
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <span class="text-gray-400 text-sm">{{ $prefix }}</span>
            </div>
        @endif

        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" @endif
            @if($inputId) id="{{ $inputId }}" @endif
            value="{{ $inputValue }}"
            placeholder="{{ $placeholder }}"
            @disabled($disabled)
            @readonly($readonly)
            @required($required)
            {{ $attributes->except('class')->merge(['class' => $inputClasses]) }}
        >

        @if($suffix)
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                <span class="text-gray-400 text-sm">{{ $suffix }}</span>
            </div>
        @endif
    </div>

    @if($error)
        <p class="text-xs text-destructive">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
</div>
