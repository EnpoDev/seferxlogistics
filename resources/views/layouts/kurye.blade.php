<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('kuryeDarkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('kuryeDarkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#000000">
    <title>{{ $title ?? 'SeferX Kurye' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* Mobile optimizations */
        html, body {
            overscroll-behavior: none;
            -webkit-overflow-scrolling: touch;
        }
        
        .dark { background-color: #0a0a0a; }
        
        /* Safe area support for iOS */
        .safe-top { padding-top: env(safe-area-inset-top); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
        
        /* Pull to refresh prevention */
        body {
            overflow-y: auto;
        }
        
        /* Smooth animations */
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Status colors */
        .status-available { background-color: #10b981; }
        .status-busy { background-color: #f59e0b; }
        .status-offline { background-color: #6b7280; }
        .status-on_break { background-color: #eab308; }
        
        /* Order status colors */
        .order-pending { background-color: #f59e0b; }
        .order-assigned { background-color: #3b82f6; }
        .order-picked_up { background-color: #8b5cf6; }
        .order-on_way { background-color: #06b6d4; }
        .order-delivered { background-color: #10b981; }
        .order-cancelled { background-color: #ef4444; }
        
        /* Bottom navigation */
        .bottom-nav {
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .dark .bottom-nav {
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.5);
        }
        
        /* Touch feedback */
        .touch-active:active {
            transform: scale(0.97);
            opacity: 0.8;
        }
        
        /* Card hover/active states */
        .card-interactive {
            transition: all 0.2s ease;
        }
        
        .card-interactive:active {
            transform: scale(0.98);
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-[#0a0a0a] min-h-screen">
    <div class="flex flex-col min-h-screen" x-data="kuryeApp()">
        
        <!-- Header -->
        <header class="sticky top-0 z-40 bg-white dark:bg-black border-b border-gray-200 dark:border-gray-800 safe-top">
            <div class="flex items-center justify-between h-14 px-4">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('logo-dark.png') }}" alt="SeferX" class="h-8 w-auto dark:hidden">
                    <img src="{{ asset('logo-white.png') }}" alt="SeferX" class="h-8 w-auto hidden dark:block">
                </div>
                
                @auth('courier')
                <div class="flex items-center space-x-2">
                    <!-- Status Indicator -->
                    <button @click="showStatusModal = true" class="flex items-center space-x-2 px-3 py-1.5 rounded-full text-xs font-medium touch-active
                        {{ Auth::guard('courier')->user()->status === 'available' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                        {{ Auth::guard('courier')->user()->status === 'busy' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                        {{ Auth::guard('courier')->user()->status === 'offline' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}
                        {{ Auth::guard('courier')->user()->status === 'on_break' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                        <span class="w-2 h-2 rounded-full 
                            {{ Auth::guard('courier')->user()->status === 'available' ? 'bg-green-500' : '' }}
                            {{ Auth::guard('courier')->user()->status === 'busy' ? 'bg-orange-500' : '' }}
                            {{ Auth::guard('courier')->user()->status === 'offline' ? 'bg-gray-500' : '' }}
                            {{ Auth::guard('courier')->user()->status === 'on_break' ? 'bg-yellow-500' : '' }}"></span>
                        <span>{{ Auth::guard('courier')->user()->getStatusLabel() }}</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode" class="p-2 rounded-full text-gray-600 dark:text-gray-400 touch-active">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>
                </div>
                @endauth
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto pb-20">
            @yield('content')
        </main>

        <!-- Bottom Navigation -->
        @auth('courier')
        <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-black border-t border-gray-200 dark:border-gray-800 bottom-nav safe-bottom">
            <div class="flex items-center justify-around h-16 px-2">
                <a href="{{ route('kurye.dashboard') }}" class="flex flex-col items-center justify-center flex-1 py-2 touch-active {{ request()->routeIs('kurye.dashboard') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-xs mt-1 font-medium">Ana Sayfa</span>
                </a>
                
                <a href="{{ route('kurye.orders') }}" class="flex flex-col items-center justify-center flex-1 py-2 touch-active {{ request()->routeIs('kurye.orders') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                    <div class="relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        @if(Auth::guard('courier')->user()->active_orders_count > 0)
                        <span class="absolute -top-1 -right-1 z-10 w-4 h-4 bg-red-500 text-white text-[10px] leading-none rounded-full flex items-center justify-center ring-2 ring-white dark:ring-black">
                            {{ Auth::guard('courier')->user()->active_orders_count }}
                        </span>
                        @endif
                    </div>
                    <span class="text-xs mt-1 font-medium">Siparişler</span>
                </a>
                
                <a href="{{ route('kurye.pool') }}" class="flex flex-col items-center justify-center flex-1 py-2 touch-active {{ request()->routeIs('kurye.pool') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span class="text-xs mt-1 font-medium">Havuz</span>
                </a>
                
                <a href="{{ route('kurye.history') }}" class="flex flex-col items-center justify-center flex-1 py-2 touch-active {{ request()->routeIs('kurye.history') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs mt-1 font-medium">Geçmiş</span>
                </a>
                
                <a href="{{ route('kurye.profile') }}" class="flex flex-col items-center justify-center flex-1 py-2 touch-active {{ request()->routeIs('kurye.profile') ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-xs mt-1 font-medium">Profil</span>
                </a>
            </div>
        </nav>
        @endauth

        <!-- Status Change Modal -->
        @auth('courier')
        <div x-show="showStatusModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-end justify-center bg-black/50"
             @click.self="showStatusModal = false">
            <div x-show="showStatusModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-full"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-full"
                 class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-t-2xl p-6 safe-bottom">
                <div class="w-12 h-1 bg-gray-300 dark:bg-gray-700 rounded-full mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4 text-center">Durumunuzu Değiştirin</h3>
                
                <div class="space-y-3">
                    <button @click="updateStatus('available')" class="w-full flex items-center space-x-3 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border-2 border-transparent hover:border-green-500 transition-colors touch-active">
                        <span class="w-4 h-4 rounded-full bg-green-500"></span>
                        <span class="font-medium text-green-800 dark:text-green-400">Müsait</span>
                    </button>
                    
                    <button @click="updateStatus('on_break')" class="w-full flex items-center space-x-3 p-4 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 border-2 border-transparent hover:border-yellow-500 transition-colors touch-active">
                        <span class="w-4 h-4 rounded-full bg-yellow-500"></span>
                        <span class="font-medium text-yellow-800 dark:text-yellow-400">Molada</span>
                    </button>
                    
                    <button @click="updateStatus('offline')" class="w-full flex items-center space-x-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border-2 border-transparent hover:border-gray-500 transition-colors touch-active">
                        <span class="w-4 h-4 rounded-full bg-gray-500"></span>
                        <span class="font-medium text-gray-800 dark:text-gray-400">Çevrimdışı</span>
                    </button>
                </div>
                
                <button @click="showStatusModal = false" class="w-full mt-4 py-3 text-gray-600 dark:text-gray-400 font-medium touch-active">
                    İptal
                </button>
            </div>
        </div>
        @endauth

        <!-- Toast Notification -->
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed top-20 left-4 right-4 z-50">
            <div :class="{
                'bg-green-500': toast.type === 'success',
                'bg-red-500': toast.type === 'error',
                'bg-yellow-500': toast.type === 'warning',
                'bg-blue-500': toast.type === 'info'
            }" class="rounded-xl p-4 text-white text-center font-medium shadow-lg">
                <span x-text="toast.message"></span>
            </div>
        </div>
    </div>

    <script>
        function kuryeApp() {
            return {
                showStatusModal: false,
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },
                
                async updateStatus(status) {
                    try {
                        const response = await fetch('{{ route("kurye.status.update") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ status })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.showToast('Durum güncellendi', 'success');
                            this.showStatusModal = false;
                            // Reload page to reflect status change
                            setTimeout(() => location.reload(), 500);
                        } else {
                            this.showToast(data.message || 'Bir hata oluştu', 'error');
                        }
                    } catch (error) {
                        this.showToast('Bağlantı hatası', 'error');
                    }
                },
                
                showToast(message, type = 'success') {
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },
                
                // Location tracking
                startLocationTracking() {
                    if ('geolocation' in navigator) {
                        navigator.geolocation.watchPosition(
                            (position) => {
                                this.updateLocation(position.coords.latitude, position.coords.longitude);
                            },
                            (error) => {
                                console.error('Location error:', error);
                            },
                            {
                                enableHighAccuracy: true,
                                maximumAge: 30000,
                                timeout: 27000
                            }
                        );
                    }
                },
                
                async updateLocation(lat, lng) {
                    try {
                        await fetch('{{ route("kurye.location.update") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ lat, lng })
                        });
                    } catch (error) {
                        console.error('Location update error:', error);
                    }
                },
                
                init() {
                    @auth('courier')
                    this.startLocationTracking();
                    @endauth
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>

