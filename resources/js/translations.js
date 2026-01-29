/**
 * Turkish translations for JavaScript
 * Synchronized with lang/tr/statuses.php and lang/tr/labels.php
 */
window.translations = {
    statuses: {
        order: {
            pending: 'Beklemede',
            preparing: 'Hazırlanıyor',
            ready: 'Hazır',
            on_delivery: 'Yolda',
            delivered: 'Teslim Edildi',
            cancelled: 'İptal'
        },
        courier: {
            available: 'Müsait',
            busy: 'Meşgul',
            offline: 'Çevrimdışı',
            on_break: 'Molada'
        },
        call: {
            initiated: 'Başlatıldı',
            ringing: 'Çalıyor',
            answered: 'Cevaplandı',
            completed: 'Tamamlandı',
            missed: 'Cevapsız',
            failed: 'Başarısız',
            busy: 'Meşgul'
        },
        transaction: {
            pending: 'Beklemede',
            completed: 'Tamamlandı',
            failed: 'Başarısız',
            refunded: 'İade Edildi',
            partially_refunded: 'Kısmi İade',
            cancelled: 'İptal Edildi'
        },
        subscription: {
            active: 'Aktif',
            trial: 'Deneme',
            expired: 'Süresi Doldu',
            cancelled: 'İptal Edildi',
            suspended: 'Askıya Alındı',
            pending: 'Beklemede',
            past_due: 'Ödeme Gecikmiş'
        },
        integration: {
            inactive: 'Pasif',
            connecting: 'Bağlanıyor',
            connected: 'Bağlı',
            error: 'Hata'
        },
        ticket: {
            open: 'Açık',
            in_progress: 'İşlemde',
            waiting_response: 'Yanıt Bekleniyor',
            resolved: 'Çözüldü',
            closed: 'Kapatıldı'
        },
        cash_transaction: {
            pending: 'Beklemede',
            completed: 'Tamamlandı',
            cancelled: 'İptal'
        }
    },
    labels: {
        payment_methods: {
            cash: 'Nakit',
            card: 'Kredi Kartı',
            online: 'Online',
            card_on_delivery: 'Kapıda Kart'
        },
        order_sources: {
            manual: 'Manuel',
            api: 'API',
            yemeksepeti: 'Yemeksepeti',
            getir: 'Getir',
            trendyol: 'Trendyol',
            migros: 'Migros'
        },
        priorities: {
            low: 'Düşük',
            normal: 'Normal',
            high: 'Yüksek',
            urgent: 'Acil'
        },
        general: {
            yes: 'Evet',
            no: 'Hayır',
            active: 'Aktif',
            inactive: 'Pasif',
            enabled: 'Açık',
            disabled: 'Kapalı',
            all: 'Tümü',
            none: 'Hiçbiri',
            unknown: 'Bilinmiyor'
        }
    }
};

/**
 * Helper function to get translation
 * @param {string} key - Dot notation key (e.g., 'statuses.order.pending')
 * @param {string} fallback - Fallback value if key not found
 * @returns {string}
 */
window.__ = function(key, fallback = null) {
    const keys = key.split('.');
    let value = window.translations;

    for (const k of keys) {
        if (value && typeof value === 'object' && k in value) {
            value = value[k];
        } else {
            return fallback || key;
        }
    }

    return value || fallback || key;
};

/**
 * Get status label helper
 * @param {string} type - Status type (order, courier, etc.)
 * @param {string} status - Status value
 * @returns {string}
 */
window.getStatusLabel = function(type, status) {
    return window.__(`statuses.${type}.${status}`, status);
};
