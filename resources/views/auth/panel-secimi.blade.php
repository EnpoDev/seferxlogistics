<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Seçimi - SeferX Lojistik</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .dark { background-color: #181818; }
    </style>
</head>
<body class="bg-white dark:bg-[#181818] transition-colors duration-200">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-4xl w-full">
            
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="flex items-center justify-center mb-6">
                    <img src="{{ asset('logo.png') }}" alt="SeferX Logo" class="h-16 w-auto">
                </div>
                <h1 class="text-3xl font-bold text-black dark:text-white mb-2">Hoş Geldiniz, {{ auth()->user()->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400">Hangi panele erişmek istersiniz?</p>
            </div>

            <!-- Panel Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                
                @if(auth()->user()->hasBayi())
                <!-- Bayi Panel Card -->
                <a href="{{ route('panel.select', ['panel' => 'bayi']) }}" 
                   class="group bg-white dark:bg-[#181818] border-2 border-gray-200 dark:border-gray-800 rounded-2xl p-8 hover:border-black dark:hover:border-white transition-all duration-200 hover:shadow-xl">
                    <div class="flex flex-col items-center text-center space-y-4">
                        <div class="p-4 bg-black dark:bg-white rounded-xl group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-12 h-12 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-black dark:text-white mb-2">Bayi Paneli</h3>
                            <p class="text-gray-600 dark:text-gray-400">Kurye yönetimi, işletmeler, vardiya saatleri ve bölgelendirme</p>
                        </div>
                        <div class="flex items-center space-x-2 text-black dark:text-white font-medium">
                            <span>Devam Et</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </div>
                    </div>
                </a>
                @endif

                @if(auth()->user()->hasIsletme())
                <!-- İşletme Panel Card -->
                <a href="{{ route('panel.select', ['panel' => 'isletme']) }}" 
                   class="group bg-white dark:bg-[#181818] border-2 border-gray-200 dark:border-gray-800 rounded-2xl p-8 hover:border-black dark:hover:border-white transition-all duration-200 hover:shadow-xl">
                    <div class="flex flex-col items-center text-center space-y-4">
                        <div class="p-4 bg-black dark:bg-white rounded-xl group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-12 h-12 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-black dark:text-white mb-2">İşletme Paneli</h3>
                            <p class="text-gray-600 dark:text-gray-400">Sipariş yönetimi, menü, müşteriler ve işletme ayarları</p>
                        </div>
                        <div class="flex items-center space-x-2 text-black dark:text-white font-medium">
                            <span>Devam Et</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </div>
                    </div>
                </a>
                @endif

            </div>

            <!-- Dark Mode Toggle & Logout -->
            <div class="flex items-center justify-center space-x-4">
                <button @click="darkMode = !darkMode"
                        class="p-3 rounded-lg text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">
                        Çıkış Yap
                    </button>
                </form>
            </div>

        </div>
    </div>
</body>
</html>

