# SeferX Lojistik - Panel Kullanim Kilavuzu

## Genel Bakis

SeferX Lojistik, restoran ve isletmeler icin gelistirilmis kapsamli bir teslimat yonetim sistemidir. Sistem 4 ana panelden olusur ve her panel farkli kullanici rollerine hitap eder.

---

## Siparis Akisi (Order Flow)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           SIPARIS YASAM DONGUSU                             │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌──────────────┐
    │  Siparis     │ ◄── Manuel giris / Trendyol Go / Diger entegrasyonlar
    │  Olusturuldu │
    └──────┬───────┘
           │
           ▼
    ┌──────────────┐
    │   PENDING    │ Siparis bekleniyor
    │  (Beklemede) │
    └──────┬───────┘
           │ Isletme siparisi kabul eder
           ▼
    ┌──────────────┐
    │  PREPARING   │ Siparis hazirlaniyor
    │ (Hazirlaniyor)│
    └──────┬───────┘
           │ Siparis hazir
           ▼
    ┌──────────────┐     ┌─────────────────┐
    │    READY     │────►│   HAVUZ (Pool)  │ Opsiyonel: Kurye atanmadiysa
    │   (Hazir)    │     │ Kuryeler alabilir│
    └──────┬───────┘     └────────┬────────┘
           │                      │
           │ Kurye atandi         │ Kurye havuzdan aldi
           ▼                      ▼
    ┌──────────────┐
    │ ON_DELIVERY  │ Kurye yola cikti
    │  (Yolda)     │
    └──────┬───────┘
           │
           ▼
    ┌──────────────┐
    │  DELIVERED   │ Teslimat tamamlandi
    │(Teslim Edildi)│
    └──────────────┘

    ┌──────────────┐
    │  CANCELLED   │ ◄── Herhangi bir asamada iptal edilebilir
    │ (Iptal Edildi)│
    └──────────────┘
