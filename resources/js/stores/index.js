/**
 * Alpine.js Stores
 * Tum store'larin merkezi kayit noktasi
 */
import toastStore from './toast';
import modalStore from './modal';
import themeStore from './theme';

/**
 * Store'lari Alpine'a kaydet
 * Bu fonksiyon alpine:init event'inde cagrilmali
 */
export function registerStores(Alpine) {
    Alpine.store('toast', toastStore);
    Alpine.store('modal', modalStore);
    Alpine.store('theme', themeStore);
}

/**
 * Store'lari export et (gerekirse dogrudan kullanim icin)
 */
export { toastStore, modalStore, themeStore };

/**
 * Global window fonksiyonlari (geriye donuk uyumluluk)
 * Mevcut showToast, showConfirmDialog gibi fonksiyonlari desteklemek icin
 */
export function setupGlobalFunctions() {
    // Toast global fonksiyonu
    window.showToast = function(message, type = 'success', duration = 4000) {
        if (window.Alpine && window.Alpine.store('toast')) {
            return window.Alpine.store('toast').show(message, type, duration);
        }
        // Fallback: Store henuz hazir degilse bekle
        console.warn('Toast store not ready, message:', message);
    };

    // Confirm dialog global fonksiyonu
    window.showConfirmDialog = function(options = {}) {
        if (window.Alpine && window.Alpine.store('modal')) {
            return window.Alpine.store('modal').confirm(options);
        }
        console.warn('Modal store not ready');
    };

    // Confirm dialog kapat
    window.closeConfirmDialog = function() {
        if (window.Alpine && window.Alpine.store('modal')) {
            window.Alpine.store('modal').closeConfirm();
        }
    };

    // Confirm action (onay butonuna basinca)
    window.confirmAction = async function() {
        const store = window.Alpine?.store('modal');
        if (store && store.confirmDialog.onConfirm) {
            await store.confirmDialog.onConfirm();
        }
    };
}
