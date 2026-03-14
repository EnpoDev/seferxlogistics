# SeferX Lojistik - API Endpoint Referansi

**Tarih:** 2026-03-14
**Versiyon:** 2.1.0
**Base URL:** `https://app.seferx.com`

---

## Icindekiler

1. [External API (OAuth2)](#1-external-api-oauth2-korumalı)
2. [Public API](#2-public-api-kimlik-dogrulama-gerektirmez)
3. [Caller ID API](#3-caller-id-api)
4. [OAuth2 Yetkilendirme](#4-oauth2-yetkilendirme)
5. [Webhook Endpoint'leri](#5-webhook-endpointleri)
6. [Isletme Panel Endpoint'leri](#6-isletme-panel-endpointleri)
7. [Bayi Panel Endpoint'leri](#7-bayi-panel-endpointleri)
8. [Kurye Uygulama Endpoint'leri](#8-kurye-uygulama-endpointleri)
9. [Admin Panel Endpoint'leri](#9-admin-panel-endpointleri)
10. [Musteri Portali](#10-musteri-portali)

---

## 1. External API (OAuth2 Korumali)

**Prefix:** `/api/external`
**Auth:** OAuth2 Bearer Token (`auth:api`)
**Rate Limit:** 60 istek/dakika

Dis platformlar (orn. seferxyemek) tarafindan kullanilir.

### 1.1 Siparis Yonetimi

#### POST /api/external/orders
Yeni siparis olustur.

**Request Body:**
```json
{
  "external_order_id": "TRY-12345",
  "restaurant_id": "conn_abc123",
  "customer_name": "Ahmet Yilmaz",
  "customer_phone": "05321234567",
  "customer_address": "Kadikoy, Istanbul",
  "items": [
    { "name": "Adana Kebap", "quantity": 2, "price": 150.00 }
  ],
  "subtotal": 300.00,
  "delivery_fee": 25.00,
  "total": 325.00,
  "payment_method": "cash|card|online|pluxee|edenred|multinet|metropol|tokenflex|setcard",
  "is_paid": false,
  "notes": "Kapida nakit",
  "scheduled_at": "2026-03-14T19:00:00"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "order": {
    "id": 456,
    "order_number": "SFX-2026-0456",
    "status": "pending",
    "tracking_token": "abc123def456"
  }
}
```

#### GET /api/external/orders/{externalOrderId}
Siparis detayini getir.

**Response:** `200 OK` - Siparis detayi (order_number, status, items, courier, timestamps)

#### PATCH /api/external/orders/{externalOrderId}/status
Siparis durumunu guncelle.

**Request Body:**
```json
{
  "status": "preparing|ready|on_delivery|delivered|cancelled"
}
```

#### POST /api/external/orders/{externalOrderId}/cancel
Siparisi iptal et.

**Request Body:**
```json
{
  "reason": "Musteri talebi"
}
```

### 1.2 Restoran Baglantilari

#### GET /api/external/restaurants
Bagli restoranlari listele.

#### PATCH /api/external/restaurants/{connectionId}/settings
Restoran baglanti ayarlarini guncelle.

**Request Body:**
```json
{
  "auto_accept": true,
  "webhook_url": "https://example.com/webhook",
  "settings": {}
}
```

### 1.3 Webhook Secret Yonetimi

#### GET /api/external/restaurants/{connectionId}/webhook-secret
Webhook secret anahtarini getir.

#### POST /api/external/restaurants/{connectionId}/webhook-secret/regenerate
Webhook secret anahtarini yeniden olustur.

---

## 2. Public API (Kimlik Dogrulama Gerektirmez)

**Prefix:** `/api/public`
**Rate Limit:** 120 istek/dakika

### GET /api/public/track/{trackingToken}
Siparis takip bilgisini getir.

**Response:** `200 OK`
```json
{
  "order_number": "SFX-2026-0456",
  "status": "on_delivery",
  "courier": { "name": "Mehmet" },
  "estimated_delivery": "2026-03-14T19:30:00"
}
```

### Siparis Takip Sayfalari (Web)

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/takip` | Takip arama sayfasi |
| POST | `/takip/ara` | Siparis numarasi ile ara |
| GET | `/tracking/{token}` | Takip sayfasi |
| GET | `/tracking/{token}/data` | Takip verisi (JSON) |

---

## 3. Caller ID API

### 3.1 API Key Korumali
**Prefix:** `/api/caller-id`
**Auth:** API Key (`api.key` middleware)
**Rate Limit:** 60 istek/dakika

#### GET /api/caller-id/lookup
Telefon numarasina gore musteri ara.

**Query Params:** `?phone=05321234567`

#### GET /api/caller-id/sync
Cevrimdisi depolama icin musteri verilerini senkronize et.

### 3.2 Cihaz Endpoint'leri (Public)
**Prefix:** `/api/cagri`
**Rate Limit:** 30 istek/dakika

#### GET /api/cagri/al/{branchId}
Fiziksel Caller ID cihazindan gelen cagriyi al.

**Query Params:** `?no=05321234567&DeviceID=xxx&DateTime=xxx&Line=1&str0=xxx&str1=xxx`

---

## 4. OAuth2 Yetkilendirme

**Prefix:** `/oauth`

| Metod | URL | Auth | Aciklama |
|-------|-----|------|----------|
| GET | `/oauth/authorize` | - | Yetkilendirme baslat |
| GET | `/oauth/authorize/show` | Session | Yetkilendirme onay sayfasi |
| POST | `/oauth/authorize/approve` | Session | Yetkilendirmeyi onayla |
| POST | `/oauth/authorize/deny` | Session | Yetkilendirmeyi reddet |
| POST | `/oauth/token` | - | Access token al (rate limit: 10/dk) |
| POST | `/oauth/revoke` | Session | Token iptal et |

**Token Alma Ornegi:**
```bash
curl -X POST https://app.seferx.com/oauth/token \
  -d "grant_type=authorization_code" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  -d "code=AUTHORIZATION_CODE" \
  -d "redirect_uri=https://yourapp.com/callback"
```

---

## 5. Webhook Endpoint'leri

### POST /webhooks/{platform}/{token}
Ucuncu parti platform webhook'lari.

**Auth:** Webhook imza dogrulama (`webhook.validate:platform`)
**Rate Limit:** 30 istek/dakika
**Desteklenen Platformlar:** trendyol, getir, yemeksepeti

### POST /voip/webhook
VoIP arama webhook'u.

**Rate Limit:** 60 istek/dakika

### POST /voip/connect/{callLogId}
VoIP baglanti webhook'u.

**Rate Limit:** 60 istek/dakika

---

## 6. Isletme Panel Endpoint'leri

**Auth:** Session (auth middleware)
**Rol:** isletme

### 6.1 Dashboard

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/dashboard` | Ana sayfa |
| GET | `/harita` | Canli harita (Livewire) |

### 6.2 Siparis Yonetimi

| Metod | URL | Rate Limit | Aciklama |
|-------|-----|------------|----------|
| GET | `/siparis` | - | Siparis listesi |
| GET | `/siparis/create` | - | Siparis olusturma formu |
| POST | `/siparis` | 30/dk | Siparis kaydet |
| GET | `/siparis/{order}/edit` | - | Siparis duzenleme formu |
| PUT | `/siparis/{order}` | 30/dk | Siparis guncelle |
| DELETE | `/siparis/{order}` | 30/dk | Siparis sil |
| PATCH | `/siparis/{order}/status` | 60/dk | Durum guncelle |
| GET | `/siparis/gecmis` | - | Siparis gecmisi |
| GET | `/siparis/iptal` | - | Iptal edilen siparisler |
| GET | `/siparis/istatistik` | - | Siparis istatistikleri |

### 6.3 Musteri Yonetimi

| Metod | URL | Rate Limit | Aciklama |
|-------|-----|------------|----------|
| GET | `/musteri` | - | Musteri listesi |
| POST | `/musteri` | 30/dk | Musteri ekle |
| GET | `/musteri/{customer}` | - | Musteri detay |
| PUT | `/musteri/{customer}` | 30/dk | Musteri guncelle |
| DELETE | `/musteri/{customer}` | 30/dk | Musteri sil |
| POST | `/musteri/search-phone` | 60/dk | Telefon ile ara |
| POST | `/musteri/quick-store` | 30/dk | Hizli musteri ekle |
| POST | `/musteri/{customer}/address` | 30/dk | Adres ekle |
| PUT | `/musteri/address/{address}` | 30/dk | Adres guncelle |
| DELETE | `/musteri/address/{address}` | 30/dk | Adres sil |

### 6.4 Restoran Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/restoran` | Restoran listesi |
| GET | `/restoran/create` | Restoran ekleme formu |
| POST | `/restoran` | Restoran kaydet |
| GET | `/restoran/{restaurant}` | Restoran detay |
| GET | `/restoran/{restaurant}/edit` | Restoran duzenleme |
| PUT | `/restoran/{restaurant}` | Restoran guncelle |
| DELETE | `/restoran/{restaurant}` | Restoran sil |
| PATCH | `/restoran/{restaurant}/toggle-featured` | One cikar/geri al |
| POST | `/restoran/{restaurant}/categories` | Kategori eslestir |

### 6.5 Kategori ve Urun Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/kategori` | Kategori listesi |
| POST | `/kategori/{category}/restaurants` | Restoran eslestir |
| GET/POST/PUT/DELETE | `/categories` | Kategori CRUD (resource) |
| GET/POST/PUT/DELETE | `/products` | Urun CRUD (resource) |
| GET | `/yonetim/urunler` | Urun listesi (yonetim) |
| POST | `/yonetim/urunler` | Urun ekle |
| PUT | `/yonetim/urunler/{product}` | Urun guncelle |
| DELETE | `/yonetim/urunler/{product}` | Urun sil |

### 6.6 Entegrasyonlar

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/yonetim/entegrasyonlar` | Entegrasyon listesi |
| POST | `/integrations/{platform}/connect` | Platform bagla |
| POST | `/integrations/{platform}/disconnect` | Baglanti kes |
| POST | `/integrations/{platform}/test` | Baglanti test et |
| POST | `/integrations/{platform}/sync` | Siparis senkronize et |
| GET | `/integrations/dashboard` | Entegrasyon dashboard |
| GET | `/integrations/{platform}/stats` | Platform istatistikleri |

### 6.7 Kurye Yonetimi (Isletme)

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/isletmem/kuryeler` | Kurye listesi |
| POST | `/isletmem/kuryeler` | Kurye ekle |
| GET | `/isletmem/kuryeler/create` | Kurye ekleme formu |
| GET | `/isletmem/kuryeler/{courier}/edit` | Kurye duzenleme |
| PUT | `/isletmem/kuryeler/{courier}` | Kurye guncelle |
| DELETE | `/isletmem/kuryeler/{courier}` | Kurye sil |
| PATCH | `/isletmem/kuryeler/{courier}/status` | Durum guncelle |
| PATCH | `/isletmem/kuryeler/{courier}/shift` | Vardiya guncelle |
| GET | `/isletmem/kuryeler/{courier}/check-shift` | Vardiya kontrol |
| GET | `/isletmem/kuryeler/available` | Musait kuryeler |
| GET | `/isletmem/kuryeler/stats` | Kurye istatistikleri |
| PATCH | `/isletmem/kuryeler/{courier}/location` | Konum guncelle |

### 6.8 Dahili API Endpoint'leri

**Rate Limit:** 60 istek/dakika (tum endpoint'ler)

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/api/couriers/search` | Kurye ara |
| GET | `/api/orders/search` | Siparis ara |
| GET | `/api/couriers/{courier}` | Kurye detay |
| GET | `/api/map-data` | Harita verisi |
| GET | `/api/isletmem/recent-calls` | Son aramalar |

### 6.9 Ayarlar

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/ayarlar/genel` | Genel ayarlar |
| POST | `/ayarlar/genel` | Genel ayarlar guncelle |
| POST | `/ayarlar/business` | Isletme bilgileri guncelle |
| POST | `/ayarlar/password` | Sifre degistir |
| GET/POST | `/ayarlar/uygulama` | Uygulama ayarlari |
| GET/POST | `/ayarlar/odeme` | Odeme ayarlari |
| GET/POST | `/ayarlar/bildirim` | Bildirim ayarlari |
| GET/POST | `/tema` | Tema ayarlari |
| GET | `/ayarlar/yazici` | Yazici ayarlari |
| POST | `/ayarlar/yazici` | Yazici ekle |
| PUT | `/ayarlar/yazici/{printer}` | Yazici guncelle |
| DELETE | `/ayarlar/yazici/{printer}` | Yazici sil |
| POST | `/ayarlar/yazici/{printer}/test` | Yazici test |
| GET/POST | `/ayarlar/yazarkasa` | Yazar kasa ayarlari |
| POST | `/ayarlar/yazarkasa/test` | Yazar kasa test |

### 6.10 Billing (Abonelik)

| Metod | URL | Aciklama |
|-------|-----|----------|
| POST | `/billing/subscribe/{plan}` | Plan abone ol |
| POST | `/billing/subscription/cancel` | Abonelik iptal |
| POST | `/billing/subscription/upgrade/{plan}` | Plan yukselt |
| POST | `/billing/cards` | Kart ekle |
| POST | `/billing/cards/{card}/default` | Varsayilan kart |
| DELETE | `/billing/cards/{card}` | Kart sil |
| GET | `/billing/invoice/{transaction}` | Fatura indir |

### 6.11 Destek

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/destek` | Destek talepleri |
| POST | `/destek` | Talep olustur |
| GET | `/destek/{ticket}` | Talep detay |
| POST | `/destek/{ticket}/reply` | Yanit gonder |
| POST | `/destek/{ticket}/close` | Talebi kapat |
| POST | `/destek/{ticket}/reopen` | Talebi yeniden ac |

---

## 7. Bayi Panel Endpoint'leri

**Prefix:** `/bayi`
**Auth:** Session (auth + role:bayi middleware)

### 7.1 Harita

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/harita` | Canli harita |

### 7.2 Kurye Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/kuryelerim` | Kurye listesi |
| GET | `/bayi/kuryelerim/yeni` | Kurye ekleme formu |
| POST | `/bayi/kuryelerim` | Kurye kaydet |
| GET | `/bayi/kuryelerim/{courier}/duzenle` | Kurye duzenleme |
| GET | `/bayi/kuryelerim/{courier}` | Kurye detay |
| PUT | `/bayi/kuryelerim/{courier}/ayarlar` | Kurye ayarlar guncelle |
| DELETE | `/bayi/kuryelerim/{courier}` | Kurye sil |
| POST | `/bayi/kurye-pricing-policy-olustur` | Fiyat politikasi olustur |
| DELETE | `/bayi/pricing-policy/{pricingPolicy}` | Fiyat politikasi sil |
| POST | `/bayi/kuryelerim/{courier}/pricing-policy` | Kuryeye politika ata |
| GET | `/bayi/kuryelerim/{courier}/mesai-logs` | Mesai loglari |
| GET | `/bayi/kuryelerim/{courier}/past-orders` | Gecmis siparisler |
| GET | `/bayi/kuryelerim/{courier}/statistics` | Kurye istatistikleri |
| POST | `/bayi/kuryeler/{courier}/sifre` | Kurye sifre ayarla |
| POST | `/bayi/kuryeler/{courier}/app-toggle` | Uygulama erisimi toggle |

### 7.3 Isletme Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/isletmelerim` | Isletme listesi |
| GET | `/bayi/isletmelerim/yeni` | Isletme ekleme formu |
| POST | `/bayi/isletmelerim` | Isletme kaydet |
| GET | `/bayi/isletmelerim/{branch}` | Isletme detay |
| GET | `/bayi/isletmelerim/{branch}/duzenle` | Isletme duzenleme |
| PUT | `/bayi/isletmelerim/{branch}` | Isletme guncelle |
| DELETE | `/bayi/isletmelerim/{branch}` | Isletme sil |
| POST | `/bayi/isletmelerim/{branch}/giris` | Isletme olarak giris (impersonation) |
| POST | `/bayi/isletmelerim/{branch}/ayarlar` | Isletme ayarlar |
| POST | `/bayi/isletmelerim/{branch}/caller-id-ayarlar` | Caller ID ayarlar |
| POST | `/bayi/isletmelerim/{branch}/bakiye-ekle` | Bakiye ekle |
| GET | `/bayi/isletmelerim/{branch}/siparisler` | Isletme siparisleri |
| GET | `/bayi/isletmelerim/{branch}/istatistikler` | Isletme istatistikleri |
| GET | `/bayi/isletmelerim/{branch}/detayli-istatistikler` | Detayli istatistikler |

### 7.4 Fiyatlandirma Politikalari

| Metod | URL | Aciklama |
|-------|-----|----------|
| POST | `/bayi/isletmelerim/{branch}/pricing-policies` | Politika olustur |
| PUT | `/bayi/isletmelerim/{branch}/pricing-policies/{policy}` | Politika guncelle |
| DELETE | `/bayi/isletmelerim/{branch}/pricing-policies/{policy}` | Politika sil |
| POST | `/bayi/pricing-policies/{policy}/rules` | Kural ekle |
| PUT | `/bayi/pricing-policy-rules/{rule}` | Kural guncelle |
| DELETE | `/bayi/pricing-policy-rules/{rule}` | Kural sil |

### 7.5 Vardiya Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/vardiya-saatleri` | Vardiya saatleri |
| POST | `/bayi/vardiya-saatleri/varsayilan` | Varsayilan vardiya kaydet |
| POST | `/bayi/vardiya-saatleri/toplu-guncelle` | Toplu guncelle |
| POST | `/bayi/vardiya-saatleri/{courier}` | Kurye vardiyasi guncelle |
| DELETE | `/bayi/vardiya-saatleri/{courier}/sil` | Vardiya sil |
| POST | `/bayi/vardiya-saatleri/{courier}/kopyala` | Vardiya kopyala |
| POST | `/bayi/vardiya-saatleri/{courier}/sablon-uygula` | Sablon uygula |

### 7.6 Yemek Vardiyalari (Yeni)

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/yemek-vardiyalari` | Yemek vardiyalari listesi |
| POST | `/bayi/yemek-vardiyalari` | Yemek vardiyasi olustur |
| PUT | `/bayi/yemek-vardiyalari/{mealShift}` | Yemek vardiyasi guncelle |
| DELETE | `/bayi/yemek-vardiyalari/{mealShift}` | Yemek vardiyasi sil |

### 7.7 Zone/Bolgelendirme

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/bolgelendirme` | Bolgelendirme sayfasi |
| POST | `/bayi/zones` | Zone olustur |
| PUT | `/bayi/zones/{zone}` | Zone guncelle |
| DELETE | `/bayi/zones/{zone}` | Zone sil |
| POST | `/bayi/zones/{zone}/courier` | Zone'a kurye ata |
| DELETE | `/bayi/zones/{zone}/courier/{courier}` | Zone'dan kurye cikar |
| GET | `/bayi/zones/api` | Zone verileri (JSON) |
| GET | `/bayi/zones/{zone}/details` | Zone detay |

### 7.8 Havuz (Pool)

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/havuz` | Havuz dashboard |
| POST | `/bayi/havuz/{order}/ata` | Siparis kurye ata |
| POST | `/bayi/havuz/{order}/otomatik-ata` | Otomatik atama |
| POST | `/bayi/havuz/toplu-ata` | Toplu atama |
| GET | `/bayi/havuz/istatistik` | Havuz istatistikleri |

### 7.9 Odemeler

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/odemeler/kurye` | Kurye odemeleri |
| POST | `/bayi/odemeler/kurye` | Kurye odeme kaydet |
| GET | `/bayi/nakit-odemeler` | Nakit odemeler |
| POST | `/bayi/nakit-odemeler` | Nakit odeme kaydet |
| POST | `/bayi/nakit-odemeler/{transaction}/cancel` | Nakit odeme iptal |
| GET | `/bayi/nakit-odemeler/{courier}/history` | Nakit odeme gecmisi |
| GET | `/bayi/odemeler/isletme` | Isletme odemeleri |
| POST | `/bayi/odemeler/isletme/{branch}` | Isletme odeme kaydet |
| GET | `/bayi/odemeler/isletme/rapor` | Isletme odeme rapor |

### 7.10 Bayi Ayarlari

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET/POST | `/bayi/ayarlar/genel` | Genel ayarlar |
| GET/POST | `/bayi/ayarlar/kurye` | Kurye ayarlari |
| GET/POST | `/bayi/ayarlar/uygulama` | Uygulama ayarlari |
| GET/POST | `/bayi/ayarlar/havuz` | Havuz ayarlari |
| GET/POST | `/bayi/ayarlar/bildirim` | Bildirim ayarlari |
| GET/POST | `/bayi/ayarlar/odeme-yontemleri` | Odeme yontemi ayarlari (Yeni) |
| GET/POST | `/bayi/tema` | Tema ayarlari |

### 7.11 Trendyol Entegrasyonu

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/ayarlar/trendyol` | Trendyol ayarlari |
| POST | `/bayi/ayarlar/trendyol/status` | Trendyol durum guncelle |
| POST | `/bayi/ayarlar/trendyol/delivery-time` | Teslimat suresi guncelle |
| POST | `/bayi/ayarlar/trendyol/working-hours` | Calisma saatleri guncelle |
| POST | `/bayi/ayarlar/trendyol/section-status` | Bolum durumu guncelle |
| POST | `/bayi/ayarlar/trendyol/product-status` | Urun durumu guncelle |
| GET | `/bayi/trendyol/orders` | Trendyol siparisleri |
| POST | `/bayi/trendyol/orders/accept` | Siparisi kabul et |
| POST | `/bayi/trendyol/orders/prepare` | Hazirlama baslat |
| POST | `/bayi/trendyol/orders/ship` | Kargoya ver |
| POST | `/bayi/trendyol/orders/deliver` | Teslim et |
| POST | `/bayi/trendyol/orders/cancel` | Iptal et |
| POST | `/bayi/trendyol/orders/invoice` | Fatura gonder |

### 7.12 Siparisler, Istatistikler ve Diger

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/siparisler/gecmis` | Gecmis siparisler |
| GET | `/bayi/siparisler/bedelsiz` | Bedelsiz istekler |
| POST | `/bayi/siparisler/bedelsiz/{order}/approve` | Bedelsiz onayla |
| POST | `/bayi/siparisler/bedelsiz/{order}/reject` | Bedelsiz reddet |
| GET | `/bayi/kullanici-yonetimi` | Kullanici yonetimi |
| GET/POST | `/bayi/kullanici-ekle` | Kullanici ekle |
| GET | `/bayi/kullanici/{user}/duzenle` | Kullanici duzenle |
| PUT | `/bayi/kullanici/{user}` | Kullanici guncelle |
| DELETE | `/bayi/kullanici/{user}` | Kullanici sil |
| GET | `/bayi/istatistik` | Temel istatistikler |
| GET | `/bayi/gelismis-istatistik` | Gelismis istatistikler |
| GET | `/bayi/paketler` | Paket yonetimi |

### 7.13 Gelismis Vardiya Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/vardiya/analytics` | Vardiya analitik |
| GET | `/bayi/vardiya/kurye/{courier}/durum` | Kurye vardiya durumu |
| GET | `/bayi/vardiya/kurye/{courier}/oneri` | Vardiya onerisi |
| POST | `/bayi/vardiya/kurye/{courier}/sablon` | Sablon uygula |
| POST | `/bayi/vardiya/kurye/{courier}/cakisma-kontrol` | Cakisma kontrol |
| GET | `/bayi/vardiya/saatlik` | Saatlik veri |
| GET | `/bayi/vardiya/istatistik` | Vardiya istatistikleri |
| POST | `/bayi/vardiya/toplu-sablon` | Toplu sablon uygula |

### 7.14 Siparis Analitik ve Finans

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/analytics` | Analitik dashboard |
| GET | `/bayi/analytics/weekly` | Haftalik karsilastirma |
| GET | `/bayi/analytics/branches` | Isletme karsilastirma |
| GET | `/bayi/analytics/heatmap` | Isitma haritasi |
| GET | `/bayi/analytics/api/realtime` | Canli veri (JSON) |
| GET | `/bayi/analytics/api/hourly` | Saatlik veri (JSON) |
| GET | `/bayi/analytics/api/daily` | Gunluk trend (JSON) |
| GET | `/bayi/analytics/api/couriers` | Kurye performans (JSON) |
| GET | `/bayi/analytics/api/heatmap` | Isitma haritasi verisi (JSON) |
| GET | `/bayi/finans` | Finansal raporlar |
| GET | `/bayi/finans/kurye-kazanc` | Kurye kazanc raporu |
| GET | `/bayi/finans/sube-performans` | Isletme performans |
| GET | `/bayi/finans/nakit-akis` | Nakit akis raporu |
| GET | `/bayi/finans/api` | Finans verisi (JSON) |
| GET | `/bayi/finans/export` | Finans export |

---

## 8. Kurye Uygulama Endpoint'leri

**Prefix:** `/kurye`
**Auth:** Session (auth:courier)

### 8.1 Kimlik Dogrulama

| Metod | URL | Auth | Aciklama |
|-------|-----|------|----------|
| GET | `/kurye/giris` | Guest | Giris formu |
| POST | `/kurye/giris` | Guest | Giris yap |
| POST | `/kurye/cikis` | Courier | Cikis yap |

### 8.2 Dashboard ve Siparisler

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/kurye/` | Dashboard |
| GET | `/kurye/siparisler` | Siparis listesi |
| GET | `/kurye/siparis/{order}` | Siparis detay |
| POST | `/kurye/siparis/{order}/durum` | Durum guncelle |
| POST | `/kurye/siparis/{order}/kabul` | Siparisi kabul et |

### 8.3 Teslimat Kaniti (POD)

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/kurye/siparis/{order}/teslim` | Teslim sayfasi |
| POST | `/kurye/siparis/{order}/pod` | POD yukle |
| GET | `/kurye/siparis/{order}/pod` | POD getir |

### 8.4 VoIP Arama

| Metod | URL | Aciklama |
|-------|-----|----------|
| POST | `/kurye/siparis/{order}/ara` | Musteriyi ara |
| GET | `/kurye/siparis/{order}/aramalar` | Arama loglari |

### 8.5 Rota Optimizasyonu

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/kurye/rota` | Rota sayfasi |
| POST | `/kurye/rota/optimize` | Rota optimize et |
| PUT | `/kurye/rota/reorder` | Rota yeniden sirala |

### 8.6 Havuz, Gecmis ve Takvim

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/kurye/havuz` | Havuz siparisleri |
| GET | `/kurye/gecmis` | Teslimat gecmisi |
| GET | `/kurye/takvim` | Haftalik takvim (Yeni - vardiya + yemek restoranlari) |
| GET | `/kurye/profil` | Profil sayfasi |

### 8.7 Durum ve Konum Guncellemeleri

| Metod | URL | Aciklama |
|-------|-----|----------|
| POST | `/kurye/durum` | Kurye durumu guncelle |
| POST | `/kurye/konum` | Konum guncelle |
| POST | `/kurye/device-token` | Cihaz token guncelle |
| POST | `/kurye/pil` | Pil durumu guncelle |
| POST | `/kurye/sync` | Cevrimdisi senkronizasyon |

---

## 9. Admin Panel Endpoint'leri

**Prefix:** `/admin`
**Auth:** Session (auth + admin middleware)

### 9.1 Dashboard ve Yonetim

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/` | Admin dashboard |

### 9.2 Bayi Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/bayiler` | Bayi listesi |
| GET | `/admin/bayiler/create` | Bayi ekleme formu |
| POST | `/admin/bayiler` | Bayi kaydet |
| GET | `/admin/bayiler/{user}` | Bayi detay |
| GET | `/admin/bayiler/{user}/edit` | Bayi duzenleme |
| PUT | `/admin/bayiler/{user}` | Bayi guncelle |
| DELETE | `/admin/bayiler/{user}` | Bayi sil |

### 9.3 Kullanici Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/kullanicilar` | Kullanici listesi |
| POST | `/admin/kullanicilar` | Kullanici ekle |
| PUT | `/admin/kullanicilar/{user}` | Kullanici guncelle |
| DELETE | `/admin/kullanicilar/{user}` | Kullanici sil |

### 9.4 Isletme Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/subeler` | Isletme listesi |
| POST | `/admin/subeler` | Isletme ekle |
| PUT | `/admin/subeler/{branch}` | Isletme guncelle |
| DELETE | `/admin/subeler/{branch}` | Isletme sil |

### 9.5 Kurye Yonetimi

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/kuryeler` | Kurye listesi |
| GET | `/admin/kuryeler/{courier}` | Kurye detay |
| PUT | `/admin/kuryeler/{courier}` | Kurye guncelle |

### 9.6 Siparis, Entegrasyon ve Islemler

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/siparisler` | Siparis listesi |
| GET | `/admin/siparisler/{order}` | Siparis detay |
| GET | `/admin/entegrasyonlar` | Entegrasyonlar |
| GET | `/admin/islemler` | Islem listesi |

### 9.7 Destek Talepleri

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/destek` | Destek listesi |
| GET | `/admin/destek/{ticket}` | Destek detay |
| POST | `/admin/destek/{ticket}/reply` | Yanit gonder |
| PUT | `/admin/destek/{ticket}/status` | Durum guncelle |

### 9.8 Planlar ve Raporlar

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/admin/planlar` | Plan listesi |
| POST | `/admin/planlar` | Plan olustur |
| PUT | `/admin/planlar/{plan}` | Plan guncelle |
| DELETE | `/admin/planlar/{plan}` | Plan sil |
| GET | `/admin/raporlar/bayi` | Bayi raporlari |
| GET | `/admin/raporlar/bayi/export` | Rapor export |

---

## 10. Musteri Portali

**Prefix:** `/portal`
**Auth:** OTP tabanli (session)

| Metod | URL | Rate Limit | Aciklama |
|-------|-----|------------|----------|
| GET | `/portal/` | - | Portal ana sayfa |
| POST | `/portal/send-otp` | 5/dk | OTP gonder |
| POST | `/portal/verify-otp` | 5/dk | OTP dogrula |
| GET | `/portal/dashboard` | - | Musteri dashboard |
| GET | `/portal/orders` | - | Siparis listesi |
| GET | `/portal/order/{order}` | - | Siparis detay |
| GET | `/portal/order/{order}/track` | - | Siparis takip |
| GET | `/portal/addresses` | - | Adres listesi |
| GET | `/portal/logout` | - | Cikis |

---

## Kimlik Dogrulama Yontemleri

| Yontem | Kullanim Alani | Aciklama |
|--------|----------------|----------|
| Session (Cookie) | Web panelleri | Standart Laravel session auth |
| OAuth2 Bearer Token | External API | Dis platform entegrasyonlari |
| API Key | Caller ID | iOS uygulama erisimleri |
| OTP | Musteri Portali | Telefon ile dogrulama |
| Courier Guard | Kurye Paneli | Ayri auth guard (auth:courier) |

## HTTP Durum Kodlari

| Kod | Anlam |
|-----|-------|
| 200 | Basarili |
| 201 | Kaynak olusturuldu |
| 302 | Yonlendirme (form submission sonrasi) |
| 401 | Kimlik dogrulama gerekli |
| 403 | Yetki yetersiz |
| 404 | Kaynak bulunamadi |
| 422 | Validation hatasi |
| 429 | Rate limit asildi |
| 500 | Sunucu hatasi |

---

*Bu dokuman SeferX Lojistik teknik ekibi icin hazirlanmistir.*
*Son guncelleme: 2026-03-14*
