# SeferX Lojistik - Kurye Yemek Restoranlari Sistemi

**Tarih:** 2026-03-14
**Versiyon:** 2.1.0
**Durum:** Aktif

---

## 1. Genel Bakis

Kurye Yemek Restoranlari Sistemi, bayilerin kuryelerine yemek hakki tanimlamasini saglayan bir moduldur. Sistem, kurye vardiya surelerine gore otomatik ogun hakki olusturur ve kuryelerin anlasmalari restoranlarda bu haklari kullanmasini yonetir.

### Temel Kavramlar

| Kavram | Aciklama | Tablo |
|--------|----------|-------|
| **Yemek Vardiyasi (Meal Shift)** | Kuryenin belirli bir gunde calisacagi zaman dilimi | `courier_meal_shifts` |
| **Ogun Hakki (Meal Benefit)** | Kuryenin kazandigi yemek hakki | `courier_meal_benefits` |
| **Restoran** | Kuryenin yemek yiyebilecegi anlasmalari isletme | `restaurants` |

---

## 2. Sistem Akisi

```
┌──────────────────────────────────────────────────────────────────────┐
│                    KURYE YEMEK SISTEMI AKISI                        │
└──────────────────────────────────────────────────────────────────────┘

  1. VARDIYA TANIMLA              2. OTOMATIK HAK OLUSTUR
  ┌────────────────┐              ┌────────────────────┐
  │ Bayi, kuryeye  │              │ Observer tetiklenir │
  │ yemek vardiyasi│───────────►  │ Vardiya suresi      │
  │ atar           │              │ hesaplanir          │
  └────────────────┘              │ Ogun hakki          │
                                  │ olusturulur         │
                                  └─────────┬──────────┘
                                            │
                                            ▼
  3. KURYE KULLANIR               4. BAYI ISARETLER
  ┌────────────────┐              ┌────────────────────┐
  │ Kurye takvimden│              │ Bayi, kuryenin     │
  │ ogun hakkini   │───────────►  │ yemek yedigini     │
  │ gorur          │              │ "kullanildi" olarak│
  │                │              │ isaretler          │
  └────────────────┘              └─────────┬──────────┘
                                            │
                                            ▼
                                  5. RAPORLAMA
                                  ┌────────────────────┐
                                  │ Aylik maliyet      │
                                  │ raporu olusturulur  │
                                  │ (meal_value bazli)  │
                                  └────────────────────┘
```

**Adim Adim Aciklama:**

1. **Vardiya Tanimla:** Bayi, `/bayi/yemek-vardiyalari` sayfasindan kuryeye gun, saat araligi ve restoran belirleyerek yemek vardiyasi atar.
2. **Otomatik Hak Olustur:** `CourierMealShiftObserver` tetiklenir. Vardiya suresi hesaplanir ve esik kuralarina gore ogun hakki (`CourierMealBenefit`) otomatik olusturulur.
3. **Kurye Kullanir:** Kurye, `/kurye/takvim` sayfasindan haftalik takviminde ogun haklarini ve atandigi restoranlari gorur.
4. **Bayi Isaretler:** Kurye yemek yediginde, bayi veya isletme ogun hakkini "kullanildi" (`is_used = true`) olarak isaretler.
5. **Raporlama:** Ay sonunda kullanilan ogun haklari `meal_value` uzerinden toplanarak maliyet raporu olusturulur.

---

## 3. Vardiya Suresi ve Ogun Hakki Kurallari

### 3.1 Vardiya Suresi Esikleri

Kuryenin bir gundeki toplam vardiya suresi, kazanacagi ogun hakki sayisini belirler.

| Vardiya Suresi | Ogun Hakki | Aciklama |
|----------------|------------|----------|
| **< 4 saat** | 0 ogun | Yemek hakki yok |
| **4 - 7 saat** | 1 ogun | Vardiya saatine denk dusen tek ogun |
| **7 - 10 saat** | 2 ogun | Iki ogun hakki |
| **10+ saat** | 3 ogun | Kahvalti + ogle + aksam (tam gun) |

### 3.2 Ogun-Saat Eslemesi

