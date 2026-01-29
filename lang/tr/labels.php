<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ödeme Yöntemleri
    |--------------------------------------------------------------------------
    */
    'payment_methods' => [
        'cash' => 'Nakit',
        'card' => 'Kredi Kartı',
        'online' => 'Online',
        'card_on_delivery' => 'Kapıda Kart',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sipariş Kaynakları
    |--------------------------------------------------------------------------
    */
    'order_sources' => [
        'manual' => 'Manuel',
        'api' => 'API',
        'yemeksepeti' => 'Yemeksepeti',
        'getir' => 'Getir',
        'trendyol' => 'Trendyol',
        'migros' => 'Migros',
    ],

    /*
    |--------------------------------------------------------------------------
    | Öncelik Seviyeleri
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        'low' => 'Düşük',
        'normal' => 'Normal',
        'high' => 'Yüksek',
        'urgent' => 'Acil',
    ],

    /*
    |--------------------------------------------------------------------------
    | Çağrı Türleri
    |--------------------------------------------------------------------------
    */
    'caller_types' => [
        'customer' => 'Müşteri',
        'courier' => 'Kurye',
    ],

    /*
    |--------------------------------------------------------------------------
    | Genel Etiketler
    |--------------------------------------------------------------------------
    */
    'general' => [
        'yes' => 'Evet',
        'no' => 'Hayır',
        'active' => 'Aktif',
        'inactive' => 'Pasif',
        'enabled' => 'Açık',
        'disabled' => 'Kapalı',
        'all' => 'Tümü',
        'none' => 'Hiçbiri',
        'unknown' => 'Bilinmiyor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Zaman Etiketleri
    |--------------------------------------------------------------------------
    */
    'time' => [
        'today' => 'Bugün',
        'yesterday' => 'Dün',
        'this_week' => 'Bu Hafta',
        'this_month' => 'Bu Ay',
        'minutes' => 'dakika',
        'hours' => 'saat',
        'days' => 'gün',
    ],

    /*
    |--------------------------------------------------------------------------
    | İşlem Türleri
    |--------------------------------------------------------------------------
    */
    'transaction_types' => [
        'earning' => 'Kazanç',
        'payment' => 'Ödeme',
        'bonus' => 'Prim',
        'penalty' => 'Ceza',
        'adjustment' => 'Düzeltme',
        'cash_collection' => 'Nakit Tahsilat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Destek Kategorileri
    |--------------------------------------------------------------------------
    */
    'ticket_categories' => [
        'technical' => 'Teknik Sorun',
        'billing' => 'Ödeme/Fatura',
        'feature_request' => 'Özellik Talebi',
        'complaint' => 'Şikayet',
        'other' => 'Diğer',
    ],
];
