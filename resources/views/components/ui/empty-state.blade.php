@props([
    'title' => 'Veri bulunamadi',
    'description' => null,
    'icon' => 'package',
    'actionText' => null,
    'actionUrl' => null,
    'actionVariant' => 'primary',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-4 text-center']) }}>
    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
        <x-ui.icon :name="$icon" class="w-8 h-8 text-gray-400 dark:text-gray-500" />
    </div>

    <h3 class="text-lg font-medium text-black dark:text-white mb-1">{{ $title }}</h3>

    @if($description)
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">{{ $description }}</p>
    @endif

    @if($actionText && $actionUrl)
        <div class="mt-6">
            <x-ui.button :href="$actionUrl" :variant="$actionVariant" icon="plus">
                {{ $actionText }}
            </x-ui.button>
        </div>
    @endif

    @isset($actions)
        <div class="mt-6 flex flex-wrap gap-3 justify-center">
            {{ $actions }}
        </div>
    @endisset
</div>