Hangi ogun hakkinin kazanilacagi, vardiya saatlerinin kapsadigi zaman dilimine gore belirlenir:

| Ogun Turu | Zaman Dilimi | Meal Type |
|-----------|--------------|-----------|
| **Kahvalti** | 06:00 - 12:00 | `breakfast` |
| **Ogle Yemegi** | 11:00 - 15:00 | `lunch` |
| **Aksam Yemegi** | 17:00 sonrasi | `dinner` |

**Kural Detayi:**
- Vardiya 06:00-12:00 arasinda basliyorsa: `breakfast` hakki kazanilir
- Vardiya 11:00-15:00 araligini kapsiyorsa: `lunch` hakki kazanilir
- Vardiya 17:00 sonrasina uzaniyorsa: `dinner` hakki kazanilir

**Ornek Senaryolar:**

| Vardiya | Sure | Kazanilan Ogunler |
|---------|------|-------------------|
| 08:00 - 11:00 | 3 saat | Hak yok (< 4 saat) |
| 08:00 - 13:00 | 5 saat | 1 ogun: Kahvalti (06:00-12:00 diliminde) |
| 09:00 - 16:00 | 7 saat | 2 ogun: Kahvalti + Ogle |
| 07:00 - 18:00 | 11 saat | 3 ogun: Kahvalti + Ogle + Aksam |
| 12:00 - 17:00 | 5 saat | 1 ogun: Ogle (11:00-15:00 diliminde) |
| 14:00 - 23:00 | 9 saat | 2 ogun: Ogle + Aksam |
| 18:00 - 23:00 | 5 saat | 1 ogun: Aksam (17:00 sonrasi) |

---

## 4. Is Kurallari

### 4.1 Devir Etmez Kurali
- Ogun hakki **sadece tanimlanan gun icin gecerlidir**.
- Kullanilmayan haklar bir sonraki gune devretmez.
- `CourierMealBenefit::isExpired()` metodu: `benefit_date` gecmis bir tarihse hak gecersiz sayilir.
- `CourierMealBenefit::isValid()` metodu: Hak kullanilmamis VE suresi gecmemisse gecerlidir.

### 4.2 Restoran Sinirlamasi
- Kurye, ogun hakkini **sadece yemek vardiyasinda atandigi restoranda** kullanabilir.
- `CourierMealShift` tablosundaki `restaurant_id` alani, kuryenin hangi restoranda yemek yiyecegini belirler.
- `CourierMealBenefit::markAsUsed($restaurantId)` cagrildiginda, gelen `restaurant_id` ile atanmis restoran karsilastirilir.
- Eslesme yoksa islem reddedilir.

### 4.3 Tekil Kayit Kurali (Unique Constraint)
- Ayni kurye, ayni gun, ayni ogun turununde **birden fazla ogun hakki olusturulamaz**.
- Veritabani seviyesinde `UNIQUE(courier_id, benefit_date, meal_type)` constraint uygulanir.
- Bu kural, Observer'in ayni vardiyayi tekrar islemesi veya cift kayit girisi durumunu engeller.

### 4.4 Sahiplik Kontrolu
- Bayi, sadece **kendi kuryelerine** yemek vardiyasi atayabilir.
- `BayiShiftController::checkCourierOwnership()` metodu ile kurye sahipligi dogrulanir.
- Kurye, sadece **kendisine ait** ogun haklarini gorebilir.

---

## 5. Veritabani Semasi

### 5.1 courier_meal_shifts

Kuryenin yemek vardiyasini tanimlar.

```
┌─────────────────────────────────────────┐
│ courier_meal_shifts                      │
├─────────────────────────────────────────┤
│ id              BIGINT PK AUTO          │
│ courier_id      BIGINT FK → couriers    │
│ restaurant_id   BIGINT FK → restaurants │
│ date            DATE                    │
│ meal_type       ENUM(breakfast,         │
│                      lunch, dinner)     │
│ start_time      TIME                    │
│ end_time        TIME                    │
│ is_active       BOOLEAN (default: true) │
│ notes           TEXT (nullable)         │
│ created_at      TIMESTAMP              │
│ updated_at      TIMESTAMP              │
├─────────────────────────────────────────┤
│ INDEX: (courier_id, date)              │
│ INDEX: (date, meal_type)               │
└─────────────────────────────────────────┘
```

