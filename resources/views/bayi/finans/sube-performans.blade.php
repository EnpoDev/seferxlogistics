<x-bayi-layout>
    <x-slot name="title">Sube Performans Raporu - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('bayi.finans.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-black dark:text-white">Sube Performans Raporu</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}</p>
                </div>
            </div>
            <select onchange="window.location.href='?period='+this.value" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bu Ay</option>
                <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>Bu Ceyrek</option>
            </select>
        </div>

        <!-- Branch Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($branchPerformance as $branch)
                <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-black dark:text-white">{{ $branch['branch_name'] }}</h3>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Toplam Gelir</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($branch['total_revenue'], 2) }} TL</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Siparis Sayisi</span>
                            <span class="text-black dark:text-white">{{ $branch['order_count'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Teslimat Geliri</span>
                            <span class="text-black dark:text-white">{{ number_format($branch['delivery_fees'], 2) }} TL</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Ort. Siparis</span>
                            <span class="text-black dark:text-white">{{ number_format($branch['avg_order_value'], 2) }} TL</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Iptal Sayisi</span>
                            <span class="text-red-600 dark:text-red-400">{{ $branch['cancelled_count'] }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500">
                    Sube verisi bulunamadi.
                </div>
            @endforelse
        </div>
    </div>
</x-bayi-layout>
