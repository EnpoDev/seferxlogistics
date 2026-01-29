<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Başarı Mesajları
    |--------------------------------------------------------------------------
    */
    'success' => [
        // Kurye
        'courier_created' => 'Kurye başarıyla eklendi.',
        'courier_updated' => 'Kurye bilgileri güncellendi.',
        'courier_deleted' => 'Kurye silindi.',
        'courier_status_updated' => 'Kurye durumu güncellendi.',
        'courier_assigned' => 'Kurye atandı.',
        'courier_unassigned' => 'Kurye ataması kaldırıldı.',

        // Sipariş
        'order_created' => 'Sipariş başarıyla oluşturuldu.',
        'order_updated' => 'Sipariş güncellendi.',
        'order_deleted' => 'Sipariş silindi.',
        'order_cancelled' => 'Sipariş iptal edildi.',
        'order_status_updated' => 'Sipariş durumu güncellendi.',
        'order_accepted' => 'Sipariş kabul edildi.',
        'order_delivered' => 'Sipariş teslim edildi.',
        'order_assigned' => 'Sipariş kuryeye atandı.',

        // Müşteri
        'customer_created' => 'Müşteri başarıyla oluşturuldu.',
        'customer_updated' => 'Müşteri bilgileri güncellendi.',
        'customer_deleted' => 'Müşteri silindi.',
        'address_added' => 'Adres başarıyla eklendi.',
        'address_updated' => 'Adres güncellendi.',
        'address_deleted' => 'Adres silindi.',

        // Kategori & Ürün
        'category_created' => 'Kategori başarıyla oluşturuldu.',
        'category_updated' => 'Kategori güncellendi.',
        'category_deleted' => 'Kategori silindi.',
        'product_created' => 'Ürün başarıyla oluşturuldu.',
        'product_updated' => 'Ürün güncellendi.',
        'product_deleted' => 'Ürün silindi.',

        // İşletme / Restoran
        'restaurant_created' => 'İşletme başarıyla oluşturuldu.',
        'restaurant_updated' => 'İşletme bilgileri güncellendi.',
        'restaurant_deleted' => 'İşletme silindi.',
        'branch_created' => 'Şube başarıyla eklendi.',
        'branch_updated' => 'Şube başarıyla güncellendi.',
        'branch_deleted' => 'Şube başarıyla silindi.',
        'restaurant_status_updated' => 'Restoran durumu güncellendi.',
        'delivery_time_updated' => 'Teslimat süresi güncellendi.',
        'working_hours_updated' => 'Çalışma saatleri güncellendi.',

        // Tema
        'theme_updated' => 'Tema ayarları başarıyla güncellendi.',

        // Genel ayarlar
        'general_settings_updated' => 'Genel ayarlar başarıyla güncellendi.',
        'default_shift_updated' => 'Varsayılan vardiya ayarları güncellendi.',
        'courier_shift_updated' => 'Seçilen kuryelerin vardiya saatleri güncellendi.',
        'courier_settings_updated' => 'Kurye ayarları başarıyla güncellendi.',
        'app_settings_updated' => 'Uygulama ayarları başarıyla güncellendi.',
        'pool_settings_updated' => 'Havuz ayarları başarıyla güncellendi.',
        'notification_settings_updated' => 'Bildirim ayarları başarıyla güncellendi.',
        'request_approved' => 'İstek onaylandı.',
        'request_rejected' => 'İstek reddedildi.',

        // Bölge
        'zone_created' => 'Bölge başarıyla oluşturuldu.',
        'zone_updated' => 'Bölge güncellendi.',
        'zone_deleted' => 'Bölge silindi.',
        'zone_courier_assigned' => 'Kurye bölgeye atandı.',
        'zone_courier_removed' => 'Kurye bölgeden çıkarıldı.',

        // Entegrasyon
        'integration_connected' => 'Bağlantı başarılı!',
        'integration_disconnected' => 'Bağlantı kesildi.',
        'integration_test_success' => 'Bağlantı testi başarılı!',
        'sync_completed' => 'Senkronizasyon tamamlandı.',

        // Genel
        'settings_saved' => 'Ayarlar kaydedildi.',
        'changes_saved' => 'Değişiklikler kaydedildi.',
        'data_exported' => 'Veriler dışa aktarıldı.',
        'password_changed' => 'Şifre değiştirildi.',
        'profile_updated' => 'Profil güncellendi.',
        'file_uploaded' => 'Dosya yüklendi.',
        'pod_uploaded' => 'Teslimat kanıtı kaydedildi ve sipariş tamamlandı.',

        // Destek
        'ticket_created' => 'Destek talebi oluşturuldu.',
        'ticket_updated' => 'Destek talebi güncellendi.',
        'ticket_closed' => 'Destek talebi kapatıldı.',
        'reply_sent' => 'Yanıt gönderildi.',

        // Vardiya
        'shift_created' => 'Vardiya oluşturuldu.',
        'shift_updated' => 'Vardiya ayarları güncellendi.',
        'shift_deleted' => 'Vardiya silindi.',

        // Konum
        'location_updated' => 'Konum güncellendi.',

        // Kurye uygulama
        'status_updated' => 'Durum güncellendi.',
        'device_token_updated' => 'Cihaz token\'ı güncellendi.',
        'battery_updated' => 'Pil durumu güncellendi.',
        'route_optimized' => 'Rota optimize edildi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hata Mesajları
    |--------------------------------------------------------------------------
    */
    'error' => [
        // Genel
        'generic' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
        'not_found' => 'Kayıt bulunamadı.',
        'unauthorized' => 'Bu işlem için yetkiniz yok.',
        'forbidden' => 'Bu sayfaya erişim izniniz yok.',
        'validation_failed' => 'Doğrulama hatası.',
        'server_error' => 'Sunucu hatası oluştu.',

        // Kurye
        'courier_not_found' => 'Kurye bulunamadı.',
        'courier_not_available' => 'Kurye müsait değil.',
        'courier_max_orders' => 'Kurye maksimum sipariş sayısına ulaştı.',
        'courier_not_on_shift' => 'Mesainiz dışında sipariş kabul edemezsiniz.',
        'courier_offline' => 'Kurye çevrimdışı.',
        'courier_has_active_orders' => 'Bu kuryenin aktif siparişleri var. Önce siparişleri tamamlayın veya başka kuryeye atayın.',

        // Sipariş
        'order_not_found' => 'Sipariş bulunamadı.',
        'order_already_assigned' => 'Sipariş zaten bir kuryeye atanmış.',
        'order_already_taken' => 'Bu sipariş başka bir kurye tarafından alındı.',
        'order_cannot_cancel' => 'Bu sipariş iptal edilemez.',
        'order_cannot_edit' => 'Bu sipariş düzenlenemez.',
        'order_cannot_delete' => 'Sadece beklemedeki veya iptal edilmiş siparişler silinebilir.',
        'order_access_denied' => 'Bu siparişe erişim yetkiniz yok.',
        'order_not_in_pool' => 'Sipariş havuzda değil.',
        'customer_notification_failed' => 'Müşteri bildirim hatası.',

        // Müşteri
        'customer_not_found' => 'Müşteri bulunamadı.',
        'customer_phone_exists' => 'Bu telefon numarası zaten kayıtlı.',

        // Kategori & Ürün
        'category_has_products' => 'Bu kategoriye ait ürünler var. Önce ürünleri silmelisiniz.',
        'product_not_found' => 'Ürün bulunamadı.',

        // Entegrasyon
        'integration_failed' => 'Bağlantı kurulamadı.',
        'integration_test_failed' => 'Bağlantı testi başarısız.',
        'integration_not_found' => 'Entegrasyon bulunamadı.',
        'invalid_platform' => 'Geçersiz platform.',
        'sync_failed' => 'Senkronizasyon başarısız.',
        'status_update_failed' => 'Durum güncellenirken bir hata oluştu.',
        'delivery_time_update_failed' => 'Teslimat süresi güncellenirken bir hata oluştu.',
        'working_hours_update_failed' => 'Çalışma saatleri güncellenirken bir hata oluştu.',
        'category_status_update_failed' => 'Kategori durumu güncellenirken bir hata oluştu.',
        'product_status_update_failed' => 'Ürün durumu güncellenirken bir hata oluştu.',
        'min_time_greater_than_max' => 'Minimum süre maksimum süreden küçük olmalıdır.',
        'select_at_least_one_day' => 'En az bir gün seçilmelidir.',

        // Şube
        'branch_not_found' => 'Şube bulunamadı.',
        'branch_settings_not_found' => 'İşletme ayarları bulunamadı.',

        // Kurye seçimi
        'select_at_least_one_courier' => 'Lütfen en az bir kurye seçin.',
        'courier_has_active_orders_delete' => 'Aktif siparişi olan kurye silinemez.',

        // Dosya
        'file_upload_failed' => 'Dosya yüklenemedi.',
        'invalid_file_type' => 'Geçersiz dosya türü.',
        'file_too_large' => 'Dosya çok büyük.',

        // Bölge
        'zone_coordinates_required' => 'Bölge koordinatları zorunludur. Lütfen haritada bölge çizin.',
        'zone_min_points' => 'Bölge için en az 4 nokta gereklidir.',

        // Vardiya
        'shift_conflict' => 'Bu gün için zaten vardiya mevcut.',
        'shift_rest_required' => 'Önceki vardiya ile arasında en az 8 saat dinlenme süresi olmalı.',

        // Auth
        'invalid_credentials' => 'Geçersiz giriş bilgileri.',
        'account_disabled' => 'Hesabınız devre dışı bırakılmış.',
        'invalid_otp' => 'Geçersiz veya süresi dolmuş doğrulama kodu.',
        'invalid_phone' => 'Geçersiz telefon numarası formatı.',
        'phone_not_found' => 'Bu telefon numarasına ait kayıt bulunamadı.',

        // Erişim
        'unauthorized' => 'Yetkiniz yok.',
        'access_denied' => 'Bu işlem için yetkiniz bulunmuyor.',
        'invalid_status_change' => 'Bu durum değişikliği geçerli değil.',

        // Dosya
        'photo_upload_failed' => 'Fotoğraf yüklenirken bir hata oluştu.',

        // POD
        'pod_not_found' => 'Bu sipariş için teslimat kanıtı bulunamadı.',
        'order_not_ready_for_delivery' => 'Bu sipariş henüz teslimat aşamasında değil.',
        'invalid_order_list' => 'Geçersiz sipariş listesi.',
        'no_orders_to_optimize' => 'Optimize edilecek aktif sipariş bulunmuyor.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bilgi Mesajları
    |--------------------------------------------------------------------------
    */
    'info' => [
        'no_orders' => 'Henüz sipariş bulunmuyor.',
        'no_couriers' => 'Henüz kurye bulunmuyor.',
        'no_customers' => 'Henüz müşteri bulunmuyor.',
        'no_products' => 'Henüz ürün bulunmuyor.',
        'no_zones' => 'Henüz bölge tanımlanmamış.',
        'no_data' => 'Gösterilecek veri yok.',
        'loading' => 'Yükleniyor...',
        'processing' => 'İşleniyor...',
        'please_wait' => 'Lütfen bekleyin...',
        'draw_zone_hint' => 'Haritada bölge çizin. Çizim tamamlandığında bilgi formu açılacak.',
        'zone_drawn' => 'Bölge çizildi! Şimdi bölge bilgilerini girin.',
        'otp_sent' => 'Doğrulama kodu gönderildi.',
        'all_couriers_assigned' => 'Tüm kuryeler atanmış.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Uyarı Mesajları
    |--------------------------------------------------------------------------
    */
    'warning' => [
        'unsaved_changes' => 'Kaydedilmemiş değişiklikler var.',
        'confirm_delete' => 'Bu işlem geri alınamaz. Devam etmek istiyor musunuz?',
        'confirm_cancel' => 'İptal etmek istediğinizden emin misiniz?',
        'low_stock' => 'Düşük stok uyarısı.',
        'pending_orders' => 'Bekleyen siparişler var.',
        'zone_min_points' => 'Bölge için en az 4 nokta gereklidir.',
        'name_required' => 'Ad alanı zorunludur.',
        'select_zone_first' => 'Lütfen önce bir bölge seçin.',
        'courier_not_selected' => 'Kurye seçilmedi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Onay Mesajları
    |--------------------------------------------------------------------------
    */
    'confirm' => [
        'delete_order' => 'Bu siparişi silmek istediğinizden emin misiniz?',
        'delete_courier' => 'Bu kuryeyi silmek istediğinizden emin misiniz?',
        'delete_customer' => 'Bu müşteriyi silmek istediğinizden emin misiniz?',
        'delete_category' => 'Bu kategoriyi silmek istediğinizden emin misiniz?',
        'delete_product' => 'Bu ürünü silmek istediğinizden emin misiniz?',
        'delete_zone' => 'Bu bölgeyi silmek istediğinizden emin misiniz?',
        'cancel_order' => 'Bu siparişi iptal etmek istediğinizden emin misiniz?',
        'logout' => 'Çıkış yapmak istediğinizden emin misiniz?',
    ],

    /*
    |--------------------------------------------------------------------------
    | Abonelik Mesajları
    |--------------------------------------------------------------------------
    */
    'subscription' => [
        'no_subscription' => 'Aktif aboneliğiniz bulunmuyor.',
        'subscription_expired' => 'Aboneliğiniz sona erdi.',
        'subscription_cancelled' => 'Aboneliğiniz iptal edildi.',
        'subscription_trial' => 'Deneme sürümünü kullanıyorsunuz.',
        'feature_not_available' => 'Bu özellik abonelik planınızda bulunmuyor.',
        'upgrade_required' => 'Bu özelliği kullanmak için planınızı yükseltmeniz gerekiyor.',
        'branch_limit_reached' => 'Maksimum işletme sayısına ulaştınız.',
        'inherited_from_bayi' => 'Abonelik bayi üzerinden sağlanıyor.',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Hata Kodları
    |--------------------------------------------------------------------------
    */
    'api' => [
        'unauthenticated' => 'Oturum açmanız gerekiyor.',
        'invalid_token' => 'Geçersiz veya süresi dolmuş token.',
        'rate_limited' => 'Çok fazla istek. :seconds saniye sonra tekrar deneyin.',
        'method_not_allowed' => 'HTTP metodu desteklenmiyor.',
        'not_found' => 'İstenen kaynak bulunamadı.',
        'duplicate_entry' => 'Bu kayıt zaten mevcut.',
        'foreign_key_violation' => 'Bu kayıt başka kayıtlarla ilişkili olduğu için işlem yapılamaz.',
        'database_error' => 'Veritabanı hatası oluştu.',
    ],
];
