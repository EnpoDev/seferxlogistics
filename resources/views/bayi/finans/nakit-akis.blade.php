<x-bayi-layout>
    <x-slot name="title">Nakit Akis Raporu - Bayi Paneli</x-slot>

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
                    <h1 class="text-3xl font-bold text-black dark:text-white">Nakit Akis Raporu</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}</p>
                </div>
            </div>
            <select onchange="window.location.href='?period='+this.value" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bu Ay</option>
                <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Gecen Ay</option>
            </select>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Income -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-black dark:text-white">Gelirler</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nakit</span>
                        <span class="text-black dark:text-white">{{ number_format($cashFlow['income']['cash'], 2) }} TL</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Kredi Karti</span>
                        <span class="text-black dark:text-white">{{ number_format($cashFlow['income']['card'], 2) }} TL</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Online</span>
                        <span class="text-black dark:text-white">{{ number_format($cashFlow['income']['online'], 2) }} TL</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-800 flex justify-between">
                        <span class="font-semibold text-black dark:text-white">Toplam</span>
                        <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($cashFlow['income']['total'], 2) }} TL</span>
                    </div>
                </div>
            </div>

            <!-- Expenses -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-black dark:text-white">Giderler</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Kurye Odemeleri</span>
                        <span class="text-black dark:text-white">{{ number_format($cashFlow['expenses']['courier_payments'], 2) }} TL</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-800 flex justify-between">
                        <span class="font-semibold text-black dark:text-white">Toplam</span>
                        <span class="font-bold text-red-600 dark:text-red-400">{{ number_format($cashFlow['expenses']['courier_payments'], 2) }} TL</span>
                    </div>
                </div>
            </div>

            <!-- Net -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold">Net Nakit Akisi</h3>
                </div>
                <p class="text-4xl font-bold">{{ number_format($cashFlow['net_cash_flow'], 2) }} TL</p>
                <p class="text-white/80 text-sm mt-2">Gelir - Gider</p>
            </div>
        </div>

        <!-- Daily Breakdown -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="font-semibold text-black dark:text-white mb-4">Gunluk Dagilim</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Siparis</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Toplam</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Nakit</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Kart</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Online</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($dailyRevenue as $day)
                            <tr>
                                <td class="px-4 py-3 text-black dark:text-white">{{ $day['date'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $day['order_count'] }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-black dark:text-white">{{ number_format($day['total_revenue'], 2) }} TL</td>
                                <td class="px-4 py-3 text-center text-green-600">{{ number_format($day['cash'], 2) }} TL</td>
                                <td class="px-4 py-3 text-center text-blue-600">{{ number_format($day['card'], 2) }} TL</td>
                                <td class="px-4 py-3 text-center text-purple-600">{{ number_format($day['online'], 2) }} TL</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-bayi-layout>
