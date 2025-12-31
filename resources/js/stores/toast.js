/**
 * Toast Notification Store
 * Global toast bildirim yonetimi icin Alpine.js store
 */
export default {
    toasts: [],
    counter: 0,

    /**
     * Toast bildirimi goster
     * @param {string} message - Bildirim mesaji
     * @param {string} type - success, error, warning, info
     * @param {number} duration - Milisaniye cinsinden sure (default: 4000)
     */
    show(message, type = 'success', duration = 4000) {
        const id = ++this.counter;

        const toast = {
            id,
            message,
            type,
            visible: true,
        };

        this.toasts.push(toast);

        // Otomatik kaldirma
        setTimeout(() => {
            this.dismiss(id);
        }, duration);

        return id;
    },

    /**
     * Basarili bildirim goster
     */
    success(message, duration = 4000) {
        return this.show(message, 'success', duration);
    },

    /**
     * Hata bildirimi goster
     */
    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    },

    /**
     * Uyari bildirimi goster
     */
    warning(message, duration = 4500) {
        return this.show(message, 'warning', duration);
    },

    /**
     * Bilgi bildirimi goster
     */
    info(message, duration = 4000) {
        return this.show(message, 'info', duration);
    },

    /**
     * Toast'i kapat
     */
    dismiss(id) {
        const index = this.toasts.findIndex(t => t.id === id);
        if (index > -1) {
            this.toasts[index].visible = false;
            // Animasyon bittikten sonra sil
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 300);
        }
    },

    /**
     * Tum toast'lari temizle
     */
    clear() {
        this.toasts.forEach(t => t.visible = false);
        setTimeout(() => {
            this.toasts = [];
        }, 300);
    },

    /**
     * Toast tipine gore renk class'i dondur
     */
    getColorClass(type) {
        const colors = {
            success: 'bg-green-500 dark:bg-green-600',
            error: 'bg-red-500 dark:bg-red-600',
            warning: 'bg-yellow-500 dark:bg-yellow-600',
            info: 'bg-blue-500 dark:bg-blue-600'
        };
        return colors[type] || colors.info;
    },

    /**
     * Toast tipine gore ikon dondur
     */
    getIcon(type) {
        const icons = {
            success: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>`,
            error: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>`,
            warning: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>`,
            info: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`
        };
        return icons[type] || icons.info;
    }
};
