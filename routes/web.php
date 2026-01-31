<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Harita;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Bayi\BayiController;
use App\Http\Controllers\Bayi\BayiCourierController;
use App\Http\Controllers\Bayi\BayiBranchController;
use App\Http\Controllers\Bayi\BayiShiftController;
use App\Http\Controllers\Bayi\BayiZoneController;
use App\Http\Controllers\Bayi\BayiPoolController;
use App\Http\Controllers\Bayi\BayiSettingsController;
use App\Http\Controllers\Bayi\BayiStatsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RestaurantController;
use App\Models\PricingPolicy;
use App\Http\Controllers\TrackingController;

// ============================================
// PUBLIC TRACKING ROUTES (No Auth Required)
// ============================================
Route::get('/takip', [TrackingController::class, 'index'])->name('tracking.index');
Route::post('/takip/ara', [TrackingController::class, 'search'])->name('tracking.search');
Route::get('/tracking/{token}', [TrackingController::class, 'show'])->name('tracking.show');
Route::get('/tracking/{token}/data', [TrackingController::class, 'data'])->name('tracking.data');

// ============================================
// WEBHOOK ROUTES (No Auth Required)
// ============================================
Route::post('/webhooks/{platform}/{token}', [\App\Http\Controllers\IntegrationController::class, 'webhook'])->name('webhooks.handle');

// VOIP Webhooks
Route::post('/voip/webhook', [\App\Http\Controllers\CallController::class, 'webhook'])->name('voip.webhook');
Route::post('/voip/connect/{callLogId}', [\App\Http\Controllers\CallController::class, 'connectWebhook'])->name('voip.webhook.connect');

// ============================================
// CUSTOMER PORTAL ROUTES (OTP Authentication)
// ============================================
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CustomerPortalController::class, 'index'])->name('index');
    Route::post('/send-otp', [\App\Http\Controllers\CustomerPortalController::class, 'sendOtp'])->name('send-otp');
    Route::post('/verify-otp', [\App\Http\Controllers\CustomerPortalController::class, 'verifyOtp'])->name('verify-otp');
    Route::get('/dashboard', [\App\Http\Controllers\CustomerPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [\App\Http\Controllers\CustomerPortalController::class, 'orders'])->name('orders');
    Route::get('/order/{order}', [\App\Http\Controllers\CustomerPortalController::class, 'orderDetail'])->name('order');
    Route::get('/order/{order}/track', [\App\Http\Controllers\CustomerPortalController::class, 'trackOrder'])->name('order.track');
    Route::get('/addresses', [\App\Http\Controllers\CustomerPortalController::class, 'addresses'])->name('addresses');
    Route::get('/logout', [\App\Http\Controllers\CustomerPortalController::class, 'logout'])->name('logout');
});