```

---

## 1. ISLETME PANELI

**Erisim:** `/dashboard` | **Rol:** `isletme`

Restoran ve kafe gibi isletmelerin gunluk operasyonlarini yonettigi paneldir. Siparis alma, takip etme ve yonetim islemleri buradan yapilir.

### 1.1 Dashboard
**Sayfa:** Ana sayfa
**Amac:** Gunluk operasyonlarin ozet gorunumu

- Bugunun siparis sayisi
- Bekleyen siparis sayisi
- Aktif kurye sayisi
- Gunluk ciro
- Son siparisler listesi

### 1.2 Harita
**Sayfa:** `/harita`
**Amac:** Canli kurye ve siparis takibi

- Tum kuryelerin anlik konumu harita uzerinde gorunur
- Aktif siparislerin teslimat adresleri isaretlenir
- Kurye durumu (musait/mesgul/cevrimdisi) renk koduyla gosterilir
- Tiklayarak kurye detaylari gorulebilir

### 1.3 Siparis Yonetimi

#### 1.3.1 Siparis Listesi
**Sayfa:** `/siparis/liste`
**Amac:** Aktif siparisleri yonetme

**Ozellikler:**
- Yeni siparis olusturma (manuel giris)
- Siparis durumunu guncelleme
- Kurye atama
- Siparis detaylarini goruntuleme
- Filtreleme (durum, tarih, arama)

**Siparis Olusturma Akisi:**
1. "Yeni Siparis" butonuna tiklanir
2. Musteri bilgileri girilir (ad, telefon, adres)
3. Urunler secilir ve miktarlar belirlenir
4. Odeme yontemi secilir (Nakit/Kart/Online)
5. Kurye atanir (opsiyonel - sonra da atanabilir)
6. Siparis kaydedilir

#### 1.3.2 Siparis Gecmisi
**Sayfa:** `/siparis/gecmis`
**Amac:** Tamamlanan ve iptal edilen siparisleri goruntuleme

- Tarih araligina gore filtreleme
- Siparis detaylarini inceleme
- Istatistiksel analiz icin veri

#### 1.3.3 Iptal Edilenler
**Sayfa:** `/siparis/iptal`
**Amac:** Iptal edilen siparisleri takip etme

- Iptal nedenleri
- Iptal tarihi ve saati
- Hangi asamada iptal edildigi

#### 1.3.4 Istatistik
**Sayfa:** `/siparis/istatistik`
**Amac:** Siparis verilerinin analizi

- Toplam siparis sayisi
- Durum bazli dagilim
- Gunluk/haftalik/aylik trendler
- En cok satilan urunler
- Ciro analizi

### 1.4 Musteri Yonetimi
**Sayfa:** `/musteriler`
**Amac:** Musteri veritabanini yonetme

- Musteri listesi
- Musteri ekleme/duzenleme
- Siparis gecmisi
- Adres bilgileri
- Iletisim bilgileri

### 1.5 Entegrasyonlar
**Sayfa:** `/entegrasyonlar`
**Amac:** Dis platform entegrasyonlarini yonetme

**Desteklenen Platformlar:**
- **Trendyol Go:** Otomatik siparis alma
- Diger platformlar eklenebilir

**Entegrasyon Akisi:**
1. Platform secilir
2. API bilgileri girilir (Supplier ID, API Key, vb.)
3. Baglanti test edilir
4. Aktif edilir
5. Siparisler otomatik olarak sisteme akar

### 1.6 Yonetim

#### 1.6.1 Kategoriler
**Sayfa:** `/yonetim/kategoriler`
**Amac:** Urun kategorilerini yonetme

- Kategori olusturma (Yiyecek, Icecek, Tatli, vb.)
- Siralama
- Aktif/Pasif durumu

#### 1.6.2 Paketler
**Sayfa:** `/yonetim/paketler`
**Amac:** Teslimat paket tiplerini yonetme

#### 1.6.3 Urunler
**Sayfa:** `/yonetim/urunler`
**Amac:** Urun katalogu yonetimi

- Urun ekleme/duzenleme
- Fiyatlandirma
- Stok durumu
- Kategori atama
- Urun gorseli

#### 1.6.4 Kayitli Kartlarim
**Sayfa:** `/yonetim/kartlar`
**Amac:** Odeme kartlarini yonetme

#### 1.6.5 Islemlerim
**Sayfa:** `/yonetim/islemler`
**Amac:** Finansal islemleri goruntuleme

### 1.7 Isletmem

#### 1.7.1 Bilgiler
**Sayfa:** `/isletmem/bilgiler`
**Amac:** Isletme bilgilerini guncelleme

- Isletme adi
- Adres bilgileri
- Iletisim bilgileri
- Calisma saatleri

#### 1.7.2 Kullanicilar
**Sayfa:** `/isletmem/kullanicilar`
**Amac:** Isletme kullanicilarini yonetme

- Yeni kullanici ekleme
- Rol atama
- Erisim yetkileri

### 1.8 Destek
**Sayfa:** `/destek`
**Amac:** Destek talebi olusturma

- Yeni talep acma
- Mevcut talepleri takip etme
- Destek ekibiyle iletisim

---

## 2. BAYI PANELI

**Erisim:** `/bayi/*` | **Rol:** `bayi`

Birden fazla isletme ve kuryeyi yoneten bayiler icin tasarlanmis paneldir. Genis capli operasyon yonetimi saglar.

### 2.1 Harita
**Sayfa:** `/bayi/harita`
**Amac:** Tum kuryelerin canli takibi

- Butun kuryelerin anlik konumu
- Siparis dagilimi
- Bolge bazli yogunluk analizi
- Kurye rotalarini goruntuleme

### 2.2 Kuryelerim
**Sayfa:** `/bayi/kuryelerim`
**Amac:** Kurye kadrosunu yonetme

**Ozellikler:**
- Yeni kurye ekleme
- Kurye bilgilerini duzenleme
- Durum takibi (Musait/Mesgul/Cevrimdisi)
- Performans metrikleri
- Nakit bakiye yonetimi

**Kurye Ekleme:**
1. Ad, telefon, TC no girilir
2. Arac plakasi (varsa)
3. Vardiya saatleri belirlenir
4. Fiyatlandirma politikasi secilir
5. Uygulama erisimi aktif edilir

### 2.3 Isletmelerim
**Sayfa:** `/bayi/isletmelerim`
**Amac:** Baglı isletmeleri yonetme

- Isletme listesi
- Isletme ekleme/duzenleme
- Siparis dagilimi
- Performans raporlari

### 2.4 Vardiya Saatleri
**Sayfa:** `/bayi/vardiya-saatleri`
**Amac:** Calisma saatlerini planlama

- Haftalik vardiya tablosu
- Kurye bazli atama
- Mesai planlama

### 2.5 Kullanici Yonetimi
**Sayfa:** `/bayi/kullanici-yonetimi`
**Amac:** Panel kullanicilarini yonetme

- Alt kullanici olusturma
- Rol ve yetki atama
- Erisim kontrolu

### 2.6 Istatistik
**Sayfa:** `/bayi/istatistik`
**Amac:** Temel performans metrikleri

- Gunluk/haftalik siparis sayilari
- Teslimat sureleri
- Kurye performanslari
- Gelir analizi

### 2.7 Gelismis Istatistik
**Sayfa:** `/bayi/gelismis-istatistik`
**Amac:** Detayli analiz ve raporlar

- Bolge bazli analiz
- Trend grafikleri
- Karsilastirmali raporlar
- Tahminleme

### 2.8 Bolgelendirme
**Sayfa:** `/bayi/bolgelendirme`
**Amac:** Teslimat bolgelerini tanimlama

**Ozellikler:**
- Harita uzerinde polygon cizerek bolge tanimlama
- Bolgeye kurye atama
- Bolge bazli teslimat ucreti belirleme
- Tahmini teslimat suresi
- Drag & drop ile kurye-bolge eslestirme

**Kullanim:**
1. "Yeni Bolge" veya "Haritadan Ciz" tiklanir
2. Harita uzerinde bolge siniri cizilir
3. Bolge adi ve renk belirlenir
4. Teslimat ucreti girilir
5. Kuryeler atanir

### 2.9 Odemeler

#### 2.9.1 Nakit Odemeler
**Sayfa:** `/bayi/nakit-odemeler`
**Amac:** Nakit tahsilat takibi

- Kuryelerdeki nakit bakiye
- Tahsilat kayitlari
- Mutabakat

#### 2.9.2 Kurye Odemeleri
**Sayfa:** `/bayi/odemeler/kurye`
**Amac:** Kuryelere yapilan odemeleri yonetme

- Teslimat basina odeme
- Haftalik/aylik odemeler
- Odeme gecmisi

#### 2.9.3 Isletme Odemeleri
**Sayfa:** `/bayi/odemeler/isletme`
**Amac:** Isletmelere yapilan odemeleri yonetme

### 2.10 Siparisler

#### 2.10.1 Gecmis
**Sayfa:** `/bayi/siparisler/gecmis`
**Amac:** Tum siparis gecmisini goruntuleme

#### 2.10.2 Bedelsiz Siparisler
**Sayfa:** `/bayi/siparisler/bedelsiz`
**Amac:** Ucretsiz teslimatlari takip etme

### 2.11 Ayarlar

#### 2.11.1 Genel Ayarlar
**Sayfa:** `/bayi/ayarlar/genel`
**Amac:** Sistem genel ayarlari

#### 2.11.2 Kurye Ayarlari
**Sayfa:** `/bayi/ayarlar/kurye`
**Amac:** Kurye ile ilgili yapilandirmalar

- Maksimum siparis limiti
- Otomatik atama kurallari
- Bildirim ayarlari

#### 2.11.3 Uygulama Ayarlari
**Sayfa:** `/bayi/ayarlar/uygulama`
**Amac:** Mobil uygulama ayarlari

#### 2.11.4 Havuz Ayarlari
**Sayfa:** `/bayi/ayarlar/havuz`
**Amac:** Siparis havuzu yapilandirmasi

**Havuz Sistemi Nedir?**
Kurye atanmayan siparisler "havuza" duser. Kuryeler kendi uygulamalarindan havuzdaki siparisleri gorup alabilirler.

**Ayarlar:**
- Havuz aktif/pasif
- Kuryelere bildirim gonderme
- Otomatik atama suresi
- Oncelik kurallari

#### 2.11.5 Bildirim Ayarlari
**Sayfa:** `/bayi/ayarlar/bildirim`
**Amac:** Bildirim tercihlerini yonetme

- Push bildirim
- SMS bildirim
- E-posta bildirim
- Bildirim turleri

### 2.12 Tema
**Sayfa:** `/bayi/tema`
**Amac:** Arayuz gorunumunu ozellestirme

- Acik/Koyu mod
- Renk semalari

---

## 3. KURYE PANELI (Mobil Uyumlu)

**Erisim:** `/kurye/*` | **Rol:** `kurye`

Kuryelerin mobil cihazlarindan kullandigi, teslimat odakli paneldir.

### 3.1 Dashboard
**Sayfa:** `/kurye/dashboard`
**Amac:** Gunluk ozet ve aktif siparis

- Bugunun teslimat sayisi
- Kazanilan tutar
- Aktif siparis bilgisi
- Durum degistirme (Musait/Mesgul/Mola)

### 3.2 Siparisler
**Sayfa:** `/kurye/orders`
**Amac:** Atanan siparisleri goruntuleme

**Siparis Karti Bilgileri:**
- Siparis numarasi
- Musteri adi ve telefonu
- Teslimat adresi
- Urunler
- Toplam tutar
- Odeme yontemi

**Islemler:**
- Navigasyonu baslat (Google Maps/Apple Maps)
- Musteri ara
- Durumu guncelle
- Teslimat onayi

### 3.3 Havuz
**Sayfa:** `/kurye/pool`
**Amac:** Havuzdaki siparisleri goruntuleme ve alma

**Akis:**
1. Kurye havuz sayfasini acar
2. Mevcut siparisleri gorur
3. Uygun siparisi secer
4. "Siparisi Al" tiklar
5. Siparis kuryeye atanir

### 3.4 Gecmis
**Sayfa:** `/kurye/history`
**Amac:** Tamamlanan teslimatlari goruntuleme

- Tarih bazli filtreleme
- Kazanc detaylari
- Teslimat sureleri

### 3.5 Profil
**Sayfa:** `/kurye/profile`
**Amac:** Kisisel bilgiler ve ayarlar

- Profil bilgileri
- Toplam kazanc
- Nakit bakiye
- Sifre degistirme
- Bildirim ayarlari

---

## 4. ADMIN PANELI

**Erisim:** `/admin/*` | **Rol:** `super_admin`

Tum sistemi yoneten ust duzey yonetim panelidir.

### 4.1 Dashboard
**Sayfa:** `/admin/dashboard`
**Amac:** Sistem geneli istatistikler

- Toplam bayi sayisi
- Toplam siparis sayisi
- Aktif kurye sayisi
- Sistem geneli ciro
- Son kayit olan bayiler
- Son siparisler

### 4.2 Bayiler
**Sayfa:** `/admin/bayiler`
**Amac:** Bayi hesaplarini yonetme

**Islemler:**
- Yeni bayi olusturma
- Bayi bilgilerini duzenleme
- Bayi detaylarini goruntuleme (subeler, kuryeler, siparisler)
- Bayi silme
- Durum degistirme (aktif/pasif)

### 4.3 Kullanicilar
**Sayfa:** `/admin/kullanicilar`
**Amac:** Tum kullanicilari yonetme

- Kullanici listesi
- Rol atama (admin, bayi, isletme, kurye)
- Kullanici olusturma/duzenleme
- Erisim yonetimi

### 4.4 Subeler
**Sayfa:** `/admin/subeler`
**Amac:** Tum subeleri goruntuleme

- Sube listesi
- Hangi bayiye ait
- Sube ayarlari

### 4.5 Kuryeler
**Sayfa:** `/admin/kuryeler`
**Amac:** Tum kuryeleri goruntuleme

- Kurye listesi
- Durum takibi
- Performans metrikleri
- Detay sayfasi

### 4.6 Siparisler
**Sayfa:** `/admin/siparisler`
**Amac:** Sistem geneli siparis takibi

- Tum siparisler
- Filtreleme (tarih, durum, bayi, platform)
- Siparis detaylari

### 4.7 Entegrasyonlar
**Sayfa:** `/admin/entegrasyonlar`
**Amac:** Platform entegrasyonlarini izleme

- Baglanti durumlari
- Hata loglari
- API kullanimi

### 4.8 Islemler
**Sayfa:** `/admin/islemler`
**Amac:** Finansal islemleri goruntuleme

- Tum islemler
- Islem turleri (abonelik, tek seferlik, iade)
- Filtreleme

### 4.9 Destek Talepleri
**Sayfa:** `/admin/destek`
**Amac:** Kullanici destek taleplerini yonetme

**Islemler:**
- Talep listesi
- Talep detayi
- Yanit gonderme
- Durum guncelleme (Acik, Islemde, Cozuldu, Kapandi)

---

## Entegrasyon Detaylari

### Trendyol Go Entegrasyonu

**Nasil Calisir:**
1. Isletme Trendyol Go API bilgilerini girer
2. Sistem periyodik olarak yeni siparisleri ceker
3. Siparisler otomatik olarak sisteme eklenir
4. Durum degisiklikleri Trendyol'a geri bildirilir

**Siparis Durumlari:**
- `Created` → Siparis olusturuldu
- `Picking` → Hazirlaniyor
- `Shipped` → Yola cikti
- `Delivered` → Teslim edildi
- `Cancelled` → Iptal edildi

---

## Bildirim Sistemi

### Push Bildirimleri
- Yeni siparis geldiginde
- Siparis durumu degistiginde
- Havuza siparis eklediginde
- Kurye atandiginda

### SMS Bildirimleri (Opsiyonel)
- Musteriye teslimat bildirimi
- Kurye yola cikti bildirimi

---

## Guvenlik

- Tum paneller giris gerektirir
- Rol bazli erisim kontrolu
- Her rol sadece yetkili oldugu sayfalara erisebilir
- Hassas islemler icin onay gerekir

---

## Kisaltmalar

| Kisaltma | Anlami |
|----------|--------|
| POD | Proof of Delivery (Teslimat Kaniti) |
| API | Application Programming Interface |
| CRUD | Create, Read, Update, Delete |

---

*Bu dokuman SeferX Lojistik sisteminin kullanim kilavuzudur. Sorulariniz icin destek ekibiyle iletisime geciniz.*
