# SeferX Lojistik - Guvenlik Denetim Bulgulari

**Tarih:** 2026-03-14 (Son guncelleme: 2026-03-14 - Tum bulgular kapatildi)
**Denetim Kaynaklari:** security-audit agent, customer-agent, lead-integrator
**Durum:** TAMAMLANDI - Tum bulgular duzeltildi

---

## Ozet

| Seviye | Toplam | Duzeltildi | Devam Ediyor |
|--------|--------|------------|--------------|
| Kritik | 5 | 5 | 0 |
| Orta | 6 | 6 | 0 |
| Dusuk | 5 | 5 | 0 |
| **Toplam** | **16** | **16** | **0** |

---

## KRITIK BULGULAR

### K-01: SQL Injection - AdminReportController
- **Dosya:** `app/Http/Controllers/Admin/AdminReportController.php:95-113`
- **Sorun:** `DB::raw()` icinde tarih parametreleri dogrudan SQL sorgusuna yaziliyordu. Saldirgan, tarih parametreleri uzerinden SQL injection yapabilirdi.
- **Oneri:** `selectRaw('...', [$param])` ile parameterized query kullanilmali.
- **Durum:** DUZELTILDI
- **Cozum:** Tum `selectRaw()` cagrilari parameterized hale getirildi. `$startDate` ve `$endDate` degerleri binding parametresi olarak geciriliyor (satir 99-113).

### K-02: IDOR - CustomerController (Yetki Atlama)
- **Dosya:** `app/Http/Controllers/CustomerController.php:123-141, 248-300`
- **Sorun:** `destroy()`, `updateAddress()`, `deleteAddress()` metodlarinda branch bazli yetkilendirme kontrolu yoktu. Herhangi bir oturum acmis kullanici, baska isletmelerin musterilerini silebilir veya adreslerini degistirebilirdi.
- **Oneri:** Her metodda `getActiveBranch()` ile branch bazli erisim kontrolu eklenmeli.
- **Durum:** DUZELTILDI
- **Cozum:** `destroy()` metoduna isletme kullanicisi icin `$customer->orders()->where('branch_id', $activeBranch->id)->exists()` kontrolu eklendi (satir 129-133). `updateAddress()` ve `deleteAddress()` metodlarina adres sahibi musteri erisim kontrolu eklendi.

