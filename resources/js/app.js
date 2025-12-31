import './bootstrap';
import './map';
import './echo-listeners';

// Alpine.js Store'lari
import { registerStores, setupGlobalFunctions } from './stores';

/**
 * Alpine.js Store Kaydi
 * Alpine CDN uzerinden yuklendiginde, stores'lari kaydetmek icin
 * alpine:init event'ini dinliyoruz
 */
document.addEventListener('alpine:init', () => {
    registerStores(window.Alpine);

    // Theme store'u baslat
    window.Alpine.store('theme').init();
});

/**
 * Global fonksiyonlari ayarla (geriye donuk uyumluluk)
 * showToast, showConfirmDialog gibi fonksiyonlar
 */
document.addEventListener('DOMContentLoaded', () => {
    setupGlobalFunctions();
});
