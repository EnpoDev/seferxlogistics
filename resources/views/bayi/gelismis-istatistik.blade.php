<x-bayi-layout>
    <x-slot name="title">Gelişmiş İstatistik - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Gelişmiş İstatistikler</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Detaylı analiz ve raporlar</p>
            </div>
            <div class="flex items-center space-x-3">
                <select class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800">
                    <option>Son 7 Gün</option>
                    <option>Son 30 Gün</option>
                    <option>Bu Ay</option>
                    <option>Geçen Ay</option>
                </select>
            </div>
        </div>

        <!-- Grafik ve Tablolar -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Günlük Sipariş Grafiği (CSS Bar Chart) -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-bold text-black dark:text-white mb-6">Günlük Sipariş Dağılımı</h3>
                <div class="flex items-end justify-between h-64 gap-2">
                    @php $maxOrders = max($stats['orders']) ?: 1; @endphp
                    @foreach($stats['orders'] as $index => $count)
                    <div class="flex flex-col items-center flex-1 group">
                        <div class="relative w-full bg-blue-100 dark:bg-blue-900/30 rounded-t-lg transition-all duration-300 group-hover:bg-blue-200 dark:group-hover:bg-blue-800/50" 
                             style="height: {{ ($count / $maxOrders) * 100 }}%">
                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-black text-white text-xs py-1 px-2 rounded">
                                {{ $count }}
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left">{{ $stats['dates'][$index] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Günlük Gelir Grafiği (CSS Bar Chart) -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-bold text-black dark:text-white mb-6">Günlük Gelir Dağılımı</h3>
                <div class="flex items-end justify-between h-64 gap-2">
                    @php $maxRevenue = max($stats['revenue']) ?: 1; @endphp
                    @foreach($stats['revenue'] as $index => $amount)
                    <div class="flex flex-col items-center flex-1 group">
                        <div class="relative w-full bg-green-100 dark:bg-green-900/30 rounded-t-lg transition-all duration-300 group-hover:bg-green-200 dark:group-hover:bg-green-800/50" 
                             style="height: {{ ($amount / $maxRevenue) * 100 }}%">
                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-black text-white text-xs py-1 px-2 rounded">
                                ₺{{ number_format($amount, 0) }}
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left">{{ $stats['dates'][$index] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- En İyi Kuryeler -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-bold text-black dark:text-white mb-4">En İyi Kuryeler</h3>
                <div class="space-y-4">
                    @foreach($stats['top_couriers'] as $courier)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-xs">
                                {{ substr($courier->name, 0, 2) }}
                            </div>
                            <span class="font-medium text-black dark:text-white">{{ $courier->name }}</span>
                        </div>
                        <span class="font-bold text-black dark:text-white">{{ $courier->orders_count }} Sipariş</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- En İyi Şubeler -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-bold text-black dark:text-white mb-4">En İyi Şubeler</h3>
                <div class="space-y-4">
                    @foreach($stats['top_branches'] as $branch)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center font-bold text-xs">
                                {{ substr($branch->name, 0, 2) }}
                            </div>
                            <span class="font-medium text-black dark:text-white">{{ $branch->name }}</span>
                        </div>
                        <span class="font-bold text-black dark:text-white">{{ $branch->orders_count }} Sipariş</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-bayi-layout>

