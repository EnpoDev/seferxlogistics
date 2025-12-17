<div>
    <div class="space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Kurye Takip Sistemi</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Kuryelerinizi canlı takip edin ve yönetin</p>
            </div>
            <button wire:click="refreshData" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity flex items-center gap-2">
                <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refreshData" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Yenile</span>
            </button>
        </div>

        <!-- Main Map Card -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-black dark:bg-white rounded-lg">
                            <svg class="w-6 h-6 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-black dark:text-white">Kurye Haritası</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Kuryelerinizi canlı takip etmek, anlık durumlarını öğrenebilmek için</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        Canlı
                    </span>
                </div>

                <!-- Interactive Map -->
                <div id="courier-map" 
                     class="rounded-lg h-96 mb-4 border border-gray-200 dark:border-gray-800 z-0"
                     data-couriers='@json($couriers)'
                     data-orders='@json($orders)'
                     wire:ignore></div>

                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Aktif Kurye</p>
                                <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ $stats['active_couriers'] ?? 0 }}</p>
                            </div>
                            <div class="p-3 bg-green-500 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Yolda</p>
                                <p class="text-2xl font-bold text-orange-500 mt-1">{{ $stats['on_delivery_orders'] ?? 0 }}</p>
                            </div>
                            <div class="p-3 bg-orange-500 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Bekleyen</p>
                                <p class="text-2xl font-bold text-yellow-500 mt-1">{{ $stats['pending_orders'] ?? 0 }}</p>
                            </div>
                            <div class="p-3 bg-yellow-500 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Bugün Tamamlandı</p>
                                <p class="text-2xl font-bold text-emerald-500 mt-1">{{ $stats['completed_today'] ?? 0 }}</p>
                            </div>
                            <div class="p-3 bg-emerald-500 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tools Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Kurye Takip Araçları -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-black dark:bg-white rounded-lg">
                            <svg class="w-6 h-6 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Kuryeler</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ count($couriers) }} kurye haritada</p>
                    <a href="{{ route('isletmem.kuryeler') }}" class="block w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors text-center">
                        Kurye Yönetimi
                    </a>
                </div>
            </div>

            <!-- Aktif Siparişler -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-black dark:bg-white rounded-lg">
                            <svg class="w-6 h-6 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                            {{ count($orders) }} Aktif
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Siparişler</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Aktif siparişleri görüntüle</p>
                    <a href="{{ route('siparis.liste') }}" class="block w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors text-center">
                        Sipariş Listesi
                    </a>
                </div>
            </div>

            <!-- Yeni Sipariş -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-black dark:bg-white rounded-lg">
                            <svg class="w-6 h-6 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Yeni Sipariş</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Hızlı sipariş oluştur</p>
                    <a href="{{ route('siparis.create') }}" class="block w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors text-center">
                        Sipariş Oluştur
                    </a>
                </div>
            </div>

        </div>

        <!-- Legend -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Harita Göstergeleri</h3>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Müsait Kurye</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-orange-500 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Meşgul Kurye</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Çevrimdışı</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Bekleyen Sipariş</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-blue-500 rounded"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hazırlanıyor</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-indigo-500 rounded"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Yolda</span>
                </div>
            </div>
        </div>

    </div>
</div>
