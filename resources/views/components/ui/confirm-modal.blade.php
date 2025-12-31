@props([
    'name' => 'confirmModal',
    'title' => 'Onay',
    'type' => 'danger', // danger, warning, info
])

@php
    $typeConfig = [
        'danger' => [
            'icon' => 'trash',
            'iconBg' => 'bg-red-100 dark:bg-red-900/30',
            'iconColor' => 'text-red-600 dark:text-red-400',
            'buttonClass' => 'bg-red-600 hover:bg-red-700 text-white',
        ],
        'warning' => [
            'icon' => 'warning',
            'iconBg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'iconColor' => 'text-yellow-600 dark:text-yellow-400',
            'buttonClass' => 'bg-yellow-600 hover:bg-yellow-700 text-white',
        ],
        'info' => [
            'icon' => 'info',
            'iconBg' => 'bg-blue-100 dark:bg-blue-900/30',
            'iconColor' => 'text-blue-600 dark:text-blue-400',
            'buttonClass' => 'bg-blue-600 hover:bg-blue-700 text-white',
        ],
    ];

    $config = $typeConfig[$type] ?? $typeConfig['danger'];
@endphp

<div
    x-data="{
        open: false,
        title: '{{ $title }}',
        message: '',
        confirmText: 'Onayla',
        cancelText: 'Vazgeç',
        onConfirm: null,
        onCancel: null,

        show(options = {}) {
            this.title = options.title || '{{ $title }}';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Onayla';
            this.cancelText = options.cancelText || 'Vazgeç';
            this.onConfirm = options.onConfirm || null;
            this.onCancel = options.onCancel || null;
            this.open = true;
        },

        confirm() {
            if (this.onConfirm && typeof this.onConfirm === 'function') {
                this.onConfirm();
            }
            this.open = false;
        },

        cancel() {
            if (this.onCancel && typeof this.onCancel === 'function') {
                this.onCancel();
            }
            this.open = false;
        }
    }"
    x-on:open-confirm.window="show($event.detail)"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    {{ $attributes }}
>
    <!-- Backdrop -->
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-background/80 backdrop-blur-sm"
        @click="cancel()"
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
            class="relative bg-card text-card-foreground rounded-xl shadow-lg border border-border w-full max-w-md transform transition-all"
            @click.stop
        >
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 {{ $config['iconBg'] }} rounded-full p-3">
                        <x-ui.icon :name="$config['icon']" class="w-6 h-6 {{ $config['iconColor'] }}" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-foreground" x-text="title"></h3>
                        <p class="mt-2 text-sm text-muted-foreground" x-text="message"></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 border-t border-border bg-muted/50 rounded-b-xl">
                <x-ui.button
                    type="button"
                    variant="secondary"
                    @click="cancel()"
                    x-text="cancelText"
                />
                <button
                    type="button"
                    @click="confirm()"
                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 {{ $config['buttonClass'] }}"
                    x-text="confirmText"
                ></button>
            </div>
        </div>
    </div>
</div>