### 5.2 courier_meal_benefits

Kuryenin kazandigi ogun hakkini temsil eder.

```
┌─────────────────────────────────────────┐
│ courier_meal_benefits                    │
├─────────────────────────────────────────┤
│ id              BIGINT PK AUTO          │
│ courier_id      BIGINT FK → couriers    │
│ branch_id       BIGINT FK → branches    │
│ benefit_date    DATE                    │
│ meal_type       ENUM(breakfast,         │
│                      lunch, dinner)     │
│ meal_value      DECIMAL(8,2)            │
│ is_used         BOOLEAN (default: false)│
│ used_at         TIMESTAMP (nullable)    │
│ notes           TEXT (nullable)         │
│ created_at      TIMESTAMP              │
│ updated_at      TIMESTAMP              │
├─────────────────────────────────────────┤
│ INDEX: (courier_id, benefit_date)      │
│ INDEX: (branch_id, benefit_date)       │
│ INDEX: (is_used)                       │
│ UNIQUE: (courier_id, benefit_date,     │
│          meal_type)                     │
└─────────────────────────────────────────┘
```

### 5.3 Iliskiler

```
couriers ─┬── 1:N ──► courier_meal_shifts ──► N:1 ── restaurants
           │
           └── 1:N ──► courier_meal_benefits ──► N:1 ── branches
```

---

## 6. Aylik Maliyet Raporu

### 6.1 Nasil Calisir

Her `CourierMealBenefit` kaydinda bir `meal_value` (TL cinsinden deger) bulunur. Bu deger, ogun hakkinin parasal karsiligini temsil eder.

**Rapor Hesaplama:**
```
Aylik Maliyet = SUM(meal_value) WHERE is_used = true AND benefit_date BETWEEN ay_basi AND ay_sonu
```

### 6.2 Rapor Kirilimlari

| Kirilim | Aciklama |
|---------|----------|
| **Kurye Bazli** | Her kuryenin aylik toplam yemek maliyeti |
| **Restoran Bazli** | Her restorana odenen toplam tutar |
| **Ogun Bazli** | Kahvalti/ogle/aksam bazinda maliyet dagilimi |
| **Gunluk** | Gun gun kullanim detayi |

### 6.3 Ornek Rapor

```
Mart 2026 - Kurye Yemek Raporu
─────────────────────────────────────
Kurye          Kahvalti  Ogle   Aksam  Toplam
─────────────────────────────────────
Ahmet K.       4x60₺    12x80₺  8x80₺  1,880₺
Mehmet Y.      0         10x80₺  6x80₺  1,280₺
Ayse D.        2x60₺    8x80₺   4x80₺  1,080₺
─────────────────────────────────────
GENEL TOPLAM                           4,240₺
```

---

## 7. Bayi Icin Kullanim Kilavuzu

### Adim 1: Restoran Tanimlama
1. `/restoran` sayfasina gidin
2. "Yeni Restoran" butonuna tiklayin
3. Restoran bilgilerini girin (ad, adres, iletisim)
4. Kaydedin

### Adim 2: Yemek Vardiyasi Olusturma
1. `/bayi/yemek-vardiyalari` sayfasina gidin
2. Haftalik takvimde istediginiz gun ve kuryeyi secin
3. Vardiya bilgilerini girin:
   - **Kurye:** Listeden secin
   - **Restoran:** Anlasmalari restoran secin (opsiyonel)
   - **Tarih:** Vardiya tarihi
   - **Ogun Turu:** Kahvalti / Ogle / Aksam
   - **Baslangic Saati:** Vardiya baslangici
   - **Bitis Saati:** Vardiya bitisi
   - **Not:** Ek bilgi (opsiyonel)
4. "Kaydet" butonuna tiklayin
5. Sistem otomatik olarak ogun hakkini olusturur

### Adim 3: Haftalik Gorunum Kontrolu
1. Haftalik takvimde her kuryenin vardiyalarini gorun
2. Ok tuslarıyla haftalar arasi gecis yapin
3. Gerekirse vardiya duzenleyin veya silin

