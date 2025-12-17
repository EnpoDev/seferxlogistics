<x-bayi-layout>
    <x-slot name="title">İstatistik - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">İstatistikler</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Genel performans ve analiz verileri</p>
            </div>
            <a href="{{ route('bayi.gelismis-istatistik') }}" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity font-medium">
                Gelişmiş İstatistik
            </a>
        </div>

        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Bugünkü Sipariş</p>
                <p class="text-3xl font-bold text-black dark:text-white mt-2">{{ $stats['today_orders'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $stats['pending_orders'] ?? 0 }} beklemede</p>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-green-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Aktif Kurye</p>
                <p class="text-3xl font-bold text-black dark:text-white mt-2">{{ $stats['active_couriers'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $stats['on_delivery_orders'] ?? 0 }} yolda</p>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-orange-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Ort. Teslimat Süresi</p>
                <p class="text-3xl font-bold text-black dark:text-white mt-2">{{ $stats['avg_delivery_time'] }} <span class="text-lg">dk</span></p>
                <p class="text-xs text-gray-500 mt-1">Son 30 gün ortalaması</p>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Bugünkü Gelir</p>
                <p class="text-3xl font-bold text-black dark:text-white mt-2">₺{{ number_format($stats['today_revenue'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Completion Rate -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Tamamlanma Oranı (Son 30 Gün)</h3>
            <div class="flex items-center gap-4">
                <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full transition-all duration-500" style="width: {{ $stats['completion_rate'] }}%"></div>
                </div>
                <span class="text-2xl font-bold text-black dark:text-white">%{{ $stats['completion_rate'] }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-2">Teslim edilen siparişlerin toplam siparişlere oranı</p>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                <p class="text-xs text-yellow-600 font-medium uppercase tracking-wider">Bekleyen</p>
                <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">{{ $stats['pending_orders'] ?? 0 }}</p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Yolda</p>
                <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $stats['on_delivery_orders'] ?? 0 }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                <p class="text-xs text-green-600 font-medium uppercase tracking-wider">Aktif Kurye</p>
                <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $stats['active_couriers'] }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                <p class="text-xs text-purple-600 font-medium uppercase tracking-wider">Ort. Teslimat</p>
                <p class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ $stats['avg_delivery_time'] }} dk</p>
            </div>
        </div>
    </div>
</x-bayi-layout>