// Guest Routes (Authentication)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    // Kayıt ol butonu kaldırıldı - Admin manuel kayıt açacak
    // Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    // Route::post('/register', [RegisterController::class, 'register']);

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

    // Impersonation - Bayi paneline geri don
    Route::post('/bayi-paneline-geri-don', [BayiBranchController::class, 'bayiPanelineGeriDon'])->name('bayi.geri-don');

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
    Route::get('/integrations/dashboard', [\App\Http\Controllers\IntegrationController::class, 'dashboard'])->name('integrations.dashboard');
    Route::get('/integrations/{platform}/stats', [\App\Http\Controllers\IntegrationController::class, 'stats'])->name('integrations.stats');
    Route::get('/yonetim/paketler', [\App\Http\Controllers\Yonetim\YonetimController::class, 'paketler'])->name('yonetim.paketler');
    Route::get('/yonetim/urunler', [\App\Http\Controllers\Yonetim\YonetimController::class, 'urunler'])->name('yonetim.urunler');
    Route::post('/yonetim/urunler', [\App\Http\Controllers\Yonetim\YonetimController::class, 'urunStore'])->name('yonetim.urunler.store');
    Route::put('/yonetim/urunler/{product}', [\App\Http\Controllers\Yonetim\YonetimController::class, 'urunUpdate'])->name('yonetim.urunler.update');
    Route::delete('/yonetim/urunler/{product}', [\App\Http\Controllers\Yonetim\YonetimController::class, 'urunDestroy'])->name('yonetim.urunler.destroy');
    Route::get('/yonetim/kartlar', [\App\Http\Controllers\Yonetim\YonetimController::class, 'kartlar'])->name('yonetim.kartlar');
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

    // İşletmem - Categories & Products
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
    
    // Müşteri Yönetimi
    Route::get('/musteri', [CustomerController::class, 'index'])->name('musteri.index');
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
    
    // Bildirim Ayarları
    Route::get('/ayarlar/bildirim', [SettingsController::class, 'notification'])->name('ayarlar.bildirim');
    Route::post('/ayarlar/bildirim', [SettingsController::class, 'updateNotification'])->name('ayarlar.notification.update');
    
    // Tema
    Route::get('/tema', [SettingsController::class, 'theme'])->name('tema');
    Route::post('/tema', [SettingsController::class, 'updateTheme'])->name('tema.update');

    // Yazıcı Ayarları
    Route::get('/ayarlar/yazici', [SettingsController::class, 'printer'])->name('ayarlar.yazici');
    Route::post('/ayarlar/yazici', [SettingsController::class, 'storePrinter'])->name('ayarlar.yazici.store');
    Route::put('/ayarlar/yazici/{printer}', [SettingsController::class, 'updatePrinter'])->name('ayarlar.yazici.update');
    Route::delete('/ayarlar/yazici/{printer}', [SettingsController::class, 'destroyPrinter'])->name('ayarlar.yazici.destroy');
    Route::post('/ayarlar/yazici/{printer}/test', [SettingsController::class, 'testPrinter'])->name('ayarlar.yazici.test');

    // Yazarkasa Ayarları
    Route::get('/ayarlar/yazarkasa', [SettingsController::class, 'cashRegister'])->name('ayarlar.yazarkasa');
    Route::post('/ayarlar/yazarkasa', [SettingsController::class, 'updateCashRegister'])->name('ayarlar.yazarkasa.update');
    Route::post('/ayarlar/yazarkasa/test', [SettingsController::class, 'testCashRegister'])->name('ayarlar.yazarkasa.test');

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
        Route::get('/harita', [\App\Http\Controllers\Bayi\BayiMapController::class, 'harita'])->name('harita');

        // Kurye Routes - BayiCourierController
        Route::get('/kuryelerim', [BayiCourierController::class, 'kuryelerim'])->name('kuryelerim');
        Route::get('/kuryelerim/yeni', [BayiCourierController::class, 'kuryeEkle'])->name('kurye-ekle');
        Route::post('/kuryelerim', [BayiCourierController::class, 'kuryeKaydet'])->name('kurye-kaydet');
        Route::get('/kuryelerim/{courier}/duzenle', [BayiCourierController::class, 'kuryeDuzenle'])->name('kurye-duzenle');
        Route::get('/kuryelerim/{courier}', [BayiCourierController::class, 'kuryeDetay'])->name('kurye-detay');
        Route::put('/kuryelerim/{courier}/ayarlar', [BayiCourierController::class, 'kuryeAyarlarGuncelle'])->name('kurye-ayarlar-guncelle');
        Route::delete('/kuryelerim/{courier}', [BayiCourierController::class, 'kuryeSil'])->name('kurye-sil');
        Route::post('/kurye-pricing-policy-olustur', [BayiCourierController::class, 'kuryePricingPolicyOlustur'])->name('kurye-pricing-policy-olustur');
        Route::delete('/pricing-policy/{pricingPolicy}', [BayiCourierController::class, 'pricingPolicySil'])->name('pricing-policy-sil');
        Route::post('/kuryelerim/{courier}/pricing-policy', [BayiCourierController::class, 'kuryePricingPolicyAta'])->name('kurye-pricing-policy-ata');
        Route::get('/kuryelerim/{courier}/mesai-logs', [BayiCourierController::class, 'kuryeMesaiLogs'])->name('kurye-mesai-logs');
        Route::get('/kuryelerim/{courier}/past-orders', [BayiCourierController::class, 'kuryePastOrders'])->name('kurye-past-orders');
        Route::get('/kuryelerim/{courier}/statistics', [BayiCourierController::class, 'kuryeStatistics'])->name('kurye-statistics');
        Route::post('/kuryeler/{courier}/sifre', [BayiCourierController::class, 'kuryeSifreAyarla'])->name('kurye.sifre');
        Route::post('/kuryeler/{courier}/app-toggle', [BayiCourierController::class, 'kuryeAppToggle'])->name('kurye.app-toggle');
        Route::get('/odemeler/kurye', [BayiCourierController::class, 'kuryeOdemeleri'])->name('odemeler.kurye');
        Route::post('/odemeler/kurye', [BayiCourierController::class, 'kuryeOdemeStore'])->name('kurye-odemeler.store');
        Route::get('/nakit-odemeler', [BayiCourierController::class, 'nakitOdemeler'])->name('nakit-odemeler');
        Route::post('/nakit-odemeler', [BayiCourierController::class, 'nakitOdemeStore'])->name('nakit-odemeler.store');
        Route::post('/nakit-odemeler/{transaction}/cancel', [BayiCourierController::class, 'nakitOdemeCancel'])->name('nakit-odemeler.cancel');
        Route::get('/nakit-odemeler/{courier}/history', [BayiCourierController::class, 'nakitOdemeHistory'])->name('nakit-odemeler.history');

        // Branch/İşletme Routes - BayiBranchController
        Route::get('/isletmelerim', [BayiBranchController::class, 'isletmelerim'])->name('isletmelerim');
        Route::get('/isletmelerim/yeni', [BayiBranchController::class, 'isletmeEkle'])->name('isletme-ekle');
        Route::post('/isletmelerim', [BayiBranchController::class, 'isletmeKaydet'])->name('isletme-kaydet');
        Route::get('/isletmelerim/{branch}', [BayiBranchController::class, 'isletmeDetay'])->name('isletme-detay');
        Route::get('/isletmelerim/{branch}/duzenle', [BayiBranchController::class, 'isletmeDuzenle'])->name('isletme-duzenle');
        Route::put('/isletmelerim/{branch}', [BayiBranchController::class, 'isletmeGuncelle'])->name('isletme-guncelle');
        Route::delete('/isletmelerim/{branch}', [BayiBranchController::class, 'isletmeSil'])->name('isletme-sil');
        Route::post('/isletmelerim/{branch}/giris', [BayiBranchController::class, 'isletmeOlarakGiris'])->name('isletme.giris');
        Route::post('/isletmelerim/{branch}/ayarlar', [BayiBranchController::class, 'updateBranchSettings'])->name('isletme.ayarlar');
        Route::post('/isletmelerim/{branch}/bakiye-ekle', [BayiBranchController::class, 'addBranchBalance'])->name('isletme.bakiye-ekle');
        Route::get('/isletmelerim/{branch}/siparisler', [BayiBranchController::class, 'getBranchOrders'])->name('isletme.siparisler');
        Route::get('/isletmelerim/{branch}/istatistikler', [BayiBranchController::class, 'getBranchStatistics'])->name('isletme.istatistikler');
        Route::get('/isletmelerim/{branch}/detayli-istatistikler', [BayiBranchController::class, 'getBranchDetailedStatistics'])->name('isletme.detayli-istatistikler');
        Route::post('/isletmelerim/{branch}/pricing-policies', [BayiBranchController::class, 'storePricingPolicy'])->name('isletme.pricing-policies.store');
        Route::put('/isletmelerim/{branch}/pricing-policies/{policy}', [BayiBranchController::class, 'updatePricingPolicy'])->name('isletme.pricing-policies.update');
        Route::delete('/isletmelerim/{branch}/pricing-policies/{policy}', [BayiBranchController::class, 'deletePricingPolicy'])->name('isletme.pricing-policies.delete');
        Route::post('/pricing-policies/{policy}/rules', [BayiBranchController::class, 'storePricingPolicyRule'])->name('pricing-policies.rules.store');
        Route::put('/pricing-policy-rules/{rule}', [BayiBranchController::class, 'updatePricingPolicyRule'])->name('pricing-policies.rules.update');
        Route::delete('/pricing-policy-rules/{rule}', [BayiBranchController::class, 'deletePricingPolicyRule'])->name('pricing-policies.rules.delete');
        Route::get('/odemeler/isletme', [BayiBranchController::class, 'isletmeOdemeleri'])->name('odemeler.isletme');
        Route::post('/odemeler/isletme/{branch}', [BayiBranchController::class, 'isletmeOdemeStore'])->name('isletme-odemeler.store');
        Route::get('/odemeler/isletme/rapor', [BayiBranchController::class, 'isletmeOdemeRapor'])->name('isletme-odemeler.rapor');

        // Shift/Vardiya Routes - BayiShiftController
        Route::get('/vardiya-saatleri', [BayiShiftController::class, 'vardiyaSaatleri'])->name('vardiya-saatleri');
        Route::post('/vardiya-saatleri/varsayilan', [BayiShiftController::class, 'vardiyaVarsayilanKaydet'])->name('vardiya-saatleri.varsayilan');
        Route::post('/vardiya-saatleri/toplu-guncelle', [BayiShiftController::class, 'vardiyaTopluGuncelle'])->name('vardiya-saatleri.toplu-guncelle');
        Route::post('/vardiya-saatleri/{courier}', [BayiShiftController::class, 'vardiyaGuncelle'])->name('vardiya-saatleri.guncelle');
        Route::delete('/vardiya-saatleri/{courier}/sil', [BayiShiftController::class, 'vardiyaSil'])->name('vardiya-saatleri.sil');
        Route::post('/vardiya-saatleri/{courier}/kopyala', [BayiShiftController::class, 'vardiyaKopyala'])->name('vardiya-saatleri.kopyala');
        Route::post('/vardiya-saatleri/{courier}/sablon-uygula', [BayiShiftController::class, 'vardiyaSablonUygula'])->name('vardiya-saatleri.sablon-uygula');

        // Zone/Bölgelendirme Routes - BayiZoneController
        Route::get('/bolgelendirme', [BayiZoneController::class, 'bolgelendirme'])->name('bolgelendirme');
        Route::post('/zones', [BayiZoneController::class, 'zoneStore'])->name('zones.store');
        Route::put('/zones/{zone}', [BayiZoneController::class, 'zoneUpdate'])->name('zones.update');
        Route::delete('/zones/{zone}', [BayiZoneController::class, 'zoneDestroy'])->name('zones.destroy');
        Route::post('/zones/{zone}/courier', [BayiZoneController::class, 'zoneAssignCourier'])->name('zones.assign-courier');
        Route::delete('/zones/{zone}/courier/{courier}', [BayiZoneController::class, 'zoneRemoveCourier'])->name('zones.remove-courier');
        Route::get('/zones/api', [BayiZoneController::class, 'zonesApi'])->name('zones.api');
        Route::get('/zones/{zone}/details', [BayiZoneController::class, 'zoneDetails'])->name('zones.details');

        // Pool/Havuz Routes - BayiPoolController
        Route::get('/havuz', [BayiPoolController::class, 'poolDashboard'])->name('havuz');
        Route::post('/havuz/{order}/ata', [BayiPoolController::class, 'poolAssign'])->name('havuz.ata');
        Route::post('/havuz/{order}/otomatik-ata', [BayiPoolController::class, 'poolAutoAssign'])->name('havuz.otomatik-ata');
        Route::post('/havuz/toplu-ata', [BayiPoolController::class, 'poolBulkAssign'])->name('havuz.toplu-ata');
        Route::get('/havuz/istatistik', [BayiPoolController::class, 'poolStats'])->name('havuz.istatistik');

        // Settings Routes - BayiSettingsController
        Route::get('/ayarlar/genel', [BayiSettingsController::class, 'ayarlarGenel'])->name('ayarlar.genel');
        Route::post('/ayarlar/genel', [BayiSettingsController::class, 'updateGenel'])->name('ayarlar.genel.update');
        Route::get('/ayarlar/kurye', [BayiSettingsController::class, 'ayarlarKurye'])->name('ayarlar.kurye');
        Route::post('/ayarlar/kurye', [BayiSettingsController::class, 'updateKurye'])->name('ayarlar.kurye.update');
        Route::get('/ayarlar/uygulama', [BayiSettingsController::class, 'ayarlarUygulama'])->name('ayarlar.uygulama');
        Route::post('/ayarlar/uygulama', [BayiSettingsController::class, 'updateUygulama'])->name('ayarlar.uygulama.update');
        Route::get('/ayarlar/havuz', [BayiSettingsController::class, 'ayarlarHavuz'])->name('ayarlar.havuz');
        Route::post('/ayarlar/havuz', [BayiSettingsController::class, 'updateHavuz'])->name('ayarlar.havuz.update');
        Route::get('/ayarlar/bildirim', [BayiSettingsController::class, 'ayarlarBildirim'])->name('ayarlar.bildirim');
        Route::post('/ayarlar/bildirim', [BayiSettingsController::class, 'updateBildirim'])->name('ayarlar.bildirim.update');
        Route::get('/tema', [BayiSettingsController::class, 'tema'])->name('tema');
        Route::post('/tema', [BayiSettingsController::class, 'updateTheme'])->name('tema.update');
        Route::get('/ayarlar/trendyol', [BayiSettingsController::class, 'ayarlarTrendyol'])->name('ayarlar.trendyol');
        Route::post('/ayarlar/trendyol/status', [BayiSettingsController::class, 'updateTrendyolStatus'])->name('ayarlar.trendyol.status');
        Route::post('/ayarlar/trendyol/delivery-time', [BayiSettingsController::class, 'updateTrendyolDeliveryTime'])->name('ayarlar.trendyol.delivery-time');
        Route::post('/ayarlar/trendyol/working-hours', [BayiSettingsController::class, 'updateTrendyolWorkingHours'])->name('ayarlar.trendyol.working-hours');
        Route::post('/ayarlar/trendyol/section-status', [BayiSettingsController::class, 'updateTrendyolSectionStatus'])->name('ayarlar.trendyol.section-status');
        Route::post('/ayarlar/trendyol/product-status', [BayiSettingsController::class, 'updateTrendyolProductStatus'])->name('ayarlar.trendyol.product-status');
        Route::get('/trendyol/orders', [BayiSettingsController::class, 'getTrendyolOrders'])->name('trendyol.orders');
        Route::post('/trendyol/orders/accept', [BayiSettingsController::class, 'acceptTrendyolOrder'])->name('trendyol.orders.accept');
        Route::post('/trendyol/orders/prepare', [BayiSettingsController::class, 'prepareTrendyolOrder'])->name('trendyol.orders.prepare');
        Route::post('/trendyol/orders/ship', [BayiSettingsController::class, 'shipTrendyolOrder'])->name('trendyol.orders.ship');
        Route::post('/trendyol/orders/deliver', [BayiSettingsController::class, 'deliverTrendyolOrder'])->name('trendyol.orders.deliver');
        Route::post('/trendyol/orders/cancel', [BayiSettingsController::class, 'cancelTrendyolOrder'])->name('trendyol.orders.cancel');
        Route::post('/trendyol/orders/invoice', [BayiSettingsController::class, 'sendTrendyolInvoice'])->name('trendyol.orders.invoice');

        // Statistics Routes - BayiStatsController
        Route::get('/kullanici-yonetimi', [BayiStatsController::class, 'kullaniciYonetimi'])->name('kullanici-yonetimi');
        Route::get('/kullanici-ekle', [BayiStatsController::class, 'kullaniciEkle'])->name('kullanici-ekle');
        Route::post('/kullanici-ekle', [BayiStatsController::class, 'kullaniciKaydet'])->name('kullanici-kaydet');
        Route::get('/kullanici/{user}/duzenle', [BayiStatsController::class, 'kullaniciDuzenle'])->name('kullanici-duzenle');
        Route::put('/kullanici/{user}', [BayiStatsController::class, 'kullaniciGuncelle'])->name('kullanici-guncelle');
        Route::get('/istatistik', [BayiStatsController::class, 'istatistik'])->name('istatistik');
        Route::get('/gelismis-istatistik', [BayiStatsController::class, 'gelismisIstatistik'])->name('gelismis-istatistik');

        // Misc Routes - BayiController
        Route::get('/siparisler/gecmis', [BayiController::class, 'gecmisSiparisler'])->name('siparisler.gecmis');
        Route::get('/siparisler/bedelsiz', [BayiController::class, 'bedelsizIstekler'])->name('siparisler.bedelsiz');
        Route::post('/siparisler/bedelsiz/{order}/approve', [BayiController::class, 'bedelsizApprove'])->name('siparisler.bedelsiz.approve');
        Route::post('/siparisler/bedelsiz/{order}/reject', [BayiController::class, 'bedelsizReject'])->name('siparisler.bedelsiz.reject');
        Route::get('/paketler', [BayiController::class, 'paketler'])->name('paketler');

        // Kuryelere Bildirim
        Route::get('/kuryelere-bildirim', [\App\Http\Controllers\Bayi\BayiNotificationController::class, 'index'])->name('kuryelere-bildirim');
        Route::post('/kuryelere-bildirim', [\App\Http\Controllers\Bayi\BayiNotificationController::class, 'send'])->name('kuryelere-bildirim.send');

        // VOIP/Twilio Ayarları
        Route::get('/ayarlar/voip/verify', [\App\Http\Controllers\CallController::class, 'verifyTwilio'])->name('ayarlar.voip.verify');
        Route::get('/ayarlar/voip/numbers', [\App\Http\Controllers\CallController::class, 'listNumbers'])->name('ayarlar.voip.numbers');

        // Finansal Raporlar
        Route::get('/finans', [\App\Http\Controllers\Bayi\FinansController::class, 'index'])->name('finans.index');
        Route::get('/finans/kurye-kazanc', [\App\Http\Controllers\Bayi\FinansController::class, 'kuryeKazanc'])->name('finans.kurye-kazanc');
        Route::get('/finans/sube-performans', [\App\Http\Controllers\Bayi\FinansController::class, 'subePerformans'])->name('finans.sube-performans');
        Route::get('/finans/nakit-akis', [\App\Http\Controllers\Bayi\FinansController::class, 'nakitAkis'])->name('finans.nakit-akis');
        Route::get('/finans/api', [\App\Http\Controllers\Bayi\FinansController::class, 'apiData'])->name('finans.api');
        Route::get('/finans/export', [\App\Http\Controllers\Bayi\FinansController::class, 'export'])->name('finans.export');

        // Gelismis Vardiya Yonetimi
        Route::get('/vardiya/analytics', [\App\Http\Controllers\Bayi\ShiftController::class, 'analytics'])->name('vardiya.analytics');
        Route::get('/vardiya/kurye/{courier}/durum', [\App\Http\Controllers\Bayi\ShiftController::class, 'courierStatus'])->name('vardiya.kurye.durum');
        Route::get('/vardiya/kurye/{courier}/oneri', [\App\Http\Controllers\Bayi\ShiftController::class, 'suggest'])->name('vardiya.kurye.oneri');
        Route::post('/vardiya/kurye/{courier}/sablon', [\App\Http\Controllers\Bayi\ShiftController::class, 'applyTemplate'])->name('vardiya.kurye.sablon');
        Route::post('/vardiya/kurye/{courier}/cakisma-kontrol', [\App\Http\Controllers\Bayi\ShiftController::class, 'checkConflicts'])->name('vardiya.kurye.cakisma');
        Route::get('/vardiya/saatlik', [\App\Http\Controllers\Bayi\ShiftController::class, 'hourlyData'])->name('vardiya.saatlik');
        Route::get('/vardiya/istatistik', [\App\Http\Controllers\Bayi\ShiftController::class, 'statistics'])->name('vardiya.istatistik');
        Route::post('/vardiya/toplu-sablon', [\App\Http\Controllers\Bayi\ShiftController::class, 'bulkApplyTemplate'])->name('vardiya.toplu-sablon');

        // Siparis Analitik Dashboard
        Route::get('/analytics', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/weekly', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'weeklyComparison'])->name('analytics.weekly');
        Route::get('/analytics/branches', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'branchComparison'])->name('analytics.branches');
        Route::get('/analytics/heatmap', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'heatmap'])->name('analytics.heatmap');
        Route::get('/analytics/api/realtime', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'realTimeApi'])->name('analytics.api.realtime');
        Route::get('/analytics/api/hourly', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'hourlyApi'])->name('analytics.api.hourly');
        Route::get('/analytics/api/daily', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'dailyTrendApi'])->name('analytics.api.daily');
        Route::get('/analytics/api/couriers', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'courierPerformanceApi'])->name('analytics.api.couriers');
        Route::get('/analytics/api/heatmap', [\App\Http\Controllers\Bayi\OrderAnalyticsController::class, 'heatmapApi'])->name('analytics.api.heatmap');
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

        // POD (Proof of Delivery)
        Route::get('/siparis/{order}/teslim', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'deliverPage'])->name('order.deliver');
        Route::post('/siparis/{order}/pod', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'uploadPod'])->name('order.pod.upload');
        Route::get('/siparis/{order}/pod', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'getPod'])->name('order.pod.get');

        // VOIP Call
        Route::post('/siparis/{order}/ara', [\App\Http\Controllers\CallController::class, 'callCustomer'])->name('order.call');
        Route::get('/siparis/{order}/aramalar', [\App\Http\Controllers\CallController::class, 'getLog'])->name('order.calls');

        // Route Optimization
        Route::get('/rota', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'routePage'])->name('route');
        Route::post('/rota/optimize', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'optimizeRoute'])->name('route.optimize');
        Route::put('/rota/reorder', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'reorderRoute'])->name('route.reorder');

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
        Route::post('/pil', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'updateBattery'])->name('battery.update');

        // Offline Sync
        Route::post('/sync', [\App\Http\Controllers\Kurye\KuryeAppController::class, 'bulkSync'])->name('sync');
    });
});

