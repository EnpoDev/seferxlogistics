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

    $sidebarWidthClass = match($sidebarWidth) {
        'narrow' => 'w-52',
        'wide' => 'w-80',
        default => 'w-64',
    };
@endphp
<!DOCTYPE html>
<html lang="tr" class="{{ $serverDarkClass }} {{ $compactMode ? 'compact-mode' : '' }} {{ !$animationsEnabled ? 'no-animations' : '' }}"
      x-data="themeManagerAdmin()"
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
        function themeManagerAdmin() {
            return {
                darkMode: {!! $initialDarkMode !!},
                sidebarOpen: {!! $sidebarAutoHide ? 'window.innerWidth >= 1024' : 'true' !!},
                openDropdown: null,
                themeMode: '{{ $themeMode }}',
                compactMode: {!! $compactMode ? 'true' : 'false' !!},
                animationsEnabled: {!! $animationsEnabled ? 'true' : 'false' !!},
                sidebarAutoHide: {!! $sidebarAutoHide ? 'true' : 'false' !!},
                init() {
                    if (this.themeMode === 'system') {
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                            if (this.themeMode === 'system') this.darkMode = e.matches;
                        });
                    }
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
    <title>{{ $title ?? 'SeferX Lojistik - Admin Panel' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/tr.js"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    @stack('styles')

    <style>
        .dark { background-color: #181818; }
        .compact-mode .p-6 { padding: 1rem; }
        .compact-mode .p-4 { padding: 0.75rem; }
        .compact-mode .space-y-4 > * + * { margin-top: 0.75rem; }
        .compact-mode .space-y-6 > * + * { margin-top: 1rem; }
        .compact-mode .gap-4 { gap: 0.75rem; }
        .compact-mode .gap-6 { gap: 1rem; }
        .compact-mode .mb-6 { margin-bottom: 1rem; }
        .compact-mode .text-2xl { font-size: 1.25rem; }

        .no-animations *, .no-animations *::before, .no-animations *::after {
            animation-duration: 0s !important;
            transition-duration: 0s !important;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.2s ease-out; }
        .animate-slideUp { animation: slideUp 0.3s ease-out; }

        button, a, input, select, textarea { transition: all 0.2s ease; }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        .dark input:focus, .dark select:focus, .dark textarea:focus {
            border-color: #fff;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-white dark:bg-[#181818] transition-colors duration-200">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 {{ $sidebarWidthClass }} bg-white dark:bg-[#181818] border-r border-gray-200 dark:border-gray-800 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static flex flex-col">

            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-20 px-4 border-b border-gray-200 dark:border-gray-800">
                <a href="{{ route('admin.dashboard') }}" class="flex mr-10 items-center space-x-2">
                    <img src="{{ asset('logo-dark.png') }}" alt="SeferX Logo" class="h-16 w-auto dark:hidden">
                    <img src="{{ asset('logo-white.png') }}" alt="SeferX Logo" class="h-16 w-auto hidden dark:block">
                </a>
            </div>

            <!-- Admin Bilgisi -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-red-50 dark:bg-red-900/20">
                <p class="text-sm font-medium text-red-700 dark:text-red-400">{{ auth()->user()->name }}</p>
                <p class="text-xs text-red-600 dark:text-red-500">Super Admin</p>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">

                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>

                <!-- Bayiler -->
                <a href="{{ route('admin.bayiler.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.bayiler.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="font-medium">Bayiler</span>
                </a>

                <!-- Planlar -->
                <a href="{{ route('admin.planlar.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.planlar.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="font-medium">Planlar</span>
                </a>

                <!-- Kullanicilar -->
                <a href="{{ route('admin.kullanicilar.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.kullanicilar.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-medium">Kullanıcılar</span>
                </a>

                <!-- Subeler -->
                <a href="{{ route('admin.subeler.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.subeler.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-medium">Şubeler</span>
                </a>

                <!-- Kuryeler -->
                <a href="{{ route('admin.kuryeler.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.kuryeler.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="font-medium">Kuryeler</span>
                </a>

                <!-- Siparisler -->
                <a href="{{ route('admin.siparisler.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.siparisler.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <span class="font-medium">Siparişler</span>
                </a>

                <div class="border-t border-gray-200 dark:border-gray-800 my-3"></div>

                <!-- Entegrasyonlar -->
                <a href="{{ route('admin.entegrasyonlar.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.entegrasyonlar.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span class="font-medium">Entegrasyonlar</span>
                </a>

                <div class="border-t border-gray-200 dark:border-gray-800 my-3"></div>

                <!-- Islemler -->
                <a href="{{ route('admin.islemler.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.islemler.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span class="font-medium">İşlemler</span>
                </a>

                <!-- Destek -->
                <a href="{{ route('admin.destek.index') }}"
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.destek.*') ? 'bg-black dark:bg-white text-white dark:text-black' : 'text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-medium">Destek Talepleri</span>
                </a>

            </nav>

            <!-- Sidebar Footer -->
            <div class="border-t border-gray-200 dark:border-gray-800 p-4 mt-auto">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-medium text-sm">{{ substr(auth()->user()->name, 0, 2) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-black dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('panel.selection') }}" class="w-full px-3 py-2 mb-2 text-sm text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-colors flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Panel Değiştir</span>
                </a>

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

                    <div class="flex items-center space-x-4">
                        <button @click="sidebarOpen = !sidebarOpen"
                                class="lg:hidden p-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <div class="hidden md:flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <span class="text-sm font-medium text-red-700 dark:text-red-400">Admin Panel</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button @click="darkMode = !darkMode"
                                class="p-2 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
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

    @include('components.toast')
    @include('components.confirm-dialog')

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