### K-03: Webhook Imza Dogrulama Middleware Kullanilmiyor
- **Dosya:** `app/Http/Middleware/ValidateWebhookSignature.php`, `routes/web.php:39-41`
- **Sorun:** `ValidateWebhookSignature` middleware yazilmis ancak bazi webhook route'larinda uygulanmiyor. Imza dogrulamasi olmayan endpoint'lere sahte webhook istekleri gonderilebilir.
- **Oneri:** Tum webhook route'larina `ValidateWebhookSignature` middleware eklenmeli. VoIP webhook'lari dahil.
- **Durum:** DUZELTILDI (Gorev #29)
- **Cozum:** Ana webhook route'u (`/webhooks/{platform}/{token}`) `webhook.validate:platform` middleware kullanmakta. VoIP webhook'lari dahil tum route'lara imza dogrulama ve rate limiting eklendi.

### K-04: FinancialReportService Veri Izolasyonu Yok (Tum Finansal Veriler Acik)
- **Dosya:** `app/Services/FinancialReportService.php`, `app/Http/Controllers/Bayi/FinansController.php`
- **Kaynak:** lead-integrator
- **Sorun:** `FinansController` metodlari `$userBranchIds` parametresini `FinancialReportService`'e gonderiyor, ancak servis bu parametreyi **hic kullanmiyor**. Method signature uyumsuzlugu nedeniyle branch filtresi uygulanmiyor. Sonuc: Herhangi bir bayi, **tum bayilerin finansal verilerini** (ciro, kurye odemeleri, nakit akis, isletme performans) gorebiliyor.
- **Oneri:** `FinancialReportService` icindeki tum query metodlarinda `$userBranchIds` parametresini `whereIn('branch_id', $userBranchIds)` filtresi olarak uygulanmali.
- **Durum:** DUZELTILDI (Gorev #40)
- **Cozum:** `FinancialReportService` icindeki tum query metodlarinda `$userBranchIds` parametresi `whereIn('branch_id', $userBranchIds)` filtresi olarak uygulandi. Split payment ve yeni odeme turleri destegi de eklendi.
- **Etki:** Yuksek - Tum bayilerin finansal verileri ifsa olabilir. Rekabet ve ticari sir ihlali riski.

### K-05: OrderAnalyticsController branchComparison() IDOR
- **Dosya:** `app/Http/Controllers/Bayi/OrderAnalyticsController.php`
- **Kaynak:** security-audit
- **Sorun:** Controller'daki 8 metoddan 7'sinde `validateBranchAccess()` kontrolu var, ancak `branchComparison()` metodunda bu kontrol **eksik**. Saldirgan, URL'de baska bayilerin branch ID'lerini gonderek isletme karsilastirma verilerine erisebilir.
- **Oneri:** `branchComparison()` metodunun basina `$this->validateBranchAccess()` cagrisi eklenmeli.
- **Durum:** DUZELTILDI (Gorev #41)
- **Etki:** Orta-Yuksek - Baska bayilerin isletme performans verilerine yetkisiz erisim.

---

## ORTA SEVIYE BULGULAR

### O-01: SettingsController Input Validation Eksikligi
- **Dosya:** `app/Http/Controllers/SettingsController.php:31-43, 56-68`
- **Sorun:** `updateApplication()` ve `updatePayment()` metodlarinda `$request->validate()` cagirilmiyor. `$request->input()` ile alinan degerler dogrudan veritabanina yaziliyor. Ornegin `language`, `timezone`, `currency`, `payment_provider`, `min_order_amount`, `max_cash_amount` alanlari dogrulanmadan kaydediliyor.
- **Oneri:**
  - `language`: `in:tr,en` gibi whitelist ile sinirlanmali
  - `timezone`: `timezone` validation rule kullanilmali
  - `currency`: `in:TRY,USD,EUR` ile sinirlanmali
  - `default_order_timeout` / `default_preparation_time`: `integer|min:1|max:120` ile sinirlanmali
  - `payment_provider`: `in:iyzico,stripe,...` whitelist eklenmeli
  - `min_order_amount` / `max_cash_amount`: `numeric|min:0` eklenmeli
- **Durum:** DUZELTILDI (Gorev #27)

### O-02: ExternalOrderController SSRF Riski
- **Dosya:** `app/Http/Controllers/Api/ExternalOrderController.php:346`
- **Sorun:** `webhook_url` alani sadece `url` validation rule'u ile dogrulaniyor. Saldirgan, dahili IP adreslerine (orn. `http://169.254.169.254`, `http://localhost`, `http://10.0.0.1`) yonlendirme yaparak SSRF (Server-Side Request Forgery) saldirisi gerceklestirebilir.
- **Oneri:** URL dogrulamasina ek olarak:
  - Ozel/dahili IP araliklari engellenmeli (10.x, 172.16-31.x, 192.168.x, 127.x, 169.254.x)
  - Sadece HTTPS protokolu kabul edilmeli
  - DNS rebinding korumalari eklenmeli
- **Durum:** DUZELTILDI (Gorev #27)

### O-03: Webhook Route'lari CSRF Korumasiz
- **Dosya:** `routes/web.php:39-47`
- **Sorun:** Webhook endpoint'leri web.php icinde tanimlandigi icin CSRF korumasina tabidir, ancak ucuncu parti platformlardan gelen isteklerde CSRF token bulunamaz. Bu route'larin CSRF exception listesine eklenmesi veya api.php'ye tasinmasi gerekir.
- **Oneri:** Webhook route'lari `VerifyCsrfToken` middleware exception listesine eklenmeli veya `routes/api.php` dosyasina tasinmali.
- **Durum:** DUZELTILDI - Ana webhook route'u `webhook.validate:platform` middleware ile calisiyor. CSRF exception yapilandirmasi tamamlandi.

### O-04: Form Submission Rate Limiting Eksik
- **Dosya:** Genel - form endpoint'leri
- **Sorun:** Oturum acma, siparis olusturma, musteri kayit gibi form submission endpoint'lerinde rate limiting uygulanmiyor. Brute-force saldirisi veya spam riski mevcut.
- **Oneri:**
  - Login endpoint'i: `throttle:5,1` (dakikada 5 istek)
  - Siparis olusturma: `throttle:30,1`
  - Musteri kayit: `throttle:10,1`
  - Sifre degistirme: `throttle:3,1`
- **Durum:** DUZELTILDI (Gorev #27)

### O-05: Zone Siparis Limiti Backend Guard Eksik
- **Dosya:** `app/Http/Controllers/OrderController.php` (store metodu)
- **Kaynak:** lead-integrator
- **Sorun:** Zone bazli siparis limitleri sadece frontend (JavaScript) tarafinda kontrol ediliyor. Backend'de `OrderController::store()` metodunda zone siparis limitinin asilip asilmadigina dair bir kontrol bulunmuyor. Saldirgan veya hata durumunda API uzerinden limitin uzerinde siparis olusturulabilir.
- **Oneri:** `OrderController::store()` metodunda siparis olusturulmadan once `zone.daily_order_count >= zone.order_limit` kontrolu eklenmeli. Limit asilmissa `422 Validation Error` dondurulmeli.
- **Durum:** DUZELTILDI (Gorev #41)
- **Etki:** Orta - Operasyonel kapasite asimi, teslimat kalitesi dususu.

### O-06: IntegrationController Veri Izolasyonu Eksik
- **Dosya:** `app/Http/Controllers/IntegrationController.php` (`stats()`, `dashboard()` metodlari)
- **Kaynak:** lead-integrator
- **Sorun:** `IntegrationController` icindeki `stats()` ve `dashboard()` metodlarindaki Order sorgulari `branch_id` filtresi icermiyor. Sonuc: Entegrasyon istatistikleri ve dashboard verileri tum isletmelerin siparislerini kapsiyordu.
- **Oneri:** Order sorgularina `where('branch_id', $branchId)` filtresi eklenmeli.
- **Durum:** DUZELTILDI (Gorev #38)
- **Cozum:** `stats()` ve `dashboard()` metodlarindaki Order sorgularina `branch_id` filtresi eklendi.
- **Etki:** Orta - Baska isletmelerin entegrasyon verilerine yetkisiz erisim.

---

## DUSUK SEVIYE BULGULAR

### D-01: ExternalOrderController Payment Method Validation
- **Dosya:** `app/Http/Controllers/Api/ExternalOrderController.php:38`
- **Sorun:** `payment_method` alani sadece `required|string` ile dogrulaniyordu, herhangi bir deger kabul edilebiliyordu.
- **Oneri:** Whitelist validation: `in:cash,card,online,pluxee,edenred,multinet,metropol,tokenflex,setcard`
- **Durum:** DUZELTILDI
- **Cozum:** Validation rule `in:cash,card,online,pluxee,edenred,multinet,metropol,tokenflex,setcard` olarak guncellendi (satir 38).

### D-02: IDOR - OrderController (Siparis Duzenleme)
- **Dosya:** `app/Http/Controllers/OrderController.php:460, 497, 637`
- **Sorun:** `edit()`, `update()`, `destroy()` metodlarinda `applyBranchFilter` kullanilmiyordu, baska isletmenin siparisi ID ile duzenlenebilirdi.
- **Oneri:** Her metodda siparis branch kontrolu eklenmeli.
- **Durum:** DUZELTILDI
- **Cozum:** `authorizeOrder()` private metodu olusturuldu ve `edit()` (satir 462), `update()` (satir 499), `destroy()` (satir 639), `updateStatus()` (satir 671) metodlarinda kullanilmakta. Bu metod `getUserBranchIds()` ile branch bazli yetki kontrolu yapiyor.

### D-03: Kurye Bildirim Sayfasi User Filtresi
- **Dosya:** Kurye bildirim sayfasi
- **Sorun:** Kurye bildirim sayfasinda user_id filtresi eksikti, baska kuryelerin bildirimlerini gorebilme riski vardi.
- **Oneri:** `user_id` bazli filtreleme eklenmeli.
- **Durum:** DUZELTILDI (commit `8af7c7c`)

### D-04: Multi-Tenant Veri Izolasyonu Eksiklikleri
- **Dosya:** Genel - controller katmani
- **Sorun:** Bazi controller'larda multi-tenant veri izolasyonu tam saglanmiyor. Entegrasyonlar ve kategoriler tum isletmelerde gorunebiliyordu.
- **Oneri:** Tum veri erisim noktalarinda branch/bayi bazli filtreleme uygulanmali.
- **Durum:** DUZELTILDI (Gorev #38)
- **Cozum:** `CategoryController`, `ProductController`, `IntegrationController` tenant filtreleri eklendi. `IntegrationController` `stats()` ve `dashboard()` branch-aware hale getirildi. `EnsureBranchExists` middleware ile branch'siz kullanicilarin erisimi engellendi.

### D-05: Branch'siz Bayi Kullanicisi Tenant Bypass
- **Dosya:** `app/Http/Middleware/EnsureBranchExists.php` (yeni), `bootstrap/app.php`, `routes/web.php`
- **Kaynak:** lead-integrator
- **Sorun:** Branch'i olmayan (silinmis veya atanmamis) bir bayi kullanicisi sisteme giris yaptiginda, `branchId` degeri `null` olarak kaliyordu. Bu durumda tenant filtresi (`where('branch_id', null)`) gecersiz hale gelerek tum verilere erisim sagliyordu.
- **Oneri:** Bayi route grubuna branch varligini kontrol eden middleware eklenmeli. Branch yoksa kullanici panele erisememeli.
- **Durum:** DUZELTILDI
- **Cozum:** `EnsureBranchExists` middleware olusturuldu. Bayi route grubu artik `['role:bayi', 'ensure.branch']` middleware zincirine sahip. Branch'siz kullanicilar hata sayfasina yonlendiriliyor.
- **Etki:** Dusuk-Orta - Nadir senaryo (branch silinmis kullanici), ancak gerceklestiginde tum tenant verileri aciga cikabilir.

---

## Onceki Denetim Bulgulari (v2.0.0 - 2025-01-01)

Asagidaki bulgular onceki guvenlik denetiminde tespit edilmis ve duzeltilmistir. Detaylar icin: `docs/revised-security-audit.md`

| Bulgu | Durum |
|-------|-------|
| TC Kimlik API'de acik | DUZELTILDI |
| API yetkilendirme eksik | DUZELTILDI |
| Telefon/Adres maskeleme yok | DUZELTILDI |
| Log'larda PII (kisisel veri) | DUZELTILDI |
| Rate limiting yok (API) | DUZELTILDI |

---

## Oneriler ve Sonraki Adimlar

### Acil (Bu Sprint) - TAMAMLANDI
1. ~~**[KRITIK]** FinancialReportService branch filtresi~~ - DUZELTILDI (K-04, Gorev #40)
2. ~~VoIP webhook route'larina imza dogrulama~~ - DUZELTILDI (K-03, Gorev #29)
3. ~~OrderAnalyticsController branchComparison() IDOR~~ - DUZELTILDI (K-05)
4. ~~Zone siparis limiti backend guard~~ - DUZELTILDI (O-05)
5. ~~SettingsController input validation~~ - DUZELTILDI (O-01)
6. ~~ExternalOrderController SSRF korumalari~~ - DUZELTILDI (O-02)
7. ~~Form endpoint'lerine rate limiting~~ - DUZELTILDI (O-04)

### Kisa Vade (Sonraki Sprint)
1. ~~Webhook route'lari CSRF exception~~ - DUZELTILDI (O-03)
2. ~~Multi-tenant veri izolasyonu~~ - DUZELTILDI (D-04, Gorev #38) + EnsureBranchExists middleware (D-05)
3. Content Security Policy (CSP) header'lari eklenmeli
4. Dependency audit (composer audit, npm audit)

### Uzun Vade
1. Otomatik guvenlik test suite'i (OWASP ZAP, PHPStan security rules)
2. Penetrasyon testi plani
3. KVKK uyumluluk denetimi (yillik)

---

*Bu dokuman SeferX Lojistik teknik ekibi icin hazirlanmistir. Guvenlik sorunlari icin: security@seferx.com*
*Sonraki denetim: 2026-06-14 (3 ay sonra)*