// ============================================
// ADMIN PANEL ROUTES
// ============================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('dashboard');

    // Bayiler
    Route::get('/bayiler', [\App\Http\Controllers\Admin\AdminController::class, 'bayiler'])->name('bayiler.index');
    Route::get('/bayiler/create', [\App\Http\Controllers\Admin\AdminController::class, 'bayiCreate'])->name('bayiler.create');
    Route::post('/bayiler', [\App\Http\Controllers\Admin\AdminController::class, 'bayiStore'])->name('bayiler.store');
    Route::get('/bayiler/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'bayiShow'])->name('bayiler.show');
    Route::get('/bayiler/{user}/edit', [\App\Http\Controllers\Admin\AdminController::class, 'bayiEdit'])->name('bayiler.edit');
    Route::put('/bayiler/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'bayiUpdate'])->name('bayiler.update');
    Route::delete('/bayiler/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'bayiDestroy'])->name('bayiler.destroy');

    // Kullanicilar
    Route::get('/kullanicilar', [\App\Http\Controllers\Admin\AdminController::class, 'kullanicilar'])->name('kullanicilar.index');
    Route::post('/kullanicilar', [\App\Http\Controllers\Admin\AdminController::class, 'kullaniciStore'])->name('kullanicilar.store');
    Route::put('/kullanicilar/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'kullaniciUpdate'])->name('kullanicilar.update');
    Route::delete('/kullanicilar/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'kullaniciDestroy'])->name('kullanicilar.destroy');

    // Subeler
    Route::get('/subeler', [\App\Http\Controllers\Admin\AdminController::class, 'subeler'])->name('subeler.index');
    Route::post('/subeler', [\App\Http\Controllers\Admin\AdminController::class, 'subeStore'])->name('subeler.store');
    Route::put('/subeler/{branch}', [\App\Http\Controllers\Admin\AdminController::class, 'subeUpdate'])->name('subeler.update');
    Route::delete('/subeler/{branch}', [\App\Http\Controllers\Admin\AdminController::class, 'subeDestroy'])->name('subeler.destroy');

    // Kuryeler
    Route::get('/kuryeler', [\App\Http\Controllers\Admin\AdminController::class, 'kuryeler'])->name('kuryeler.index');
    Route::get('/kuryeler/{courier}', [\App\Http\Controllers\Admin\AdminController::class, 'kuryeShow'])->name('kuryeler.show');
    Route::put('/kuryeler/{courier}', [\App\Http\Controllers\Admin\AdminController::class, 'kuryeUpdate'])->name('kuryeler.update');

    // Siparisler
    Route::get('/siparisler', [\App\Http\Controllers\Admin\AdminController::class, 'siparisler'])->name('siparisler.index');
    Route::get('/siparisler/{order}', [\App\Http\Controllers\Admin\AdminController::class, 'siparisShow'])->name('siparisler.show');

    // Entegrasyonlar
    Route::get('/entegrasyonlar', [\App\Http\Controllers\Admin\AdminController::class, 'entegrasyonlar'])->name('entegrasyonlar.index');

    // Islemler
    Route::get('/islemler', [\App\Http\Controllers\Admin\AdminController::class, 'islemler'])->name('islemler.index');

    // Destek
    Route::get('/destek', [\App\Http\Controllers\Admin\AdminController::class, 'destek'])->name('destek.index');
    Route::get('/destek/{ticket}', [\App\Http\Controllers\Admin\AdminController::class, 'destekShow'])->name('destek.show');
    Route::post('/destek/{ticket}/reply', [\App\Http\Controllers\Admin\AdminController::class, 'destekReply'])->name('destek.reply');
    Route::put('/destek/{ticket}/status', [\App\Http\Controllers\Admin\AdminController::class, 'destekUpdateStatus'])->name('destek.status');

    // Planlar (Abonelik Paketleri)
    Route::get('/planlar', [\App\Http\Controllers\Admin\AdminController::class, 'planlar'])->name('planlar.index');
    Route::post('/planlar', [\App\Http\Controllers\Admin\AdminController::class, 'planStore'])->name('planlar.store');
    Route::put('/planlar/{plan}', [\App\Http\Controllers\Admin\AdminController::class, 'planUpdate'])->name('planlar.update');
    Route::delete('/planlar/{plan}', [\App\Http\Controllers\Admin\AdminController::class, 'planDestroy'])->name('planlar.destroy');
});

// ============================================
// OAUTH2 ROUTES (For External Platform Integration)
// ============================================
Route::prefix('oauth')->name('oauth.')->group(function () {
    // Authorization endpoint (can be accessed without auth for initial redirect)
    Route::get('/authorize', [\App\Http\Controllers\Api\OAuthController::class, 'authorize'])->name('authorize');

    // These routes require authentication
    Route::middleware('auth')->group(function () {
        Route::get('/authorize/show', [\App\Http\Controllers\Api\OAuthController::class, 'showAuthorize'])->name('authorize.show');
        Route::post('/authorize/approve', [\App\Http\Controllers\Api\OAuthController::class, 'approveAuthorize'])->name('authorize.approve');
        Route::post('/authorize/deny', [\App\Http\Controllers\Api\OAuthController::class, 'denyAuthorize'])->name('authorize.deny');
        Route::post('/revoke', [\App\Http\Controllers\Api\OAuthController::class, 'revoke'])->name('revoke');
    });

    // Token endpoint (no auth required, uses client credentials)
    // Rate limited to prevent brute-force attacks (10 requests per minute)
    Route::post('/token', [\App\Http\Controllers\Api\OAuthController::class, 'token'])
        ->middleware('throttle:10,1')
        ->name('token');
});
