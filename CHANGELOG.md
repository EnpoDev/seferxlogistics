# SeferX Lojistik - Degisiklik Gunlugu (Changelog)

Tum onemli degisiklikler bu dosyada belgelenmektedir.
Format [Keep a Changelog](https://keepachangelog.com/tr/1.0.0/) standardina uygundur.

---

## [2.1.0] - 2026-03-14

### Yeni Ozellikler (Added)

#### "Subeler" -> "Isletmeler" Yeniden Adlandirma
- Tum sistem genelinde "Sube/Subeler" terminolojisi "Isletme/Isletmeler" olarak degistirildi
- Veritabani tablolari, model isimleri ve iliskiler guncellendi
- UI etiketleri, menu basliklari ve breadcrumb'lar yeni terminolojiye uyumlandi
- API endpoint'leri geriye donuk uyumluluk korunarak guncellendi

#### Kurye Filtreleme (Branch Izolasyonu)
- Bayi panelinde kurye listesi sadece ilgili bayinin kuryelerini gosterir
- Vardiya saatleri ve isletme panelinde kurye filtresi eklendi
- Isletme bazli veri filtreleme altyapisi olusturuldu
- Coklu isletme destegi icin isletme secici eklendi

#### Kurye Yemek Restoranlari Sistemi
- Kuryelerin yemek yiyebilecegi restoranlari tanimlama ozelligi eklendi
- Haftalik yemek plani gorunumu olusturuldu
- `courier_meal_shifts` ve `courier_meal_benefits` tablolari eklendi
- Kurye uygulamasinda haftalik vardiya ve yemek restoranlari gorunumu

#### Yeni Odeme Yontemleri
- Pluxee destegi eklendi
- Edenred destegi eklendi
- Multinet destegi eklendi
- Metropol destegi eklendi
- Tokenflex destegi eklendi
- Setcard destegi eklendi
- Split (bolunmus) odeme destegi eklendi (`split_payment` alani orders tablosuna eklendi)
- Isletme bazinda odeme yontemi yapilandirma sayfasi (`payment_options` branches tablosuna eklendi)

#### Urun Varyasyon Sistemi
- Porsiyon secimi destegi (kucuk, orta, buyuk vb.)
- Ekstra secimler (malzeme ekleme/cikarma)
- Varyasyon bazli fiyatlandirma
- Siparis formunda varyasyon secim arayuzu
- `product_option_groups` ve `product_options` tablolari eklendi
- `ProductController` CRUD endpoint'leri ile varyasyon yonetimi

#### Mahalle/Zone Secimi Siparislerde
- Siparis olusturma formuna mahalle/bolge secimi eklendi
- Zone bazli siparis limitleri tanimlandi (`order_limits` zones tablosuna eklendi)
- `zone_id` alani orders tablosuna eklendi
- Adres dogrulama altyapisi (`address_validation` orders tablosuna eklendi)
- Teslimat ucreti zone bazli otomatik hesaplama

#### Entegrasyon ve Veri Izolasyonu
- Isletme bazli entegrasyon ve kategori izolasyonu saglanarak tum isletmelerde gorunme sorunu giderildi
- `IntegrationController` `stats()` ve `dashboard()` metodlarina `branch_id` filtresi eklendi
- `FinansController` `kuryeKazanc()` ve `subePerformans()` metodlarinda tenant izolasyonu duzeltildi
- Mimari analiz ile veri erisim katmanlari gozden gecirildi

#### Trendyol Otomatik Senkronizasyon (AutoSync)
- Manuel sync tusuna gerek kalmadan otomatik siparis senkronizasyonu
- `trendyol:sync` artisan komutu ve 5 dakikalik scheduler eklendi
- `SyncIntegrationOrders` job her dakika calisarak siparis cekiyor
- Durum degisikliklerinin otomatik olarak Trendyol'a geri bildirilmesi

### Guvenlik Duzeltmeleri (Security)

- **[KRITIK]** SQL Injection duzeltildi: `AdminReportController` icinde `selectRaw()` parameterized query kullanacak sekilde guncellendi
- **[KRITIK]** IDOR duzeltildi: `CustomerController` icinde `destroy()`, `updateAddress()`, `deleteAddress()` metodlarina branch bazli yetki kontrolu eklendi
- **[DUSUK]** IDOR duzeltildi: `OrderController` icinde `edit()`, `update()`, `destroy()`, `updateStatus()` metodlarina `authorizeOrder()` kontrolu eklendi
- **[DUSUK]** `ExternalOrderController` payment_method alani whitelist validation ile sinirlandirildi
- **[DUSUK]** Kurye bildirim sayfasina user_id filtresi eklendi
- **[KRITIK]** `FinancialReportService` branch filtresi duzeltildi - `$userBranchIds` parametresi tum query metodlarinda `whereIn('branch_id')` ile uygulanmakta
- **[KRITIK]** IDOR duzeltildi: `OrderAnalyticsController::branchComparison()` metoduna `validateBranchAccess()` eklendi
- **[ORTA]** Zone siparis limiti backend guard eklendi - `OrderController::store()` icinde limit kontrolu
- **[ORTA]** SettingsController input validation eklendi, ExternalOrderController SSRF korumalari uygulandi
- **[ORTA]** Form endpoint'lerine rate limiting eklendi (siparis, musteri, sifre degistirme)
- **[ORTA]** `IntegrationController` `stats()` ve `dashboard()` Order sorgularina `branch_id` filtresi eklendi
- **[ORTA]** `FinansController` `kuryeKazanc()` ve `subePerformans()` tenant izolasyonu duzeltildi
- **[DUSUK]** `EnsureBranchExists` middleware eklendi: branch'siz bayi kullanicilarinin null branchId ile tum tenant verilerine erismesi engellendi
- Detaylar: `docs/security-findings-2026-03-14.md`

### Duzeltmeler (Fixed)

- Nakit odemeler sekmesinde kurye secimi gorunmuyor sorunu giderildi
- Isletme fiyatlandirmalari (PricingPolicy) calismiyordu, duzeltildi
- Super Admin panelinde kurumsal planlar duzenlenemiyordu, duzeltildi
- Kategoriler ve urunler silinemiyor sorunu giderildi
- Siparis olusturulurken teslimat ucretinin sifirlanma sorunu duzeltildi (`delivery_fee` varsayilan 0, zone limit kontrolu real-time query ile)
- Top products SQL ambiguous column hatasi duzeltildi
- Branch dropdown dark tema arka plan sorunu giderildi
- Branch secici inline style ve dark tema renk uyumsuzluklari duzeltildi
- Tum subelerin isletme secicide gorunmesi saglandi
- Kullanici yonetimi yetkilendirme sorunu giderildi
- Caller ID endpoint Branch modeli ile uyumlu hale getirildi
- Webhook ve guvenlik sorunlari duzeltildi
- Migration'lar SQLite uyumlu hale getirildi
- Impersonation sirasinda session kaybi sorunu giderildi
- Siparis formu field mapping duzeltildi: `$g->is_required` -> `$g->required`, `$o->price_diff` -> `$o->price_modifier`

### Iyilestirmeler (Improved)

- Transaction loglama ve hata yonetimi iyilestirildi
- Webhook retry logic ve secret yonetimi eklendi
- Admin paneli kullanici ve kurye sayfasi iyilestirildi
- Bayi paneli genel iyilestirmeler ve bug fix'ler
- Bayi raporlari sayfasi ve kurye sahiplik kontrolu eklendi
- Isletme olarak giris yap (impersonation) ozelligi eklendi
- Isletme detay sayfasi genisletildi
- Caller ID cihaz loglama ve migration iyilestirmeleri
- Siparis filtreleme, isletme silme ve Caller ID API eklendi
- Kurye performans metrikleri - ortalama teslimat suresi dashboard'a eklendi
- Kurye yemek maliyet raporu (`getMealCostReport`) ve `restaurant_id` kolonu eklendi
- FinancialReportService branch filtresi, split payment ve yeni odeme turleri destegi eklendi
- Urun varyasyon admin UI: `menu.blade.php` AJAX save, siralama ve varsayilan secenek destegi eklendi

### Veritabani Degisiklikleri (Migrations)

| Migration | Aciklama |
|-----------|----------|
| `2026_02_26_174530` | Caller ID cihaz alani branch ayarlarina eklendi |
| `2026_03_02_214957` | Split payment alani orders tablosuna eklendi |
| `2026_03_02_215507` | Address validation alani orders tablosuna eklendi |
| `2026_03_02_220142` | Payment options alani branches tablosuna eklendi |
| `2026_03_02_220706` | `courier_meal_shifts` tablosu olusturuldu |
| `2026_03_02_220711` | `courier_meal_benefits` tablosu olusturuldu |
| `2026_03_03_100823` | Order limits alani zones tablosuna eklendi |
| `2026_03_03_101003` | Zone ID alani orders tablosuna eklendi |
| - | `product_option_groups` tablosu olusturuldu (urun varyasyon gruplari) |
| - | `product_options` tablosu olusturuldu (varyasyon secenekleri) |

---

## [2.0.0] - 2025-01-01

### Guvenlik ve KVKK Uyumluluk Revizyonu

- Rol bazli yetkilendirme (Role-based Authorization) eklendi
- TC Kimlik numarasi API response'larindan kaldirildi (KVKK uyumu)
- Telefon, adres, e-posta, IBAN maskeleme (`PrivacyHelper`) eklendi
- Yapilandirilmis guvenlik ve denetim loglari (`AuditLogService`) eklendi
- API rate limiting (throttle middleware) eklendi
- Guvenlik ve entegrasyon log kanallari olusturuldu (2 yil saklama KVKK gerekliligi)
- Veri erisim ve degisiklik loglari eklendi
- Detaylar: `docs/revised-security-audit.md`

---

## [1.0.0] - Ilk Surum

### Temel Ozellikler

- Isletme Paneli: Siparis yonetimi, musteri yonetimi, urun katalogu
- Bayi Paneli: Coklu isletme ve kurye yonetimi, bolgelendirme, istatistikler
- Kurye Paneli: Mobil uyumlu teslimat yonetimi, havuz sistemi
- Admin Paneli: Sistem geneli yonetim ve izleme
- Trendyol Go entegrasyonu
- Push bildirim sistemi
- Harita uzerinde canli kurye takibi

---

*Bu dosya SeferX Lojistik ekibi tarafindan guncel tutulmaktadir.*
