<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Kurye Yönetim Sistemi' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .dark { background-color: #181818; }
    </style>
</head>
<body class="bg-white dark:bg-[#181818] transition-colors duration-200">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: true, openDropdown: null }">

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-[#181818] border-r border-gray-200 dark:border-gray-800 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static">

            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-black dark:bg-white rounded-lg flex items-center justify-center">
                        <span class="text-white dark:text-black font-bold text-lg">K</span>
                    </div>
                    <span class="text-lg font-semibold text-black dark:text-white">Kurye Yönetim</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">

                <!-- Ana Sayfa / Harita -->
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
                        <a href="{{ route('siparis.aktif') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.aktif') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Aktif Siparişler
                        </a>
                        <a href="{{ route('siparis.gecmis') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.gecmis') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Sipariş Geçmişi
                        </a>
                        <a href="{{ route('siparis.iptal') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.iptal') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            İptal Edilen
                        </a>
                        <a href="{{ route('siparis.istatistik') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('siparis.istatistik') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            İstatistikler
                        </a>
                    </div>
                </div>

                <!-- Gelişmiş İstatistik -->
                <a href="{{ route('gelismis-istatistik') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('gelismis-istatistik') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="font-medium">Gelişmiş İstatistik</span>
                </a>

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
                        <a href="{{ route('yonetim.kullanicilar') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('yonetim.kullanicilar') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Kullanıcılar
                        </a>
                        <a href="{{ route('yonetim.roller') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('yonetim.roller') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Rol & Yetki
                        </a>
                    </div>
                </div>

                <!-- Menü Yönetimi -->
                <a href="{{ route('menu') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('menu') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span class="font-medium">Menü</span>
                </a>

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
                        <a href="{{ route('isletmem.bilgiler') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('isletmem.bilgiler') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            İşletme Bilgileri
                        </a>
                        <a href="{{ route('isletmem.subeler') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('isletmem.subeler') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Şube Yönetimi
                        </a>
                    </div>
                </div>

                <!-- Hesap Ayarları -->
                <div class="space-y-1">
                    <button @click="openDropdown = openDropdown === 'hesap' ? null : 'hesap'"
                            :class="{ 'bg-gray-100 dark:bg-gray-900': openDropdown === 'hesap' || {{ request()->is('hesap*') ? 'true' : 'false' }} }"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="font-medium">Hesap Ayarları</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'hesap' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openDropdown === 'hesap' || {{ request()->is('hesap*') ? 'true' : 'false' }}"
                         x-collapse
                         class="ml-8 space-y-1">
                        <a href="{{ route('hesap.profil') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('hesap.profil') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Profil Ayarları
                        </a>
                        <a href="{{ route('hesap.guvenlik') }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('hesap.guvenlik') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                            Güvenlik
                        </a>
                    </div>
                </div>

            </nav>

            <!-- Sidebar Footer -->
            <div class="border-t border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-black dark:bg-white rounded-full flex items-center justify-center">
                            <span class="text-white dark:text-black font-medium text-sm">MH</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-black dark:text-white truncate">Muhammet Hüseyin</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">Yönetici</p>
                        </div>
                    </div>
                </div>
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
                {{ $slot }}
            </main>

        </div>

    </div>

    @livewireScripts
</body>
</html>
