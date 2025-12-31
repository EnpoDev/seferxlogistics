{{-- Confirm Dialog - Alpine Store ile calisan versiyon --}}
<div
    x-data
    x-show="$store.modal.confirmDialog.open"
    x-cloak
    class="fixed inset-0 z-[9998] overflow-y-auto"
>
    {{-- Backdrop --}}
    <div
        x-show="$store.modal.confirmDialog.open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 backdrop-blur-sm"
        style="background-color: rgba(0, 0, 0, 0.5);"
        @click="$store.modal.confirmDialog.onCancel && $store.modal.confirmDialog.onCancel()"
    ></div>

    {{-- Dialog --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="$store.modal.confirmDialog.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white dark:bg-[#181818] rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-md w-full transform transition-all"
            @click.stop
        >
            <div class="p-6">
                {{-- Icon --}}
                <div
                    class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"
                    :class="$store.modal.getConfirmIconClass()"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>

                {{-- Title --}}
                <h3
                    class="text-lg font-bold text-center text-black dark:text-white mb-2"
                    x-text="$store.modal.confirmDialog.title"
                ></h3>

                {{-- Message --}}
                <p
                    class="text-sm text-center text-gray-600 dark:text-gray-400 mb-6"
                    x-text="$store.modal.confirmDialog.message"
                ></p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="$store.modal.confirmDialog.onCancel && $store.modal.confirmDialog.onCancel()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors font-medium"
                        x-text="$store.modal.confirmDialog.cancelText"
                    ></button>

                    <button
                        type="button"
                        @click="$store.modal.confirmDialog.onConfirm && $store.modal.confirmDialog.onConfirm()"
                        :disabled="$store.modal.confirmDialog.loading"
                        :class="$store.modal.getConfirmButtonClass()"
                        class="flex-1 px-4 py-2.5 text-white rounded-lg transition-colors font-medium flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <span x-show="!$store.modal.confirmDialog.loading" x-text="$store.modal.confirmDialog.confirmText"></span>
                        <svg x-show="$store.modal.confirmDialog.loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
