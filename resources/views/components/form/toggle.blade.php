@props([
    'name',
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
    'error' => null,
])

@php
    $isChecked = old($name, $checked);
@endphp

<div {{ $attributes->only('class') }} x-data="{ checked: {{ $isChecked ? 'true' : 'false' }} }">
    <label class="flex items-center justify-between gap-3 cursor-pointer {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}">
        <div class="flex-1">
            @if($label)
                <span class="font-medium text-black dark:text-white">{{ $label }}</span>
            @endif

            @if($description)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $description }}</p>
            @endif
        </div>

        <button
            type="button"
            role="switch"
            :aria-checked="checked"
            @click="checked = !checked"
            @if($disabled) disabled @endif
            class="relative h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}"
            :class="checked ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'"
        >
            <span
                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white dark:bg-gray-900 shadow-lg transition-transform duration-200"
                :class="checked ? 'translate-x-5' : 'translate-x-0'"
            ></span>
        </button>
        <input type="hidden" name="{{ $name }}" :value="checked ? '1' : '0'">
    </label>

    @if($error)
        <p class="text-xs text-red-500 mt-1">{{ $error }}</p>
    @endif
</div>
