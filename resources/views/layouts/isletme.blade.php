@php
    $themeSettings = \App\Models\ThemeSetting::where('user_id', auth()->id())->first();
    $themeMode = $themeSettings?->theme_mode ?? 'system';
    $compactMode = $themeSettings?->compact_mode ?? false;
    $animationsEnabled = $themeSettings?->animations_enabled ?? true;
    $sidebarAutoHide = $themeSettings?->sidebar_auto_hide ?? true;
    $sidebarWidth = $themeSettings?->sidebar_width ?? 'normal';
    $initialDarkMode = $themeMode === 'dark' ? 'true' : ($themeMode === 'light' ? 'false' : "window.matchMedia('(prefers-color-scheme: dark)').matches");

    // Server-side dark mode class determination for FOUC prevention
    $serverDarkClass = '';
    if ($themeMode === 'dark') {
        $serverDarkClass = 'dark';
    }

    // Sidebar width classes
    $sidebarWidthClass = match($sidebarWidth) {
        'narrow' => 'w-52',
        'wide' => 'w-80',
        default => 'w-64',
    };
@endphp
<!DOCTYPE html>
<html lang="tr" class="{{ $serverDarkClass }} {{ $compactMode ? 'compact-mode' : '' }} {{ !$animationsEnabled ? 'no-animations' : '' }}"
      x-data="themeManager()"
      x-init="init()"
      :class="{ 'dark': darkMode, 'compact-mode': compactMode, 'no-animations': !animationsEnabled }">
