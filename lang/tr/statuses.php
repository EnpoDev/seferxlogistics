<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sipariş Durumları
    |--------------------------------------------------------------------------
    */
    'order' => [
        'pending' => 'Beklemede',
        'preparing' => 'Hazırlanıyor',
        'ready' => 'Hazır',
        'on_delivery' => 'Yolda',
        'delivered' => 'Teslim Edildi',
        'cancelled' => 'İptal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kurye Durumları
    |--------------------------------------------------------------------------
    */
    'courier' => [
        'available' => 'Müsait',
        'busy' => 'Meşgul',
        'offline' => 'Çevrimdışı',
        'on_break' => 'Molada',
    ],

    /*
    |--------------------------------------------------------------------------
    | Çağrı Durumları
    |--------------------------------------------------------------------------
    */
    'call' => [
        'initiated' => 'Başlatıldı',
        'ringing' => 'Çalıyor',
        'answered' => 'Cevaplandı',
        'completed' => 'Tamamlandı',
        'missed' => 'Cevapsız',
        'failed' => 'Başarısız',
        'busy' => 'Meşgul',
    ],

    /*
    |--------------------------------------------------------------------------
    | İşlem (Transaction) Durumları
    |--------------------------------------------------------------------------
    */
    'transaction' => [
        'pending' => 'Beklemede',
        'completed' => 'Tamamlandı',
        'failed' => 'Başarısız',
        'refunded' => 'İade Edildi',
        'partially_refunded' => 'Kısmi İade',
        'cancelled' => 'İptal Edildi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Abonelik Durumları
    |--------------------------------------------------------------------------
    */
    'subscription' => [
        'active' => 'Aktif',
        'trial' => 'Deneme',
        'expired' => 'Süresi Doldu',
        'cancelled' => 'İptal Edildi',
        'suspended' => 'Askıya Alındı',
        'pending' => 'Beklemede',
        'past_due' => 'Ödeme Gecikmiş',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entegrasyon Durumları
    |--------------------------------------------------------------------------
    */
    'integration' => [
        'inactive' => 'Pasif',
        'connecting' => 'Bağlanıyor',
        'connected' => 'Bağlı',
        'error' => 'Hata',
    ],

    /*
    |--------------------------------------------------------------------------
    | Destek Talep Durumları
    |--------------------------------------------------------------------------
    */
    'ticket' => [
        'open' => 'Açık',
        'in_progress' => 'İşlemde',
        'waiting_response' => 'Yanıt Bekleniyor',
        'waiting_customer' => 'Müşteri Yanıtı Bekleniyor',
        'resolved' => 'Çözüldü',
        'closed' => 'Kapatıldı',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nakit İşlem Durumları
    |--------------------------------------------------------------------------
    */
    'cash_transaction' => [
        'pending' => 'Beklemede',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal',
    ],
];
