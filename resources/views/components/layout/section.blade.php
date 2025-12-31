@props([
    'title' => null,
    'description' => null,
    'collapsible' => false,
    'collapsed' => false,
    'border' => false,
])

<div
    @if($collapsible) x-data="{ open: {{ $collapsed ? 'false' : 'true' }} }" @endif
    {{ $attributes->merge(['class' => 'space-y-4' . ($border ? ' pb-6 border-b border-gray-200 dark:border-gray-800' : '')]) }}
>
    @if($title)
        <div class="flex items-center justify-between">
            <div>
                @if($collapsible)
                    <button
                        type="button"
                        @click="open = !open"
                        class="flex items-center gap-2 text-lg font-semibold text-black dark:text-white hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                    >
                        <span>{{ $title }}</span>
                        <svg
                            class="w-5 h-5 transition-transform duration-200"
                            :class="{ 'rotate-180': open }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                @else
                    <h2 class="text-lg font-semibold text-black dark:text-white">{{ $title }}</h2>
                @endif

                @if($description)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    @if($collapsible)
        <div x-show="open" x-collapse>
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
