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
use App\Http\Controllers\Bayi\BayiController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RestaurantController;

// Guest Routes (Authentication)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset (placeholder routes)
    Route::get('/forgot-password', function () {
        return view('auth.login');
    })->name('password.request');
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
    
    // API endpoints for map data
    // API endpoint for searching couriers
    Route::get('/api/couriers/search', function (\Illuminate\Http\Request $request) {
        $query = $request->input('q', '');
        $status = $request->input('status');
        
        $couriers = \App\Models\Courier::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%")
                      ->orWhere('vehicle_plate', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'lat' => (float) $c->lat,
                'lng' => (float) $c->lng,
                'status' => $c->status,
                'status_label' => $c->getStatusLabel(),
                'vehicle_plate' => $c->vehicle_plate,
                'active_orders_count' => $c->active_orders_count,
            ]);
        
        return response()->json($couriers);
    })->name('api.couriers.search');
    
    // API endpoint for searching orders
    Route::get('/api/orders/search', function (\Illuminate\Http\Request $request) {
        $query = $request->input('q', '');
        $status = $request->input('status');
        
        $orders = \App\Models\Order::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('order_number', 'like', "%{$query}%")
                      ->orWhere('customer_name', 'like', "%{$query}%")
                      ->orWhere('customer_phone', 'like', "%{$query}%")
                      ->orWhere('customer_address', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($q) use ($status) {
                if ($status === 'active') {
                    $q->whereNotIn('status', ['delivered', 'cancelled']);
                } else {
                    $q->where('status', $status);
                }
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('courier')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name,
                'customer_phone' => $o->customer_phone,
                'customer_address' => $o->customer_address,
                'lat' => (float) $o->lat,
                'lng' => (float) $o->lng,
                'status' => $o->status,
                'status_label' => $o->getStatusLabel(),
                'total' => $o->total,
                'courier_name' => $o->courier?->name,
                'created_at' => $o->created_at->diffForHumans(),
            ]);
        
        return response()->json($orders);
    })->name('api.orders.search');

    Route::get('/api/map-data', function () {
        $couriers = \App\Models\Courier::whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'lat' => (float) $c->lat,
                'lng' => (float) $c->lng,
                'status' => $c->status,
                'vehicle_plate' => $c->vehicle_plate,
                'active_orders_count' => $c->active_orders_count,
            ]);
        
        $orders = \App\Models\Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('courier')
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name,
                'customer_address' => $o->customer_address,
                'lat' => (float) $o->lat,
                'lng' => (float) $o->lng,
                'status' => $o->status,
                'total' => $o->total,
                'courier_name' => $o->courier?->name,
            ]);
        
        $stats = [
            'pending' => \App\Models\Order::where('status', 'pending')->count(),
            'active' => \App\Models\Order::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'pool' => \App\Models\Order::where('status', 'ready')->whereNull('courier_id')->count(),
            'cancelled' => \App\Models\Order::where('status', 'cancelled')->whereDate('created_at', today())->count(),
        ];
        
        return response()->json([
            'couriers' => $couriers,
            'orders' => $orders,
            'stats' => $stats,
        ]);
    })->name('api.map-data');
    
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
    Route::get('/ayarlar/odeme', [SettingsController::class, 'payment'])->name('ayarlar.odeme');
    Route::get('/ayarlar/yazici', [SettingsController::class, 'printer'])->name('ayarlar.yazici');
    Route::get('/ayarlar/bildirim', [SettingsController::class, 'notification'])->name('ayarlar.bildirim');
    Route::post('/ayarlar/bildirim', [SettingsController::class, 'updateNotification'])->name('ayarlar.notification.update');
    Route::get('/ayarlar/yazarkasa', [SettingsController::class, 'cashRegister'])->name('ayarlar.yazarkasa');

    // Tema & Destek
    Route::get('/tema', [SettingsController::class, 'theme'])->name('tema');
    Route::get('/destek', [SettingsController::class, 'support'])->name('destek');

    // ============================================
    // BAYI PANEL ROUTES
    // ============================================
    Route::prefix('bayi')->name('bayi.')->group(function () {
        Route::get('/harita', [BayiController::class, 'harita'])->name('harita');
        Route::get('/kuryelerim', [BayiController::class, 'kuryelerim'])->name('kuryelerim');
        Route::get('/kuryelerim/yeni', [BayiController::class, 'kuryeEkle'])->name('kurye-ekle');
        Route::get('/kuryelerim/{courier}/duzenle', [BayiController::class, 'kuryeDuzenle'])->name('kurye-duzenle');
        
        // İşletmelerim Routes
        Route::get('/isletmelerim', [BayiController::class, 'isletmelerim'])->name('isletmelerim');
        Route::get('/isletmelerim/yeni', [BayiController::class, 'isletmeEkle'])->name('isletme-ekle');
        Route::post('/isletmelerim', [BayiController::class, 'isletmeKaydet'])->name('isletme-kaydet');
        Route::get('/isletmelerim/{branch}', [BayiController::class, 'isletmeDetay'])->name('isletme-detay');
        Route::get('/isletmelerim/{branch}/duzenle', [BayiController::class, 'isletmeDuzenle'])->name('isletme-duzenle');
        Route::put('/isletmelerim/{branch}', [BayiController::class, 'isletmeGuncelle'])->name('isletme-guncelle');
        Route::delete('/isletmelerim/{branch}', [BayiController::class, 'isletmeSil'])->name('isletme-sil');

        Route::get('/vardiya-saatleri', [BayiController::class, 'vardiyaSaatleri'])->name('vardiya-saatleri');
        Route::post('/vardiya-saatleri/varsayilan', [BayiController::class, 'vardiyaVarsayilanKaydet'])->name('vardiya-saatleri.varsayilan');
        Route::post('/vardiya-saatleri/toplu-guncelle', [BayiController::class, 'vardiyaTopluGuncelle'])->name('vardiya-saatleri.toplu-guncelle');
        Route::post('/vardiya-saatleri/{courier}', [BayiController::class, 'vardiyaGuncelle'])->name('vardiya-saatleri.guncelle');
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
        Route::get('/odemeler/isletme', [BayiController::class, 'isletmeOdemeleri'])->name('odemeler.isletme');
        Route::get('/siparisler/gecmis', [BayiController::class, 'gecmisSiparisler'])->name('siparisler.gecmis');
        Route::get('/siparisler/bedelsiz', [BayiController::class, 'bedelsizIstekler'])->name('siparisler.bedelsiz');
        Route::get('/ayarlar/genel', [BayiController::class, 'ayarlarGenel'])->name('ayarlar.genel');
        Route::get('/ayarlar/kurye', [BayiController::class, 'ayarlarKurye'])->name('ayarlar.kurye');
        Route::get('/ayarlar/uygulama', [BayiController::class, 'ayarlarUygulama'])->name('ayarlar.uygulama');
        Route::get('/ayarlar/havuz', [BayiController::class, 'ayarlarHavuz'])->name('ayarlar.havuz');
        Route::get('/ayarlar/bildirim', [BayiController::class, 'ayarlarBildirim'])->name('ayarlar.bildirim');
        Route::get('/tema', [BayiController::class, 'tema'])->name('tema');
        Route::get('/yardim', [BayiController::class, 'yardim'])->name('yardim');
    });
});
