@props([
    'name' => 'search',
    'placeholder' => 'Ara...',
    'value' => null,
    'debounce' => 300,
    'autoSubmit' => false,
    'size' => 'md',
])

@php
    // Boyut stilleri
    $sizes = [
        'sm' => 'h-8 text-sm pl-10 pr-9',
        'md' => 'h-10 text-sm pl-11 pr-10',
        'lg' => 'h-11 text-base pl-12 pr-10',
    ];

    $iconPositions = [
        'sm' => 'left-3',
        'md' => 'left-4',
        'lg' => 'left-4',
    ];

    $inputSize = $sizes[$size] ?? $sizes['md'];
    $iconPos = $iconPositions[$size] ?? $iconPositions['md'];
@endphp

<div
    x-data="{
        search: '{{ old($name, $value) }}',
        timeout: null,
        submit() {
            @if($autoSubmit)
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.$refs.form?.submit() || this.$el.closest('form')?.submit();
                }, {{ $debounce }});
            @endif
        }
    }"
    {{ $attributes->only('class')->merge(['class' => 'relative']) }}
>
    <div class="absolute inset-y-0 {{ $iconPos }} flex items-center pointer-events-none">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>

    <input
        type="search"
        name="{{ $name }}"
        x-model="search"
        @input="submit()"
        placeholder="{{ $placeholder }}"
        class="w-full {{ $inputSize }} bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-900/10 dark:focus:ring-white/20 focus:border-gray-400 dark:focus:border-gray-500 transition-colors"
        {{ $attributes->except('class') }}
    >

    <button
        type="button"
        x-show="search.length > 0"
        @click="search = ''; @if($autoSubmit) $el.closest('form')?.submit() @endif"
        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
