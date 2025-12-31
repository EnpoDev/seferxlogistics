@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
    'value' => '1',
    'error' => null,
])

@php
    $isChecked = $name ? old($name, $checked) : $checked;
    $inputId = $id ?? $name;
@endphp

<div {{ $attributes->only('class') }}>
    <label class="flex items-start gap-3 cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
        <div class="flex items-center h-5 mt-0.5">
            <input
                type="checkbox"
                @if($name) name="{{ $name }}" @endif
                @if($inputId) id="{{ $inputId }}" @endif
                value="{{ $value }}"
                @checked($isChecked)
                @disabled($disabled)
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-gray-400 dark:focus:ring-gray-500 focus:ring-2"
                {{ $attributes->except('class') }}
            >
        </div>

        <div class="flex-1">
            @if($label)
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
            @endif

            @if($description)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $description }}</p>
            @endif
        </div>
    </label>

    @if($error)
        <p class="text-xs text-red-500 mt-1">{{ $error }}</p>
    @endif
</div>