### Adim 4: Ogun Hakkini Kullanildi Olarak Isaretleme
1. Kurye yemek yedikten sonra ilgili ogun hakkini secin
2. "Kullanildi" olarak isaretleyin
3. Sistem `used_at` tarihini otomatik kaydeder

### Adim 5: Aylik Rapor Inceleme
1. Ay sonunda maliyet raporunu inceleyin
2. Kurye ve restoran bazli kirilimları kontrol edin
3. `meal_value` degerlerini restoran anlasmalariniza gore yapilandirin

---

## 8. Kurye Icin Kullanim Kilavuzu

### Takvim Gorunumu
1. `/kurye/takvim` sayfasina gidin
2. Haftalik takvimde asagidaki bilgileri gorun:
   - **Yemek Vardiyalari:** Hangi gun, hangi saatlerde, hangi restoranda yemek yiyebilirsiniz
   - **Ogun Haklari:** Kazandiginiz ogun haklarinin durumu (kullanildi/kullanilmadi)
3. Ok tuslariyla onceki/sonraki haftaya gecis yapin

### Onemli Bilgiler
- Ogun hakkiniz **sadece belirlenen gun icin gecerlidir**, ertesi gune devralmaz
- Yemek **sadece atandiginiz restoranda** yiyebilirsiniz
- Kullanilmayan haklar otomatik olarak gecersiz olur

---

## 9. API Endpoint'leri

Detayli API dokumantasyonu icin: [API Referansi](api-reference.md#76-yemek-vardiyalari-yeni)

### Bayi Paneli

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/bayi/yemek-vardiyalari` | Haftalik vardiya gorunumu |
| POST | `/bayi/yemek-vardiyalari` | Vardiya olustur |
| PUT | `/bayi/yemek-vardiyalari/{id}` | Vardiya guncelle |
| DELETE | `/bayi/yemek-vardiyalari/{id}` | Vardiya sil |

### Kurye Paneli

| Metod | URL | Aciklama |
|-------|-----|----------|
| GET | `/kurye/takvim` | Haftalik takvim (vardiya + ogun haklari) |

---

## 10. Teknik Detaylar

### Ilgili Dosyalar

| Dosya | Aciklama |
|-------|----------|
| `app/Models/CourierMealShift.php` | Yemek vardiyasi modeli |
| `app/Models/CourierMealBenefit.php` | Ogun hakki modeli |
| `app/Observers/CourierMealShiftObserver.php` | Otomatik hak olusturma (Observer) |
| `app/Http/Controllers/Bayi/BayiShiftController.php` | Bayi tarafli CRUD islemleri |
| `app/Http/Controllers/Kurye/KuryeAppController.php` | Kurye takvim gorunumu |
| `database/migrations/2026_03_02_220706_*` | Meal shifts tablosu |
| `database/migrations/2026_03_02_220711_*` | Meal benefits tablosu |
| `database/migrations/2026_03_14_100000_*` | Restaurant ID alani ekleme |

### Model Metodlari

**CourierMealShift:**
- `isCurrentlyActive()` - Vardiya su an aktif mi kontrolu
- `getDurationHours()` - Vardiya suresi (saat cinsinden)
- `scopeActive()` - Aktif vardiyalari filtrele
- `scopeForDate($date)` - Belirli bir gunun vardiyalari
- `scopeForMealType($type)` - Belirli ogun turune gore filtrele

**CourierMealBenefit:**
- `markAsUsed()` - Ogun hakkini kullanildi olarak isaretle
- `isExpired()` - Hak suresi gecmis mi kontrolu
- `isValid()` - Hak gecerli mi kontrolu (kullanilmamis + suresi gecmemis)
- `scopeUnused()` - Kullanilmamis haklari filtrele
- `scopeUsed()` - Kullanilmis haklari filtrele
- `scopeForDate($date)` - Belirli bir gunun haklari

---

*Bu dokuman SeferX Lojistik teknik ve operasyon ekibi icin hazirlanmistir.*
*Son guncelleme: 2026-03-14*
