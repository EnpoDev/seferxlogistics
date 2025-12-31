@props([
    'name',
    'label' => null,
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
    'rows' => 4,
    'resize' => true,
])

@php
    // Textarea class'lari
    $textareaClasses = 'flex min-h-[80px] w-full rounded-xl border bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/20 focus:border-gray-400 dark:focus:border-gray-500 transition-colors';

    if ($error) {
        $textareaClasses .= ' border-red-300 dark:border-red-700 focus:ring-red-500/20';
    } else {
        $textareaClasses .= ' border-gray-200 dark:border-gray-700';
    }

    if ($disabled || $readonly) {
        $textareaClasses .= ' cursor-not-allowed opacity-50';
    }

    if (!$resize) {
        $textareaClasses .= ' resize-none';
    }
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

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @disabled($disabled)
        @readonly($readonly)
        @required($required)
        {{ $attributes->except('class')->merge(['class' => $textareaClasses]) }}
    >{{ old($name, $value) }}</textarea>

    @if($error)
        <p class="text-xs text-destructive">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
</div>
