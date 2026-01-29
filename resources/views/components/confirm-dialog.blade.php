<!-- Confirm Dialog Component -->
<div id="confirmDialog" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9998] flex items-center justify-center p-4 animate-fadeIn">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="confirmDialogContent">
        <div class="p-6">
            <!-- Icon -->
            <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            
            <!-- Title -->
            <h3 class="text-lg font-bold text-center text-black dark:text-white mb-2" id="confirmTitle">
                Emin misiniz?
            </h3>
            
            <!-- Message -->
            <p class="text-sm text-center text-gray-600 dark:text-gray-400 mb-6" id="confirmMessage">
                Bu işlem geri alınamaz.
            </p>
            
            <!-- Actions -->
            <div class="flex gap-3">
                <button type="button" onclick="window.closeConfirmDialog()" 
                    class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors font-medium">
                    İptal
                </button>
                <button type="button" id="confirmButton"
                    class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                    <span id="confirmButtonText">Sil</span>
                    <svg class="hidden w-4 h-4 animate-spin" id="confirmSpinner" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let confirmCallback = null;

    function initConfirmDialog() {
        const dialog = document.getElementById('confirmDialog');
        const confirmButton = document.getElementById('confirmButton');

        if (!dialog || !confirmButton) return;

        window.showConfirmDialog = function(options = {}) {
            const content = document.getElementById('confirmDialogContent');
            const title = document.getElementById('confirmTitle');
            const message = document.getElementById('confirmMessage');
            const button = document.getElementById('confirmButton');
            const buttonText = document.getElementById('confirmButtonText');

            title.textContent = options.title || 'Emin misiniz?';
            message.textContent = options.message || 'Bu işlem geri alınamaz.';
            buttonText.textContent = options.confirmText || 'Sil';

            if (options.type === 'danger') {
                button.className = 'flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium flex items-center justify-center gap-2';
            } else {
                button.className = 'flex-1 px-4 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-lg transition-colors font-medium flex items-center justify-center gap-2';
            }

            confirmCallback = options.onConfirm;

            dialog.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);

            // ESC to close
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    window.closeConfirmDialog();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
        };

        window.closeConfirmDialog = function() {
            const content = document.getElementById('confirmDialogContent');

            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                dialog.classList.add('hidden');
                confirmCallback = null;
            }, 200);
        };

        window.confirmAction = async function() {
            if (!confirmCallback) return;

            const button = document.getElementById('confirmButton');
            const buttonText = document.getElementById('confirmButtonText');
            const spinner = document.getElementById('confirmSpinner');

            // Show loading
            button.disabled = true;
            buttonText.classList.add('opacity-0');
            spinner.classList.remove('hidden');

            try {
                await confirmCallback();
                window.closeConfirmDialog();
            } catch (error) {
                console.error(error);
                if (window.showToast) showToast('Bir hata oluştu', 'error');
            } finally {
                button.disabled = false;
                buttonText.classList.remove('opacity-0');
                spinner.classList.add('hidden');
            }
        };

        confirmButton.addEventListener('click', window.confirmAction);

        // Click outside to close
        dialog.addEventListener('click', function(e) {
            if (e.target === this) {
                window.closeConfirmDialog();
            }
        });
    }

    // Run after everything is loaded to ensure we override any other definitions
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initConfirmDialog, 0);
        });
    } else {
        setTimeout(initConfirmDialog, 0);
    }
})();
</script>

