@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
    'icon' => true,
])

@php
    // shadcn pattern
    $types = [
        'default' => [
            'container' => 'bg-background text-foreground border-border',
            'icon' => 'info',
        ],
        'destructive' => [
            'container' => 'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive',
            'icon' => 'error',
        ],
        'info' => [
            'container' => 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
            'icon' => 'info',
        ],
        'success' => [
            'container' => 'border-green-200 bg-green-50 text-green-900 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300',
            'icon' => 'success',
        ],
        'warning' => [
            'container' => 'border-yellow-200 bg-yellow-50 text-yellow-900 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
            'icon' => 'warning',
        ],
        'danger' => [
            'container' => 'border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300',
            'icon' => 'error',
        ],
    ];

    $typeSet = $types[$type] ?? $types['info'];
    $baseClasses = 'relative w-full rounded-[--radius-lg] border p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4';
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => "{$baseClasses} {$typeSet['container']}"]) }}
    role="alert"
>
    @if($icon)
        <x-ui.icon :name="$typeSet['icon']" class="w-4 h-4" />
    @endif

    <div class="@if($icon) @endif">
        @if($title)
            <h5 class="mb-1 font-medium leading-none tracking-tight">{{ $title }}</h5>
            <div class="text-sm [&_p]:leading-relaxed opacity-90">
                {{ $slot }}
            </div>
        @else
            <div class="text-sm [&_p]:leading-relaxed">{{ $slot }}</div>
        @endif
    </div>

    @if($dismissible)
        <button
            type="button"
            @click="show = false"
            class="absolute right-4 top-4 rounded-[--radius-sm] opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
        >
            <span class="sr-only">Kapat</span>
            <x-ui.icon name="x" class="w-4 h-4" />
        </button>
    @endif
</div>
