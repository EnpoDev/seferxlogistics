@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
    'inline' => false,
])

@php
    $selectedValue = old($name, $selected);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-2']) }}>
    @if($label)
        <label class="block text-sm font-medium text-black dark:text-white">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="{{ $inline ? 'flex flex-wrap gap-4' : 'space-y-2' }}">
        @foreach($options as $value => $optionLabel)
            <label class="flex items-center gap-2 cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $value }}"
                    @checked($selectedValue == $value)
                    @disabled($disabled)
                    @required($required)
                    class="w-4 h-4 border-gray-300 dark:border-gray-600 text-black dark:text-white focus:ring-black dark:focus:ring-white focus:ring-2"
                    {{ $attributes->except('class') }}
                >
                <span class="text-sm text-black dark:text-white">{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>

    @if($error)
        <p class="text-xs text-red-500">{{ $error }}</p>
    @elseif($hint)
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
