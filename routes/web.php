<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Harita;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Bayi\BayiController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RestaurantController;
use App\Models\PricingPolicy;

// Guest Routes (Authentication)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Panel Selection
    Route::get('/panel-secimi', [PanelController::class, 'showSelection'])->name('panel.selection');
    Route::get('/panel/{panel}', [PanelController::class, 'selectPanel'])->name('panel.select');

    // Ana Sayfa - Redirect based on active panel
    Route::get('/', function () {
        $activePanel = session('active_panel', auth()->user()->getFirstRole());
        
        if ($activePanel === 'bayi') {
            return redirect()->route('bayi.harita');
        } else {
            return redirect()->route('dashboard');
        }
    });

    // Harita & Kurye Takip
    Route::get('/harita', Harita::class)->name('harita');

    // Sipariş Yönetimi
    Route::get('/siparis', [OrderController::class, 'index'])->name('siparis.liste');
    Route::get('/siparis/create', [OrderController::class, 'create'])->name('siparis.create');
    Route::post('/siparis', [OrderController::class, 'store'])->name('siparis.store');
    Route::get('/siparis/{order}/edit', [OrderController::class, 'edit'])->name('siparis.edit');
    Route::put('/siparis/{order}', [OrderController::class, 'update'])->name('siparis.update');
    Route::delete('/siparis/{order}', [OrderController::class, 'destroy'])->name('siparis.destroy');
    Route::patch('/siparis/{order}/status', [OrderController::class, 'updateStatus'])->name('siparis.updateStatus');
    Route::get('/siparis/gecmis', [OrderController::class, 'history'])->name('siparis.gecmis');
    Route::get('/siparis/iptal', [OrderController::class, 'cancelled'])->name('siparis.iptal');
    Route::get('/siparis/istatistik', [OrderController::class, 'statistics'])->name('siparis.istatistik');

    // Yönetim
    Route::get('/yonetim/entegrasyonlar', [\App\Http\Controllers\IntegrationController::class, 'index'])->name('yonetim.entegrasyonlar');
    Route::post('/integrations/{platform}/connect', [\App\Http\Controllers\IntegrationController::class, 'connect'])->name('integrations.connect');
    Route::post('/integrations/{platform}/disconnect', [\App\Http\Controllers\IntegrationController::class, 'disconnect'])->name('integrations.disconnect');
    Route::post('/integrations/{platform}/test', [\App\Http\Controllers\IntegrationController::class, 'testConnection'])->name('integrations.test');
    Route::post('/integrations/{platform}/sync', [\App\Http\Controllers\IntegrationController::class, 'syncOrders'])->name('integrations.sync');
    Route::get('/yonetim/paketler', [\App\Http\Controllers\Yonetim\YonetimController::class, 'paketler'])->name('yonetim.paketler');
    Route::get('/yonetim/urunler', [\App\Http\Controllers\Yonetim\YonetimController::class, 'urunler'])->name('yonetim.urunler');
    Route::get('/yonetim/kartlar', [\App\Http\Controllers\Yonetim\YonetimController::class, 'kartlar'])->name('yonetim.kartlar');
    Route::get('/yonetim/abonelikler', [\App\Http\Controllers\Yonetim\YonetimController::class, 'abonelikler'])->name('yonetim.abonelikler');
    Route::get('/yonetim/islemler', [\App\Http\Controllers\Yonetim\YonetimController::class, 'islemler'])->name('yonetim.islemler');

    // Billing Routes
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::post('/subscribe/{plan}', [\App\Http\Controllers\BillingController::class, 'subscribe'])->name('subscribe');
        Route::post('/subscription/cancel', [\App\Http\Controllers\BillingController::class, 'cancelSubscription'])->name('subscription.cancel');
        Route::post('/subscription/upgrade/{plan}', [\App\Http\Controllers\BillingController::class, 'upgrade'])->name('subscription.upgrade');
        Route::post('/cards', [\App\Http\Controllers\BillingController::class, 'storeCard'])->name('cards.store');
        Route::post('/cards/{card}/default', [\App\Http\Controllers\BillingController::class, 'setDefaultCard'])->name('cards.default');
        Route::delete('/cards/{card}', [\App\Http\Controllers\BillingController::class, 'destroyCard'])->name('cards.destroy');
        Route::get('/invoice/{transaction}', [\App\Http\Controllers\BillingController::class, 'downloadInvoice'])->name('invoice.download');
    });

    // İşletmem - Menu & Products
    Route::get('/isletmem/menu', [MenuController::class, 'index'])->name('isletmem.menu');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    
    // İşletmem - Couriers
    Route::get('/isletmem/kuryeler', [CourierController::class, 'index'])->name('isletmem.kuryeler');
    Route::post('/isletmem/kuryeler', [CourierController::class, 'store'])->name('couriers.store');
    Route::get('/isletmem/kuryeler/create', [CourierController::class, 'create'])->name('couriers.create');
    Route::get('/isletmem/kuryeler/{courier}/edit', [CourierController::class, 'edit'])->name('couriers.edit');
    Route::put('/isletmem/kuryeler/{courier}', [CourierController::class, 'update'])->name('couriers.update');
    Route::delete('/isletmem/kuryeler/{courier}', [CourierController::class, 'destroy'])->name('couriers.destroy');
    Route::patch('/isletmem/kuryeler/{courier}/status', [CourierController::class, 'updateStatus'])->name('couriers.updateStatus');
    Route::patch('/isletmem/kuryeler/{courier}/shift', [CourierController::class, 'updateShift'])->name('couriers.updateShift');
    Route::get('/isletmem/kuryeler/{courier}/check-shift', [CourierController::class, 'checkShift'])->name('couriers.checkShift');
    Route::get('/isletmem/kuryeler/available', [CourierController::class, 'getAvailable'])->name('couriers.available');
    Route::get('/isletmem/kuryeler/stats', [CourierController::class, 'getStats'])->name('couriers.stats');
    Route::patch('/isletmem/kuryeler/{courier}/location', [CourierController::class, 'updateLocation'])->name('couriers.updateLocation');
    
    // API endpoints for map data (Rate limited: 60 requests/minute)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/api/couriers/search', [ApiController::class, 'searchCouriers'])->name('api.couriers.search');
        Route::get('/api/orders/search', [ApiController::class, 'searchOrders'])->name('api.orders.search');
        Route::get('/api/couriers/{courier}', [ApiController::class, 'showCourier'])->name('api.couriers.show');
        Route::get('/api/map-data', [ApiController::class, 'mapData'])->name('api.map-data');
    });
    
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // İşletmem - Users
    Route::get('/isletmem/kullanicilar', [UserController::class, 'index'])->name('isletmem.kullanicilar');
    Route::post('/isletmem/kullanicilar', [UserController::class, 'store'])->name('users.store');
    Route::get('/isletmem/kullanicilar/create', [UserController::class, 'create'])->name('users.create');
    Route::get('/isletmem/kullanicilar/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/isletmem/kullanicilar/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/isletmem/kullanicilar/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    
    // İşletmem - Other
    Route::get('/isletmem/menu-entegrasyon', [MenuController::class, 'menuIntegration'])->name('isletmem.menu-entegrasyon');
    
    // Müşteri Yönetimi
    Route::get('/musteri', [CustomerController::class, 'index'])->name('musteri.index');
    Route::get('/musteri/create', [CustomerController::class, 'store'])->name('musteri.create');
    Route::post('/musteri', [CustomerController::class, 'store'])->name('musteri.store');
    Route::get('/musteri/{customer}', [CustomerController::class, 'show'])->name('musteri.show');
    Route::put('/musteri/{customer}', [CustomerController::class, 'update'])->name('musteri.update');
    Route::delete('/musteri/{customer}', [CustomerController::class, 'destroy'])->name('musteri.destroy');
    Route::post('/musteri/search-phone', [CustomerController::class, 'searchByPhone'])->name('musteri.search-phone');
    Route::post('/musteri/quick-store', [CustomerController::class, 'quickStore'])->name('musteri.quick-store');
    Route::post('/musteri/{customer}/address', [CustomerController::class, 'addAddress'])->name('musteri.address.store');
    Route::put('/musteri/address/{address}', [CustomerController::class, 'updateAddress'])->name('musteri.address.update');
    Route::delete('/musteri/address/{address}', [CustomerController::class, 'deleteAddress'])->name('musteri.address.destroy');
    
    // Restoran Yönetimi
    Route::get('/restoran', [RestaurantController::class, 'index'])->name('restoran.index');
    Route::get('/restoran/create', [RestaurantController::class, 'create'])->name('restoran.create');
    Route::post('/restoran', [RestaurantController::class, 'store'])->name('restoran.store');
    Route::get('/restoran/{restaurant}', [RestaurantController::class, 'show'])->name('restoran.show');
    Route::get('/restoran/{restaurant}/edit', [RestaurantController::class, 'edit'])->name('restoran.edit');
    Route::put('/restoran/{restaurant}', [RestaurantController::class, 'update'])->name('restoran.update');
    Route::delete('/restoran/{restaurant}', [RestaurantController::class, 'destroy'])->name('restoran.destroy');
    Route::patch('/restoran/{restaurant}/toggle-featured', [RestaurantController::class, 'toggleFeatured'])->name('restoran.toggle-featured');
    Route::post('/restoran/{restaurant}/categories', [RestaurantController::class, 'syncCategories'])->name('restoran.sync-categories');
    
    // Kategori Yönetimi (Güncellendi)
    Route::get('/kategori', [CategoryController::class, 'index'])->name('kategori.index');
    Route::post('/kategori/{category}/restaurants', [CategoryController::class, 'syncRestaurants'])->name('kategori.sync-restaurants');
    
    // Eski müşteri route'u
    Route::get('/isletmem/musteriler', [CustomerController::class, 'index'])->name('isletmem.musteriler');

    // Hesap Ayarları
    Route::get('/ayarlar/genel', [SettingsController::class, 'general'])->name('ayarlar.genel');
    Route::post('/ayarlar/genel', [SettingsController::class, 'updateGeneral'])->name('ayarlar.general.update');
    Route::post('/ayarlar/business', [SettingsController::class, 'updateBusiness'])->name('ayarlar.business.update');
    Route::post('/ayarlar/password', [SettingsController::class, 'updatePassword'])->name('ayarlar.password.update');
    Route::get('/ayarlar/uygulama', [SettingsController::class, 'application'])->name('ayarlar.uygulama');
    Route::post('/ayarlar/uygulama', [SettingsController::class, 'updateApplication'])->name('ayarlar.uygulama.update');
    
    // Ödeme Ayarları
    Route::get('/ayarlar/odeme', [SettingsController::class, 'payment'])->name('ayarlar.odeme');
    Route::post('/ayarlar/odeme', [SettingsController::class, 'updatePayment'])->name('ayarlar.odeme.update');
    
    // Yazıcı Ayarları
    Route::get('/ayarlar/yazici', [SettingsController::class, 'printer'])->name('ayarlar.yazici');
    Route::post('/ayarlar/yazici', [SettingsController::class, 'storePrinter'])->name('ayarlar.yazici.store');
    Route::put('/ayarlar/yazici/{printer}', [SettingsController::class, 'updatePrinter'])->name('ayarlar.yazici.update');
    Route::delete('/ayarlar/yazici/{printer}', [SettingsController::class, 'destroyPrinter'])->name('ayarlar.yazici.destroy');
    Route::post('/ayarlar/yazici/{printer}/test', [SettingsController::class, 'testPrinter'])->name('ayarlar.yazici.test');
    
    // Bildirim Ayarları
    Route::get('/ayarlar/bildirim', [SettingsController::class, 'notification'])->name('ayarlar.bildirim');
    Route::post('/ayarlar/bildirim', [SettingsController::class, 'updateNotification'])->name('ayarlar.notification.update');
    
    // Yazarkasa Ayarları
    Route::get('/ayarlar/yazarkasa', [SettingsController::class, 'cashRegister'])->name('ayarlar.yazarkasa');
    Route::post('/ayarlar/yazarkasa', [SettingsController::class, 'updateCashRegister'])->name('ayarlar.yazarkasa.update');
    Route::post('/ayarlar/yazarkasa/test', [SettingsController::class, 'testCashRegister'])->name('ayarlar.yazarkasa.test');

    // Tema
    Route::get('/tema', [SettingsController::class, 'theme'])->name('tema');
    Route::post('/tema', [SettingsController::class, 'updateTheme'])->name('tema.update');
    
    // Destek
    Route::get('/destek', [\App\Http\Controllers\SupportController::class, 'index'])->name('destek');
    Route::post('/destek', [\App\Http\Controllers\SupportController::class, 'store'])->name('destek.store');
    Route::get('/destek/{ticket}', [\App\Http\Controllers\SupportController::class, 'show'])->name('destek.show');
    Route::post('/destek/{ticket}/reply', [\App\Http\Controllers\SupportController::class, 'reply'])->name('destek.reply');
    Route::post('/destek/{ticket}/close', [\App\Http\Controllers\SupportController::class, 'close'])->name('destek.close');
    Route::post('/destek/{ticket}/reopen', [\App\Http\Controllers\SupportController::class, 'reopen'])->name('destek.reopen');

    // ============================================
    // BAYI PANEL ROUTES
    // ============================================
    Route::prefix('bayi')->name('bayi.')->group(function () {
        Route::get('/harita', [BayiController::class, 'harita'])->name('harita');
        Route::get('/kuryelerim', [BayiController::class, 'kuryelerim'])->name('kuryelerim');
        Route::get('/kuryelerim/yeni', [BayiController::class, 'kuryeEkle'])->name('kurye-ekle');
        Route::get('/kuryelerim/{courier}/duzenle', [BayiController::class, 'kuryeDuzenle'])->name('kurye-duzenle');

        // Kurye Detay Routes
        Route::get('/kuryelerim/{courier}', [BayiController::class, 'kuryeDetay'])->name('kurye-detay');
        Route::put('/kuryelerim/{courier}/ayarlar', [BayiController::class, 'kuryeAyarlarGuncelle'])->name('kurye-ayarlar-guncelle');
        Route::delete('/kuryelerim/{courier}', [BayiController::class, 'kuryeSil'])->name('kurye-sil');
        Route::post('/kurye-pricing-policy-olustur', [BayiController::class, 'kuryePricingPolicyOlustur'])->name('kurye-pricing-policy-olustur');
        Route::delete('/pricing-policy/{pricingPolicy}', [BayiController::class, 'pricingPolicySil'])->name('pricing-policy-sil');
        Route::post('/kuryelerim/{courier}/pricing-policy', [BayiController::class, 'kuryePricingPolicyAta'])->name('kurye-pricing-policy-ata');
        Route::get('/kuryelerim/{courier}/mesai-logs', [BayiController::class, 'kuryeMesaiLogs'])->name('kurye-mesai-logs');
        Route::get('/kuryelerim/{courier}/past-orders', [BayiController::class, 'kuryePastOrders'])->name('kurye-past-orders');
        Route::get('/kuryelerim/{courier}/statistics', [BayiController::class, 'kuryeStatistics'])->name('kurye-statistics');

        // İşletmelerim Routes
        Route::get('/isletmelerim', [BayiController::class, 'isletmelerim'])->name('isletmelerim');
        Route::get('/isletmelerim/yeni', [BayiController::class, 'isletmeEkle'])->name('isletme-ekle');
        Route::post('/isletmelerim', [BayiController::class, 'isletmeKaydet'])->name('isletme-kaydet');
        Route::get('/isletmelerim/{branch}', [BayiController::class, 'isletmeDetay'])->name('isletme-detay');
        Route::get('/isletmelerim/{branch}/duzenle', [BayiController::class, 'isletmeDuzenle'])->name('isletme-duzenle');
        Route::put('/isletmelerim/{branch}', [BayiController::class, 'isletmeGuncelle'])->name('isletme-guncelle');
        Route::delete('/isletmelerim/{branch}', [BayiController::class, 'isletmeSil'])->name('isletme-sil');

        // Branch Settings Routes
        Route::post('/isletmelerim/{branch}/ayarlar', [BayiController::class, 'updateBranchSettings'])->name('isletme.ayarlar');
        Route::post('/isletmelerim/{branch}/bakiye-ekle', [BayiController::class, 'addBranchBalance'])->name('isletme.bakiye-ekle');
        Route::get('/isletmelerim/{branch}/siparisler', [BayiController::class, 'getBranchOrders'])->name('isletme.siparisler');
        Route::get('/isletmelerim/{branch}/istatistikler', [BayiController::class, 'getBranchStatistics'])->name('isletme.istatistikler');
        Route::get('/isletmelerim/{branch}/detayli-istatistikler', [BayiController::class, 'getBranchDetailedStatistics'])->name('isletme.detayli-istatistikler');

        // Pricing Policy Routes
        Route::post('/isletmelerim/{branch}/pricing-policies', [BayiController::class, 'storePricingPolicy'])->name('isletme.pricing-policies.store');
        Route::put('/isletmelerim/{branch}/pricing-policies/{policy}', [BayiController::class, 'updatePricingPolicy'])->name('isletme.pricing-policies.update');
        Route::delete('/isletmelerim/{branch}/pricing-policies/{policy}', [BayiController::class, 'deletePricingPolicy'])->name('isletme.pricing-policies.delete');
        Route::post('/pricing-policies/{policy}/rules', [BayiController::class, 'storePricingPolicyRule'])->name('pricing-policies.rules.store');
        Route::put('/pricing-policy-rules/{rule}', [BayiController::class, 'updatePricingPolicyRule'])->name('pricing-policies.rules.update');
        Route::delete('/pricing-policy-rules/{rule}', [BayiController::class, 'deletePricingPolicyRule'])->name('pricing-policies.rules.delete');

        Route::get('/vardiya-saatleri', [BayiController::class, 'vardiyaSaatleri'])->name('vardiya-saatleri');
        Route::post('/vardiya-saatleri/varsayilan', [BayiController::class, 'vardiyaVarsayilanKaydet'])->name('vardiya-saatleri.varsayilan');
        Route::post('/vardiya-saatleri/toplu-guncelle', [BayiController::class, 'vardiyaTopluGuncelle'])->name('vardiya-saatleri.toplu-guncelle');
        Route::post('/vardiya-saatleri/{courier}', [BayiController::class, 'vardiyaGuncelle'])->name('vardiya-saatleri.guncelle');
        Route::delete('/vardiya-saatleri/{courier}/sil', [BayiController::class, 'vardiyaSil'])->name('vardiya-saatleri.sil');
        Route::post('/vardiya-saatleri/{courier}/kopyala', [BayiController::class, 'vardiyaKopyala'])->name('vardiya-saatleri.kopyala');
        Route::post('/vardiya-saatleri/{courier}/sablon-uygula', [BayiController::class, 'vardiyaSablonUygula'])->name('vardiya-saatleri.sablon-uygula');
        Route::get('/kullanici-yonetimi', [BayiController::class, 'kullaniciYonetimi'])->name('kullanici-yonetimi');
        Route::get('/istatistik', [BayiController::class, 'istatistik'])->name('istatistik');
        Route::get('/gelismis-istatistik', [BayiController::class, 'gelismisIstatistik'])->name('gelismis-istatistik');
        Route::get('/bolgelendirme', [BayiController::class, 'bolgelendirme'])->name('bolgelendirme');
        Route::post('/zones', [BayiController::class, 'zoneStore'])->name('zones.store');
        Route::put('/zones/{zone}', [BayiController::class, 'zoneUpdate'])->name('zones.update');
        Route::delete('/zones/{zone}', [BayiController::class, 'zoneDestroy'])->name('zones.destroy');
        Route::post('/zones/{zone}/courier', [BayiController::class, 'zoneAssignCourier'])->name('zones.assign-courier');
        Route::delete('/zones/{zone}/courier/{courier}', [BayiController::class, 'zoneRemoveCourier'])->name('zones.remove-courier');
        Route::get('/zones/api', [BayiController::class, 'zonesApi'])->name('zones.api');
        Route::get('/zones/{zone}/details', [BayiController::class, 'zoneDetails'])->name('zones.details');
        Route::get('/odemeler/kurye', [BayiController::class, 'kuryeOdemeleri'])->name('odemeler.kurye');
        Route::post('/odemeler/kurye', [BayiController::class, 'kuryeOdemeStore'])->name('kurye-odemeler.store');
        Route::get('/odemeler/isletme', [BayiController::class, 'isletmeOdemeleri'])->name('odemeler.isletme');
        Route::post('/odemeler/isletme/{branch}', [BayiController::class, 'isletmeOdemeStore'])->name('isletme-odemeler.store');
        Route::get('/odemeler/isletme/rapor', [BayiController::class, 'isletmeOdemeRapor'])->name('isletme-odemeler.rapor');
        Route::get('/siparisler/gecmis', [BayiController::class, 'gecmisSiparisler'])->name('siparisler.gecmis');
        Route::get('/siparisler/bedelsiz', [BayiController::class, 'bedelsizIstekler'])->name('siparisler.bedelsiz');
        Route::post('/siparisler/bedelsiz/{order}/approve', [BayiController::class, 'bedelsizApprove'])->name('siparisler.bedelsiz.approve');
        Route::post('/siparisler/bedelsiz/{order}/reject', [BayiController::class, 'bedelsizReject'])->name('siparisler.bedelsiz.reject');
        Route::get('/ayarlar/genel', [BayiController::class, 'ayarlarGenel'])->name('ayarlar.genel');
        Route::post('/ayarlar/genel', [BayiController::class, 'updateGenel'])->name('ayarlar.genel.update');
        Route::get('/ayarlar/kurye', [BayiController::class, 'ayarlarKurye'])->name('ayarlar.kurye');
        Route::post('/ayarlar/kurye', [BayiController::class, 'updateKurye'])->name('ayarlar.kurye.update');
        Route::get('/ayarlar/uygulama', [BayiController::class, 'ayarlarUygulama'])->name('ayarlar.uygulama');
        Route::post('/ayarlar/uygulama', [BayiController::class, 'updateUygulama'])->name('ayarlar.uygulama.update');
        Route::get('/ayarlar/havuz', [BayiController::class, 'ayarlarHavuz'])->name('ayarlar.havuz');
        Route::post('/ayarlar/havuz', [BayiController::class, 'updateHavuz'])->name('ayarlar.havuz.update');
        Route::get('/ayarlar/bildirim', [BayiController::class, 'ayarlarBildirim'])->name('ayarlar.bildirim');
        Route::post('/ayarlar/bildirim', [BayiController::class, 'updateBildirim'])->name('ayarlar.bildirim.update');
        Route::get('/tema', [BayiController::class, 'tema'])->name('tema');
        Route::post('/tema', [BayiController::class, 'updateTheme'])->name('tema.update');
        Route::get('/yardim', [BayiController::class, 'yardim'])->name('yardim');
        
        // Kurye Şifre Yönetimi
        Route::post('/kuryeler/{courier}/sifre', [BayiController::class, 'kuryeSifreAyarla'])->name('kurye.sifre');
        Route::post('/kuryeler/{courier}/app-toggle', [BayiController::class, 'kuryeAppToggle'])->name('kurye.app-toggle');

        // Nakit Ödemeler
        Route::get('/nakit-odemeler', [BayiController::class, 'nakitOdemeler'])->name('nakit-odemeler');
        Route::post('/nakit-odemeler', [BayiController::class, 'nakitOdemeStore'])->name('nakit-odemeler.store');
        Route::post('/nakit-odemeler/{transaction}/cancel', [BayiController::class, 'nakitOdemeCancel'])->name('nakit-odemeler.cancel');
        Route::get('/nakit-odemeler/{courier}/history', [BayiController::class, 'nakitOdemeHistory'])->name('nakit-odemeler.history');
    });
});

