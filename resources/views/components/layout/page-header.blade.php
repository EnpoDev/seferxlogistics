@props([
    'title',
    'subtitle' => null,
    'backUrl' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-start gap-4">
            @if($backUrl)
                <a href="{{ $backUrl }}" class="mt-1 p-2 -ml-2 text-gray-500 hover:text-black dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <x-ui.icon name="arrow-left" class="w-5 h-5" />
                </a>
            @endif

            <div>
                @isset($icon)
                    <div class="flex items-center gap-3 mb-1">
                        {{ $icon }}
                        <h1 class="text-2xl font-bold text-black dark:text-white">{{ $title }}</h1>
                    </div>
                @else
                    <h1 class="text-2xl font-bold text-black dark:text-white">{{ $title }}</h1>
                @endisset

                @if($subtitle)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-3">
                {{ $actions }}
            </div>
        @endisset
    </div>

    @isset($tabs)
        <div class="mt-4">
            {{ $tabs }}
        </div>
    @endisset
</div>
