{{-- Toast Container - Alpine Store ile calisan versiyon --}}
<div
    x-data
    id="toast-container"
    class="fixed top-4 right-4 z-[9999] space-y-2 pointer-events-none"
>
    <template x-for="toast in $store.toast.toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            :class="$store.toast.getColorClass(toast.type)"
            class="pointer-events-auto flex items-center gap-3 text-white px-4 py-3 rounded-lg shadow-lg max-w-md"
        >
            <div class="flex-shrink-0" x-html="$store.toast.getIcon(toast.type)"></div>
            <div class="flex-1 text-sm font-medium" x-text="toast.message"></div>
            <button
                @click="$store.toast.dismiss(toast.id)"
                class="flex-shrink-0 hover:bg-white/20 rounded p-1 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </template>
</div>

{{-- Laravel Flash Messages --}}
<script>
document.addEventListener('alpine:initialized', function() {
    @if(session('success'))
        Alpine.store('toast').success('{{ session('success') }}');
    @endif

    @if(session('error'))
        Alpine.store('toast').error('{{ session('error') }}');
    @endif

    @if(session('warning'))
        Alpine.store('toast').warning('{{ session('warning') }}');
    @endif

    @if(session('info'))
        Alpine.store('toast').info('{{ session('info') }}');
    @endif
});
</script>
