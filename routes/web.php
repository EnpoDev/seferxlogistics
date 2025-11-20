<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Harita;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

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

    // Ana Sayfa - Redirect to Harita
    Route::get('/', function () {
        return redirect()->route('harita');
    });

    // Harita & Kurye Takip
    Route::get('/harita', Harita::class)->name('harita');

    // Sipariş Yönetimi
    Route::get('/siparis/aktif', function () {
        return view('pages.siparis.aktif');
    })->name('siparis.aktif');

    Route::get('/siparis/gecmis', function () {
        return view('pages.siparis.gecmis');
    })->name('siparis.gecmis');

    Route::get('/siparis/iptal', function () {
        return view('pages.siparis.iptal');
    })->name('siparis.iptal');

    Route::get('/siparis/istatistik', function () {
        return view('pages.siparis.istatistik');
    })->name('siparis.istatistik');

    // Gelişmiş İstatistik
    Route::get('/gelismis-istatistik', function () {
        return view('pages.gelismis-istatistik');
    })->name('gelismis-istatistik');

    // Yönetim
    Route::get('/yonetim/kullanicilar', function () {
        return view('pages.yonetim.kullanicilar');
    })->name('yonetim.kullanicilar');

    Route::get('/yonetim/roller', function () {
        return view('pages.yonetim.roller');
    })->name('yonetim.roller');

    // Menü Yönetimi
    Route::get('/menu', function () {
        return view('pages.menu');
    })->name('menu');

    // İşletmem
    Route::get('/isletmem/bilgiler', function () {
        return view('pages.isletmem.bilgiler');
    })->name('isletmem.bilgiler');

    Route::get('/isletmem/subeler', function () {
        return view('pages.isletmem.subeler');
    })->name('isletmem.subeler');

    // Hesap Ayarları
    Route::get('/hesap/profil', function () {
        return view('pages.hesap.profil');
    })->name('hesap.profil');

    Route::get('/hesap/guvenlik', function () {
        return view('pages.hesap.guvenlik');
    })->name('hesap.guvenlik');
});