// ============================================
// KURYE MOBİL UYGULAMA ROUTES
// ============================================
Route::prefix('kurye')->name('kurye.')->group(function () {
    // Guest Routes (Login)
    Route::middleware('guest:courier')->group(function () {
        Route::get('/giris', [\App\Http\Controllers\Kurye\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/giris', [\App\Http\Controllers\Kurye\AuthController::class, 'login'])->name('login.submit');
    });

    // Authenticated Courier Routes
    Route::middleware('auth:courier')->group(function () {
        Route::post('/cikis', [\App\Http\Controllers\Kurye\AuthController::class, 'logout'])->name('logout');
        
        // Dashboard
        Route::get('/', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'dashboard'])->name('dashboard');
        
        // Orders
        Route::get('/siparisler', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'orders'])->name('orders');
        Route::get('/siparis/{order}', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'orderDetail'])->name('order.detail');
        Route::post('/siparis/{order}/durum', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'updateOrderStatus'])->name('order.updateStatus');
        Route::post('/siparis/{order}/kabul', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'acceptOrder'])->name('order.accept');
        
        // Pool
        Route::get('/havuz', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'pool'])->name('pool');
        
        // History
        Route::get('/gecmis', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'history'])->name('history');
        
        // Profile
        Route::get('/profil', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'profile'])->name('profile');
        
        // Status & Location Updates
        Route::post('/durum', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'updateStatus'])->name('status.update');
        Route::post('/konum', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'updateLocation'])->name('location.update');
        Route::post('/device-token', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'updateDeviceToken'])->name('device.token');
    });
});
