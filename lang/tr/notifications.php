<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Bildirimleri
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'order_confirmed' => [
            'title' => 'Sipariş Onaylandı',
            'body' => 'Siparişiniz onaylandı! Takip kodu: :tracking_code. Takip için: :tracking_url',
        ],
        'order_preparing' => [
            'title' => 'Sipariş Hazırlanıyor',
            'body' => 'Siparişiniz hazırlanıyor. Tahmini süre: :estimated_time dakika.',
        ],
        'order_ready' => [
            'title' => 'Sipariş Hazır',
            'body' => 'Siparişiniz hazır, kurye yola çıkmak üzere.',
        ],
        'courier_assigned' => [
            'title' => 'Kurye Atandı',
            'body' => 'Kuryeniz :courier_name siparişinizi teslim alacak. Tel: :courier_phone',
        ],
        'order_on_way' => [
            'title' => 'Sipariş Yola Çıktı',
            'body' => 'Siparişiniz yola çıktı! Tahmini varış: :estimated_time dakika. Takip: :tracking_url',
        ],
        'courier_arrived' => [
            'title' => 'Kurye Kapıda',
            'body' => 'Kuryeniz kapınızda! Lütfen siparişinizi teslim alın.',
        ],
        'order_delivered' => [
            'title' => 'Sipariş Teslim Edildi',
            'body' => 'Siparişiniz teslim edildi. Bizi tercih ettiğiniz için teşekkürler!',
        ],
        'order_cancelled' => [
            'title' => 'Sipariş İptal Edildi',
            'body' => 'Siparişiniz iptal edildi. Sebep: :reason',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Bildirimleri
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'order_confirmed' => [
            'title' => 'Sipariş Onaylandı',
            'body' => "Merhaba :customer_name,\n\nSiparişiniz onaylandı!\n\nSipariş No: :order_number\nTutar: :total TL\n\nTakip için: :tracking_url\n\nTeşekkürler!",
        ],
        'order_preparing' => [
            'title' => 'Sipariş Hazırlanıyor',
            'body' => "Merhaba :customer_name,\n\nSiparişiniz hazırlanıyor.\nTahmini süre: :estimated_time dakika.\n\nTakip için: :tracking_url",
        ],
        'order_ready' => [
            'title' => 'Sipariş Hazır',
            'body' => "Merhaba :customer_name,\n\nSiparişiniz hazır! Kurye yola çıkmak üzere.\n\nTakip için: :tracking_url",
        ],
        'courier_assigned' => [
            'title' => 'Kurye Atandı',
            'body' => "Merhaba :customer_name,\n\nKuryeniz: :courier_name\nTelefon: :courier_phone\n\nSiparişiniz kısa sürede teslim edilecek.",
        ],
        'order_on_way' => [
            'title' => 'Sipariş Yola Çıktı',
            'body' => "Merhaba :customer_name,\n\nSiparişiniz yola çıktı!\n\nKurye: :courier_name\nTahmini varış: :estimated_time dakika\n\nCanlı takip: :tracking_url",
        ],
        'courier_arrived' => [
            'title' => 'Kurye Kapıda',
            'body' => "Merhaba :customer_name,\n\nKuryeniz kapınızda! Lütfen siparişinizi teslim alın.",
        ],
        'order_delivered' => [
            'title' => 'Sipariş Teslim Edildi',
            'body' => "Merhaba :customer_name,\n\nSiparişiniz teslim edildi!\n\nBizi tercih ettiğiniz için teşekkürler.\nGörüşleriniz bizim için değerli.",
        ],
        'order_cancelled' => [
            'title' => 'Sipariş İptal Edildi',
            'body' => "Merhaba :customer_name,\n\nSiparişiniz iptal edildi.\n\nSebep: :reason\n\nSorularınız için bize ulaşabilirsiniz.",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Bildirimleri (FCM)
    |--------------------------------------------------------------------------
    */
    'push' => [
        'new_order' => [
            'title' => 'Yeni Sipariş!',
            'body' => '#:order_number - :total TL - :address',
        ],
        'order_assigned' => [
            'title' => 'Sipariş Atandı!',
            'body' => '#:order_number - :address - :total TL',
        ],
        'pool_order' => [
            'title' => 'Yeni Havuz Siparişi',
            'body' => 'Yeni sipariş: :order_number - :total TL',
        ],
        'order_cancelled' => [
            'title' => 'Sipariş İptal Edildi',
            'body' => '#:order_number siparişi iptal edildi.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-posta Bildirimleri
    |--------------------------------------------------------------------------
    */
    'email' => [
        'welcome' => [
            'subject' => 'Hoş Geldiniz!',
            'greeting' => 'Merhaba :name,',
            'body' => 'Platformumuza hoş geldiniz. Hesabınız başarıyla oluşturuldu.',
        ],
        'password_reset' => [
            'subject' => 'Şifre Sıfırlama',
            'body' => 'Şifrenizi sıfırlamak için aşağıdaki butona tıklayın.',
            'action' => 'Şifremi Sıfırla',
            'expires' => 'Bu link :count dakika içinde geçerliliğini yitirecektir.',
        ],
        'order_confirmation' => [
            'subject' => 'Sipariş Onayı - #:order_number',
            'greeting' => 'Merhaba :name,',
            'body' => 'Siparişiniz başarıyla alındı.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bildirim Ayarları
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'sms_enabled' => 'SMS bildirimleri',
        'whatsapp_enabled' => 'WhatsApp bildirimleri',
        'push_enabled' => 'Push bildirimleri',
        'email_enabled' => 'E-posta bildirimleri',
        'on_order_confirmed' => 'Sipariş onaylandığında',
        'on_order_preparing' => 'Sipariş hazırlanırken',
        'on_courier_assigned' => 'Kurye atandığında',
        'on_order_on_way' => 'Sipariş yola çıktığında',
        'on_order_delivered' => 'Sipariş teslim edildiğinde',
        'on_order_cancelled' => 'Sipariş iptal edildiğinde',
    ],
];
