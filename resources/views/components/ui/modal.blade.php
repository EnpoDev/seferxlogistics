@props([
    'name',
    'title' => null,
    'size' => 'md',
    'closeable' => true,
    'closeOnClickAway' => true,
])

@php
    // Boyut stilleri
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        'full' => 'max-w-full mx-4',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="@if($closeable) open = false @endif"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    {{ $attributes }}
>
    <!-- Backdrop - shadcn pattern -->
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-background/80 backdrop-blur-sm"
        @if($closeOnClickAway && $closeable)
            @click="open = false"
        @endif
    ></div>

    <!-- Modal Panel -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-card text-card-foreground rounded-[--radius-lg] shadow-lg border border-border w-full {{ $sizeClass }} transform transition-all"
            @click.stop
        >
            @if($title || $closeable)
                <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                    @if($title)
                        <h3 class="text-lg font-semibold leading-none tracking-tight text-foreground">{{ $title }}</h3>
                    @else
                        <div></div>
                    @endif

                    @if($closeable)
                        <button
                            type="button"
                            @click="open = false"
                            class="rounded-[--radius-sm] p-1.5 opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            <x-ui.icon name="x" class="w-4 h-4" />
                            <span class="sr-only">Close</span>
                        </button>
                    @endif
                </div>
            @endif

            <div class="p-6">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 px-6 py-4 border-t border-border bg-muted/50 rounded-b-[--radius-lg]">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