<head>
    <!-- Prevent FOUC (Flash of Unstyled Content) for dark mode -->
    <script>
        (function() {
            var themeMode = '{{ $themeMode }}';
            var isDark = themeMode === 'dark' || (themeMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
        })();
    </script>
    <script>
        function themeManager() {
            return {
                darkMode: {!! $initialDarkMode !!},
                sidebarOpen: {!! $sidebarAutoHide ? 'window.innerWidth >= 1024' : 'true' !!},
                openDropdown: null,
                themeMode: '{{ $themeMode }}',
                compactMode: {!! $compactMode ? 'true' : 'false' !!},
                animationsEnabled: {!! $animationsEnabled ? 'true' : 'false' !!},
                sidebarAutoHide: {!! $sidebarAutoHide ? 'true' : 'false' !!},
                init() {
                    // Watch for system theme changes if theme mode is 'system'
                    if (this.themeMode === 'system') {
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                            if (this.themeMode === 'system') this.darkMode = e.matches;
                        });
                    }
                    // Handle sidebar auto-hide on resize
                    if (this.sidebarAutoHide) {
                        window.addEventListener('resize', () => {
                            this.sidebarOpen = window.innerWidth >= 1024;
                        });
                    }
                    this.$watch('darkMode', val => localStorage.setItem('darkMode', val));
                }
            }
        }
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SeferX Lojistik' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style>
        .dark { background-color: #181818; }
        
        /* Compact Mode */
        .compact-mode .p-6 { padding: 1rem; }
        .compact-mode .p-4 { padding: 0.75rem; }
        .compact-mode .px-4 { padding-left: 0.75rem; padding-right: 0.75rem; }
        .compact-mode .py-4 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .compact-mode .px-3 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .compact-mode .py-3 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .compact-mode .py-2 { padding-top: 0.375rem; padding-bottom: 0.375rem; }
        .compact-mode .space-y-4 > * + * { margin-top: 0.75rem; }
        .compact-mode .space-y-6 > * + * { margin-top: 1rem; }
        .compact-mode .gap-4 { gap: 0.75rem; }
        .compact-mode .gap-6 { gap: 1rem; }
        .compact-mode .mb-6 { margin-bottom: 1rem; }
        .compact-mode .mb-4 { margin-bottom: 0.75rem; }
        .compact-mode .text-2xl { font-size: 1.25rem; }
        .compact-mode .text-xl { font-size: 1.125rem; }
        .compact-mode .h-16 { height: 3.5rem; }
        
        /* No Animations */
        .no-animations *, .no-animations *::before, .no-animations *::after {
            animation-duration: 0s !important;
            animation-delay: 0s !important;
            transition-duration: 0s !important;
            transition-delay: 0s !important;
        }
        
        /* Custom Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.2s ease-out;
        }
        
        .animate-slideUp {
            animation: slideUp 0.3s ease-out;
        }
        
        .animate-slideDown {
            animation: slideDown 0.3s ease-out;
        }
        
        .animate-pulse-slow {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        /* Smooth transitions for all interactive elements */
        button, a, input, select, textarea {
            transition: all 0.2s ease;
        }
        
        /* Loading spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        
        /* Skeleton loader */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }
        
        .dark .skeleton {
            background: linear-gradient(90deg, #2a2a2a 25%, #3a3a3a 50%, #2a2a2a 75%);
            background-size: 1000px 100%;
        }
        
        /* Better focus states */
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        
        .dark input:focus, .dark select:focus, .dark textarea:focus {
            border-color: #fff;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }
        
        /* Ripple effect */
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .ripple:active::after {
            width: 300px;
            height: 300px;
        }
    </style>
</head>
<body class="bg-white dark:bg-[#181818] transition-colors duration-200">
    {{-- Impersonation Bar --}}
    <x-impersonation-bar />

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 {{ $sidebarWidthClass }} bg-white dark:bg-[#181818] border-r border-gray-200 dark:border-gray-800 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static flex flex-col">

            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-20 px-4 border-b border-gray-200 dark:border-gray-800">
            <a href="{{ route('dashboard') }}" class="flex mr-10 items-center space-x-2">
                    <img src="{{ asset('logo-dark.png') }}" alt="SeferX Logo" class="h-16 w-auto dark:hidden">
                    <img src="{{ asset('logo-white.png') }}" alt="SeferX Logo" class="h-16 w-auto hidden dark:block">
                </a>
            </div>

            <!-- İşletme Adı -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                <p class="text-sm font-medium text-black dark:text-white">{{ auth()->user()->branch->name ?? auth()->user()->name }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">İşletme</p>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">

                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>

                <!-- Harita -->
                <a href="{{ route('harita') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('harita') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <span class="font-medium">Harita</span>
                </a>

                <!-- Sipariş Yönetimi -->
                <div class="space-y-1">
                    <button @click="openDropdown = openDropdown === 'siparis' ? null : 'siparis'"
                            :class="{ 'bg-gray-100 dark:bg-gray-900': openDropdown === 'siparis' || {{ request()->is('siparis*') ? 'true' : 'false' }} }"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <span class="font-medium">Sipariş Yönetimi</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'siparis' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openDropdown === 'siparis' || {{ request()->is('siparis*') ? 'true' : 'false' }}"
                         x-collapse
                         class="ml-8 space-y-1">
                        <a href="{{ route('siparis.liste') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.liste') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Sipariş
                        </a>
                        <a href="{{ route('siparis.gecmis') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.gecmis') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Geçmiş
                        </a>
                        <a href="{{ route('siparis.iptal') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.iptal') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            İptal Edilenler
                        </a>
                        <a href="{{ route('siparis.istatistik') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.istatistik') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            İstatistik
                        </a>
                    </div>
                </div>

                <!-- Yönetim -->
                <div class="space-y-1">
                    <button @click="openDropdown = openDropdown === 'yonetim' ? null : 'yonetim'"
                            :class="{ 'bg-gray-100 dark:bg-gray-900': openDropdown === 'yonetim' || {{ request()->is('yonetim*') ? 'true' : 'false' }} }"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="font-medium">Yönetim</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'yonetim' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openDropdown === 'yonetim' || {{ request()->is('yonetim*') ? 'true' : 'false' }}"
                         x-collapse
                         class="ml-8 space-y-1">
                        <a href="{{ route('yonetim.entegrasyonlar') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('yonetim.entegrasyonlar') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Entegrasyonlar
                        </a>
                        <a href="{{ route('yonetim.paketler') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('yonetim.paketler') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Paketler
                        </a>
                        <a href="{{ route('yonetim.urunler') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('yonetim.urunler') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Ürünler
                        </a>
                        <a href="{{ route('yonetim.kartlar') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('yonetim.kartlar') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Kayıtlı Kartlarım
                        </a>
                        <a href="{{ route('yonetim.islemler') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('yonetim.islemler') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            İşlemlerim
                        </a>
                    </div>
                </div>

                <!-- İşletmem -->
                <div class="space-y-1">
                    <button @click="openDropdown = openDropdown === 'isletme' ? null : 'isletme'"
                            :class="{ 'bg-gray-100 dark:bg-gray-900': openDropdown === 'isletme' || {{ request()->is('isletmem*') ? 'true' : 'false' }} }"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="font-medium">İşletmem</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'isletme' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openDropdown === 'isletme' || {{ request()->is('isletmem*') ? 'true' : 'false' }}"
                         x-collapse
                         class="ml-8 space-y-1">
                        <a href="{{ route('isletmem.kullanicilar') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('isletmem.kullanicilar') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Kullanıcı Yönetimi
                        </a>
                        <a href="{{ route('musteri.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('musteri.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Müşteri Yönetimi
                        </a>
                        <a href="{{ route('isletmem.kuryeler') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('isletmem.kuryeler') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Kurye Yönetimi
                        </a>
                        <a href="{{ route('restoran.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('restoran.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Restoran Yönetimi
                        </a>
                        <a href="{{ route('kategori.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('kategori.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Kategori Yönetimi
                        </a>
                    </div>
                </div>

                <!-- Hesap Ayarları -->
                <div class="space-y-1">
                    <button @click="openDropdown = openDropdown === 'ayarlar' ? null : 'ayarlar'"
                            :class="{ 'bg-gray-100 dark:bg-gray-900': openDropdown === 'ayarlar' || {{ request()->is('ayarlar*') ? 'true' : 'false' }} }"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="font-medium">Hesap Ayarları</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'ayarlar' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openDropdown === 'ayarlar' || {{ request()->is('ayarlar*') ? 'true' : 'false' }}"
                         x-collapse
                         class="ml-8 space-y-1">
                        <a href="{{ route('ayarlar.genel') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('ayarlar.genel') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Genel Ayarlar
                        </a>
                        <a href="{{ route('ayarlar.uygulama') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('ayarlar.uygulama') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Uygulama Ayarları
                        </a>
                        <a href="{{ route('ayarlar.odeme') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('ayarlar.odeme') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Ödeme Yöntemleri
                        </a>
                        <a href="{{ route('ayarlar.bildirim') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('ayarlar.bildirim') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Bildirim ayarları
                        </a>
                    </div>
                </div>

                <!-- Tema yapılandırması -->
                <a href="{{ route('tema') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tema') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    <span class="font-medium">Tema yapılandırması</span>
                </a>

                <!-- Teknik Destek -->
                <a href="{{ route('destek') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('destek') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-medium">Teknik Destek</span>
                </a>

            </nav>

            <!-- Sidebar Footer - User Section -->
            <div class="border-t border-gray-200 dark:border-gray-800 p-4 mt-auto">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-black dark:bg-white rounded-full flex items-center justify-center">
                            <span class="text-white dark:text-black font-medium text-sm">{{ substr(auth()->user()->name, 0, 2) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-black dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
                
                @if($canSwitchPanel ?? false)
                <a href="{{ route('panel.selection') }}" class="w-full px-3 py-2 mb-2 text-sm text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-colors flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Panel Değiştir</span>
                </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-3 py-2 text-sm text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-colors text-left flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Çıkış Yap</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Navbar -->
            <header class="bg-white dark:bg-[#181818] border-b border-gray-200 dark:border-gray-800 h-16">
                <div class="h-full px-4 flex items-center justify-between">

                    <!-- Left: Menu Toggle & Search -->
                    <div class="flex items-center space-x-4">
                        <button @click="sidebarOpen = !sidebarOpen"
                                class="lg:hidden p-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <div class="hidden md:flex items-center space-x-2 px-3 py-2 bg-gray-100 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" placeholder="Ara ⌘ K"
                                   class="bg-transparent border-0 focus:outline-none text-sm text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 w-64">
                        </div>
                    </div>

                    <!-- Right: Dark Mode Toggle & Notifications -->
                    <div class="flex items-center space-x-3">

                        <!-- Dark Mode Toggle -->
                        <button @click="darkMode = !darkMode"
                                class="p-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>

                        <!-- Notifications -->
                        <button class="relative p-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-black dark:bg-white rounded-full"></span>
                        </button>

                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-white dark:bg-[#181818] p-6">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>

        </div>

    </div>

    <!-- Toast Notification System -->
    @include('components.toast')
    
    <!-- Confirm Dialog Component -->
    @include('components.confirm-dialog')

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
